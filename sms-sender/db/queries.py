"""
SQL Server queries for the SMS sender worker.

send_status on campaign_recipients:
  1 = pending   2 = sent   3 = failed   4 = blocked (in no-send window when cancelled)

campaign_status on campaigns:
  1=draft  2=scheduled  3=processing  4=completed  5=paused  6=cancelled
"""
from __future__ import annotations

import json
from dataclasses import dataclass, field
from datetime import datetime
from typing import Any

from db.connection import get_pool
from utils.logger import get_logger

logger = get_logger(__name__)

# ── campaign status constants ─────────────────────────────────────────────────
CAMPAIGN_SCHEDULED  = 2
CAMPAIGN_PROCESSING = 3
CAMPAIGN_PAUSED     = 5
CAMPAIGN_CANCELLED  = 6

# ── recipient send_status constants ──────────────────────────────────────────
RECIPIENT_PENDING  = 1
RECIPIENT_SENT     = 2
RECIPIENT_FAILED   = 3
RECIPIENT_BLOCKED  = 4


# ── data classes ──────────────────────────────────────────────────────────────

@dataclass
class CampaignRow:
    id: int
    company_id: int | None
    name: str
    sender: str
    sms_provider: str
    total_recipients: int
    sent_count: int
    failed_count: int
    no_send_rules: list[dict] = field(default_factory=list)


@dataclass
class RecipientRow:
    id: int
    campaign_id: int
    phone: str
    message: str
    segments: int


@dataclass
class SendResult:
    recipient_id: int
    success: bool
    provider_message_id: str | None = None
    error: str | None = None


# ── helpers ───────────────────────────────────────────────────────────────────

def _now() -> str:
    return datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")


def _chunked(lst: list, size: int):
    for i in range(0, len(lst), size):
        yield lst[i : i + size]


def _parse_json(raw: str | None, fallback):
    try:
        return json.loads(raw) if raw else fallback
    except (json.JSONDecodeError, TypeError):
        return fallback


# ── campaign queries ──────────────────────────────────────────────────────────

def fetch_pending_campaigns() -> list[CampaignRow]:
    """
    Returns campaigns ready to process:
    - status=3 (processing): always — resumes after worker restart
    - status=2 (scheduled): only when scheduled_at has been reached
    C-11: uses GETUTCDATE() since Python stores all timestamps in UTC.
    """
    pool = get_pool()
    with pool.get() as conn:
        cursor = conn.cursor()
        cursor.execute("""
            SELECT
                c.id,
                c.company_id,
                c.name,
                ISNULL(c.sender, LEFT(co.name, 16)) AS sender,
                ISNULL(co.settings, '{}')            AS company_settings,
                c.total_recipients,
                c.sent_count,
                c.failed_count,
                c.no_send_rules
            FROM campaigns c
            LEFT JOIN companies co ON co.id = c.company_id
            WHERE c.deleted_at IS NULL
              AND (
                c.campaign_status = ?
                OR (
                  c.campaign_status = ?
                  AND (c.scheduled_at IS NULL OR c.scheduled_at <= GETUTCDATE())
                )
              )
            ORDER BY c.id ASC
        """, CAMPAIGN_PROCESSING, CAMPAIGN_SCHEDULED)

        rows = []
        for row in cursor.fetchall():
            company_cfg = _parse_json(row.company_settings, {})
            provider    = company_cfg.get("sms_provider", "directo")
            rules       = _parse_json(row.no_send_rules, [])
            if not isinstance(rules, list):
                rules = []

            rows.append(CampaignRow(
                id=row.id,
                company_id=row.company_id,
                name=row.name,
                sender=str(row.sender or "SMS")[:16],
                sms_provider=provider,
                total_recipients=row.total_recipients or 0,
                sent_count=row.sent_count or 0,
                failed_count=row.failed_count or 0,
                no_send_rules=rules,
            ))
        return rows


def get_campaign_status(campaign_id: int) -> int:
    """Lightweight status check used mid-processing to detect pause/cancel."""
    pool = get_pool()
    with pool.get() as conn:
        cursor = conn.cursor()
        cursor.execute(
            "SELECT campaign_status FROM campaigns WHERE id = ?", campaign_id
        )
        row = cursor.fetchone()
        return int(row.campaign_status) if row else CAMPAIGN_CANCELLED


def claim_campaign(campaign_id: int, worker_id: str) -> bool:
    """
    Atomically claim a campaign for this worker.
    C-01: handles two cases:
      1. Scheduled (status=2) → move to processing (status=3).
      2. Immediate (already status=3, no worker) → assign worker_id.
         Also reclaims campaigns from dead workers (stale > WORKER_STALE_MINUTES).
    Returns True only if this worker successfully claimed it.
    """
    from config.settings import settings
    pool = get_pool()
    with pool.get() as conn:
        cursor = conn.cursor()

        # Case 1: scheduled → processing
        cursor.execute("""
            UPDATE campaigns
            SET campaign_status = ?, worker_id = ?, updated_at = ?
            WHERE id = ? AND campaign_status = ?
        """, CAMPAIGN_PROCESSING, worker_id, _now(), campaign_id, CAMPAIGN_SCHEDULED)
        conn.commit()
        if cursor.rowcount > 0:
            return True

        # Case 2: immediate (already processing) with no worker, or stale worker
        cursor.execute("""
            UPDATE campaigns
            SET worker_id = ?, updated_at = ?
            WHERE id = ? AND campaign_status = ?
              AND (
                worker_id IS NULL
                OR worker_id = ''
                OR DATEDIFF(MINUTE, updated_at, GETUTCDATE()) > ?
              )
        """, worker_id, _now(), campaign_id, CAMPAIGN_PROCESSING,
            settings.WORKER_STALE_MINUTES)
        conn.commit()
        return cursor.rowcount > 0


def update_campaign_counters(campaign_id: int, sent_delta: int, failed_delta: int) -> dict[str, Any]:
    """Increment sent/failed counters. Returns current cumulative totals."""
    pool = get_pool()
    with pool.get() as conn:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE campaigns
            SET sent_count   = sent_count   + ?,
                failed_count = failed_count + ?,
                updated_at   = ?
            WHERE id = ?
        """, sent_delta, failed_delta, _now(), campaign_id)
        conn.commit()

        cursor.execute(
            "SELECT total_recipients, sent_count, failed_count FROM campaigns WHERE id = ?",
            campaign_id,
        )
        row = cursor.fetchone()
        return {
            "total":  row.total_recipients or 0,
            "sent":   row.sent_count  or 0,
            "failed": row.failed_count or 0,
        }


# ── recipient queries ─────────────────────────────────────────────────────────

def fetch_recipient_batch(campaign_id: int, batch_size: int) -> list[RecipientRow]:
    """Pull next N pending recipients (send_status=1), ordered by id."""
    pool = get_pool()
    with pool.get() as conn:
        cursor = conn.cursor()
        cursor.execute(f"""
            SELECT TOP ({batch_size}) id, phone, message, segments
            FROM campaign_recipients
            WHERE campaign_id = ? AND send_status = ?
            ORDER BY id ASC
        """, campaign_id, RECIPIENT_PENDING)

        return [
            RecipientRow(
                id=r.id,
                campaign_id=campaign_id,
                phone=r.phone,
                message=r.message,
                segments=r.segments or 1,
            )
            for r in cursor.fetchall()
        ]


def apply_send_results(results: list[SendResult]) -> tuple[int, int]:
    """
    Persist send outcomes. Returns (sent_count, failed_count).
    C-06: uses executemany() — one round-trip per status type instead of N individual UPDATEs.
    """
    if not results:
        return 0, 0

    sent_list   = [r for r in results if r.success]
    failed_list = [r for r in results if not r.success]
    now = _now()
    pool = get_pool()

    with pool.get() as conn:
        cursor = conn.cursor()

        if sent_list:
            cursor.executemany("""
                UPDATE campaign_recipients
                SET send_status         = ?,
                    sent_at             = ?,
                    provider_message_id = ?,
                    updated_at          = ?
                WHERE id = ?
            """, [
                (RECIPIENT_SENT, now, r.provider_message_id or "", now, r.recipient_id)
                for r in sent_list
            ])

        if failed_list:
            cursor.executemany("""
                UPDATE campaign_recipients
                SET send_status   = ?,
                    error_message = ?,
                    updated_at    = ?
                WHERE id = ?
            """, [
                (RECIPIENT_FAILED, (r.error or "")[:500], now, r.recipient_id)
                for r in failed_list
            ])

        conn.commit()

    return len(sent_list), len(failed_list)


def get_balance_info(company_id: int) -> tuple[float, float]:
    """
    Returns (balance, price_per_segment) for a company.
    price_per_segment: company override first, then global app_settings fallback.
    """
    pool = get_pool()
    with pool.get() as conn:
        cursor = conn.cursor()
        cursor.execute("""
            SELECT
                co.balance,
                ISNULL(
                    TRY_CAST(JSON_VALUE(co.settings, '$.sms_price_per_segment') AS DECIMAL(10,4)),
                    (
                        SELECT TRY_CAST(value AS DECIMAL(10,4))
                        FROM app_settings
                        WHERE [key] = 'sms_price_per_segment'
                    )
                ) AS price_per_segment
            FROM companies co
            WHERE co.id = ?
        """, company_id)
        row = cursor.fetchone()
        if not row:
            return 0.0, 0.45
        balance = float(row.balance or 0)
        price   = float(row.price_per_segment or 0.45)
        return balance, price


def auto_pause_campaign(campaign_id: int) -> None:
    """Set campaign status to paused (5). Called by worker on insufficient balance."""
    pool = get_pool()
    with pool.get() as conn:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE campaigns SET campaign_status = ?, updated_at = ? WHERE id = ?
        """, CAMPAIGN_PAUSED, _now(), campaign_id)
        conn.commit()


def block_pending_recipients(campaign_id: int) -> int:
    """
    Mark all remaining pending recipients as blocked (send_status=4).
    Called when a campaign is cancelled while recipients are still pending
    inside a no-send window.
    Returns count of rows updated.
    """
    pool = get_pool()
    with pool.get() as conn:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE campaign_recipients
            SET send_status = ?, updated_at = ?
            WHERE campaign_id = ? AND send_status = ?
        """, RECIPIENT_BLOCKED, _now(), campaign_id, RECIPIENT_PENDING)
        conn.commit()
        return cursor.rowcount
