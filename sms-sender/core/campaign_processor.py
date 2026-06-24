"""
CampaignProcessor — drives a single campaign from start to finish.

Loop per iteration:
  1. Check campaign status (pause / cancel detection).
  2. Check no_send_rules (sleep interruptibly until window clears).
  3. Fetch next DB batch of pending recipients.
  4. Send provider chunks concurrently (bounded by HTTP semaphore).
  5. Persist results and update counters.
  6. Notify Laravel webhook every NOTIFY_EVERY recipients.
  Repeat until no pending recipients remain, then send completed=True.

Abort conditions:
  - status=5 (paused)   → stop; Laravel already owns the state.
  - status=6 (cancelled)→ stop; block any remaining pending recipients.
  Both abort WITHOUT sending completed=True (billing stays in Laravel hands).
"""
from __future__ import annotations

import asyncio
from datetime import datetime

from config.settings import settings
from db import queries as q
from db.connection import run_query
from db.queries import (
    CAMPAIGN_CANCELLED,
    CAMPAIGN_PAUSED,
    CampaignRow,
    RecipientRow,
    SendResult,
)
from notifier import webhook
from providers.base import SmsMessage
from providers.router import get_provider
from utils.logger import get_logger
from utils.no_send_rules import is_blocked, seconds_until_clear

logger = get_logger(__name__)

_NOTIFY_EVERY       = 500   # send progress webhook every N recipients
_SLEEP_CHECK_SECS   = 60    # poll status every N seconds while sleeping in window


class CampaignProcessor:
    def __init__(self, campaign: CampaignRow, http_sem: asyncio.Semaphore, worker_id: str) -> None:
        self._campaign   = campaign
        self._sem        = http_sem
        self._worker_id  = worker_id
        self._provider   = get_provider(campaign.sms_provider)
        self._total_sent = campaign.sent_count
        self._total_fail = campaign.failed_count
        self._since_notify = 0
        self._aborted    = False

    # ── entry point ───────────────────────────────────────────────────────────

    async def run(self) -> None:
        c = self._campaign
        logger.info(
            "Campaign %d '%s'  provider=%s  total=%d  sent=%d  failed=%d",
            c.id, c.name, c.sms_provider,
            c.total_recipients, c.sent_count, c.failed_count,
        )

        claimed = await run_query(q.claim_campaign, c.id, self._worker_id)
        if not claimed:
            logger.warning("Campaign %d already claimed by another worker — skip.", c.id)
            return

        try:
            await self._process_loop()
        except Exception as exc:
            logger.exception("Campaign %d unhandled error: %s", c.id, exc)
            return  # leave at status=3; next worker start resumes

        if self._aborted:
            return  # paused or cancelled — Laravel owns status

        # All recipients processed — ask Laravel to close + charge
        await webhook.notify_progress(
            c.id, self._total_sent, self._total_fail,
            c.total_recipients, completed=True,
        )
        logger.info(
            "Campaign %d completed — sent=%d  failed=%d",
            c.id, self._total_sent, self._total_fail,
        )

    # ── main loop ─────────────────────────────────────────────────────────────

    async def _process_loop(self) -> None:
        db_batch  = settings.WORKER_DB_BATCH_SIZE
        bulk_size = self._provider.BULK_SIZE

        while True:
            # ── 1. pause / cancel check ───────────────────────────────────────
            if await self._check_abort():
                return

            # ── 2. no_send_rules check ────────────────────────────────────────
            if self._campaign.no_send_rules:
                now = datetime.now()
                if is_blocked(self._campaign.no_send_rules, now):
                    secs = seconds_until_clear(self._campaign.no_send_rules, now)
                    mins = secs // 60
                    logger.info(
                        "Campaign %d in no-send window — waiting %dm %ds",
                        self._campaign.id, mins, secs % 60,
                    )
                    aborted = await self._sleep_interruptible(secs)
                    if aborted:
                        return
                    continue  # re-check status + rules after sleep

            # ── 3. balance check ──────────────────────────────────────────────
            allowed = await self._check_balance(db_batch)
            if self._aborted:
                return

            # ── 4. fetch next batch ───────────────────────────────────────────
            recipients: list[RecipientRow] = await run_query(
                q.fetch_recipient_batch, self._campaign.id, allowed
            )
            if not recipients:
                break  # all done

            # ── 5. send concurrently in provider-sized chunks ─────────────────
            chunks = [
                recipients[i : i + bulk_size]
                for i in range(0, len(recipients), bulk_size)
            ]
            await asyncio.gather(*[self._send_chunk(chunk) for chunk in chunks])

    # ── chunk sending ─────────────────────────────────────────────────────────

    async def _send_chunk(self, recipients: list[RecipientRow]) -> None:
        messages = [
            SmsMessage(
                recipient_id=r.id,
                phone=r.phone,
                message=r.message,
                sender=self._campaign.sender,
            )
            for r in recipients
        ]

        async with self._sem:
            try:
                bulk_result = await self._provider.send_bulk(messages, self._campaign.name)
            except Exception as exc:
                logger.error(
                    "Campaign %d chunk raised (%d msgs): %s",
                    self._campaign.id, len(messages), exc,
                )
                bulk_result = None

        if bulk_result is None:
            send_results = [
                SendResult(r.id, False, error="Provider exception")
                for r in recipients
            ]
        else:
            send_results = [
                SendResult(
                    recipient_id=r.recipient_id,
                    success=r.success,
                    provider_message_id=r.provider_message_id,
                    error=r.error,
                )
                for r in bulk_result.results
            ]

        sent, failed = await run_query(q.apply_send_results, send_results)
        counters = await run_query(
            q.update_campaign_counters, self._campaign.id, sent, failed
        )
        self._total_sent = counters["sent"]
        self._total_fail = counters["failed"]
        self._since_notify += sent + failed

        logger.debug(
            "Campaign %d  +sent=%d +failed=%d  total %d/%d",
            self._campaign.id, sent, failed,
            self._total_sent + self._total_fail, self._campaign.total_recipients,
        )

        if self._since_notify >= _NOTIFY_EVERY:
            self._since_notify = 0
            await webhook.notify_progress(
                self._campaign.id,
                self._total_sent,
                self._total_fail,
                self._campaign.total_recipients,
            )

    # ── helpers ───────────────────────────────────────────────────────────────

    async def _check_balance(self, batch_size: int) -> int:
        """
        Verify company has enough balance for at least 1 SMS.
        Returns the number of recipients to fetch this batch (may be < batch_size).
        Sets self._aborted = True and notifies Laravel if balance is exhausted.
        """
        company_id = self._campaign.company_id
        if not company_id:
            return batch_size  # super-admin: no balance check

        balance, price = await run_query(q.get_balance_info, company_id)
        can_send = int(balance // price) if price > 0 else 0

        if can_send == 0:
            logger.info(
                "Campaign %d auto-paused: insufficient balance (balance=%.4f price=%.4f)",
                self._campaign.id, balance, price,
            )
            await run_query(q.auto_pause_campaign, self._campaign.id)
            await webhook.notify_progress(
                self._campaign.id,
                self._total_sent,
                self._total_fail,
                self._campaign.total_recipients,
                paused_reason="no_balance",
            )
            self._aborted = True
            return 0

        return min(can_send, batch_size)

    async def _check_abort(self) -> bool:
        """
        Check DB for pause/cancel. On abort, set self._aborted and return True.
        Also blocks pending recipients if cancelled.
        """
        status = await run_query(q.get_campaign_status, self._campaign.id)

        if status == CAMPAIGN_PAUSED:
            logger.info("Campaign %d paused by user — stopping.", self._campaign.id)
            self._aborted = True
            return True

        if status == CAMPAIGN_CANCELLED:
            logger.info("Campaign %d cancelled — blocking pending recipients.", self._campaign.id)
            blocked = await run_query(q.block_pending_recipients, self._campaign.id)
            logger.info("Campaign %d: %d recipients blocked.", self._campaign.id, blocked)
            self._aborted = True
            return True

        return False

    async def _sleep_interruptible(self, total_seconds: int) -> bool:
        """
        Sleep for total_seconds, waking every _SLEEP_CHECK_SECS to check
        for pause/cancel. Returns True if aborted during sleep.
        """
        elapsed = 0
        while elapsed < total_seconds:
            chunk = min(_SLEEP_CHECK_SECS, total_seconds - elapsed)
            await asyncio.sleep(chunk)
            elapsed += chunk

            if await self._check_abort():
                return True

            # Re-evaluate: window may have shifted (DST, etc.)
            now = datetime.now()
            if not is_blocked(self._campaign.no_send_rules, now):
                return False  # window ended early

        return False
