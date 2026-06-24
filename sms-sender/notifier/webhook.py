"""
Calls the Laravel API to report campaign progress.
Laravel handles billing (on completion) and real-time broadcast via Reverb.

Auth: Bearer token — matches CampaignProgressController's expectation.
Route: POST /api/campaign/{id}/progress
Payload: {sent, failed, completed}
"""
from __future__ import annotations

import asyncio

import aiohttp

from config.settings import settings
from utils.logger import get_logger

logger = get_logger(__name__)

# C-02: retry delays (seconds) for completed=True webhooks only.
# Progress webhooks remain best-effort to avoid blocking sends.
_COMPLETED_RETRY_DELAYS = [5, 15, 30, 60, 120]

_session: aiohttp.ClientSession | None = None


def _get_session() -> aiohttp.ClientSession:
    global _session
    if _session is None or _session.closed:
        _session = aiohttp.ClientSession(
            base_url=settings.LARAVEL_BASE_URL,
            headers={"Authorization": f"Bearer {settings.LARAVEL_WEBHOOK_SECRET}"},
            timeout=aiohttp.ClientTimeout(total=15),
        )
    return _session


async def notify_progress(
    campaign_id: int,
    sent: int,
    failed: int,
    total: int,
    completed: bool = False,
    paused_reason: str | None = None,
) -> None:
    """
    Send progress update to Laravel.
    completed=True triggers billing; retried with backoff until Laravel confirms.
    paused_reason="no_balance" signals auto-pause due to insufficient balance.
    """
    payload: dict = {"sent": sent, "failed": failed, "completed": completed}
    if paused_reason:
        payload["paused_reason"] = paused_reason

    url = f"/api/campaign/{campaign_id}/progress"
    session = _get_session()
    max_attempts = len(_COMPLETED_RETRY_DELAYS) + 1 if completed else 1

    for attempt in range(max_attempts):
        try:
            async with session.post(url, json=payload) as resp:
                if resp.status in (200, 204):
                    if completed:
                        logger.info(
                            "Laravel notified: campaign %d completed "
                            "(sent=%d failed=%d total=%d attempt=%d)",
                            campaign_id, sent, failed, total, attempt + 1,
                        )
                    return
                body = await resp.text()
                logger.warning(
                    "Webhook %s HTTP %d (attempt %d/%d): %s",
                    url, resp.status, attempt + 1, max_attempts, body[:200],
                )
        except Exception as exc:
            logger.warning(
                "Webhook campaign %d failed (attempt %d/%d): %s",
                campaign_id, attempt + 1, max_attempts, exc,
            )

        if completed and attempt < max_attempts - 1:
            delay = _COMPLETED_RETRY_DELAYS[attempt]
            logger.info(
                "Retrying completed webhook for campaign %d in %ds...",
                campaign_id, delay,
            )
            await asyncio.sleep(delay)

    if completed:
        logger.error(
            "CRITICAL: Could not notify Laravel of campaign %d completion "
            "after %d attempts. Manual intervention required.",
            campaign_id, max_attempts,
        )


async def close() -> None:
    global _session
    if _session and not _session.closed:
        await _session.close()
