"""
Main worker loop.

- Polls the DB every WORKER_POLL_INTERVAL seconds for scheduled campaigns.
- Runs up to WORKER_MAX_CAMPAIGNS campaigns concurrently.
- Shares a single HTTP semaphore across all active campaigns to cap
  outbound connections regardless of how many campaigns are in flight.
- Handles SIGTERM / SIGINT gracefully (finishes current chunks, then exits).
"""
from __future__ import annotations

import asyncio
import signal
import uuid

from config.settings import settings
from core.campaign_processor import CampaignProcessor
from db import queries as q
from db.connection import run_query
from notifier import webhook as notifier_webhook
from providers.router import close_all as close_providers
from utils.logger import configure_root, get_logger

logger = get_logger(__name__)

# C-01: unique ID for this worker instance, used to claim campaigns atomically.
# Short enough to fit in campaigns.worker_id (varchar 16).
WORKER_ID = str(uuid.uuid4())[:16]


class Worker:
    def __init__(self) -> None:
        self._http_sem = asyncio.Semaphore(settings.WORKER_HTTP_CONCURRENCY)
        self._campaign_sem = asyncio.Semaphore(settings.WORKER_MAX_CAMPAIGNS)
        self._active: set[int] = set()   # campaign IDs currently in flight
        self._shutdown = asyncio.Event()

    # ── lifecycle ─────────────────────────────────────────────────────────────

    async def start(self) -> None:
        configure_root(settings.LOG_LEVEL)
        self._install_signal_handlers()

        logger.info(
            "SMS sender worker started "
            "(id=%s max_campaigns=%d, http_concurrency=%d, poll_interval=%ds)",
            WORKER_ID,
            settings.WORKER_MAX_CAMPAIGNS,
            settings.WORKER_HTTP_CONCURRENCY,
            settings.WORKER_POLL_INTERVAL,
        )

        await self._poll_loop()
        await self._teardown()

    async def _teardown(self) -> None:
        logger.info("Shutting down — waiting for active campaigns to finish…")
        # _campaign_sem tracks slots; acquiring all slots == all campaigns done
        for _ in range(settings.WORKER_MAX_CAMPAIGNS):
            await self._campaign_sem.acquire()
        await close_providers()
        await notifier_webhook.close()
        logger.info("Worker stopped cleanly.")

    def _install_signal_handlers(self) -> None:
        loop = asyncio.get_running_loop()
        for sig in (signal.SIGTERM, signal.SIGINT):
            try:
                loop.add_signal_handler(sig, self._shutdown.set)
            except NotImplementedError:
                # Windows — use a KeyboardInterrupt handler instead
                pass

    # ── poll loop ─────────────────────────────────────────────────────────────

    async def _poll_loop(self) -> None:
        while not self._shutdown.is_set():
            await self._dispatch_pending()

            try:
                await asyncio.wait_for(
                    self._shutdown.wait(),
                    timeout=settings.WORKER_POLL_INTERVAL,
                )
            except asyncio.TimeoutError:
                pass

    async def _dispatch_pending(self) -> None:
        campaigns = await run_query(q.fetch_pending_campaigns)
        if not campaigns:
            return

        for campaign in campaigns:
            if self._shutdown.is_set():
                break
            if campaign.id in self._active:
                continue

            # Try to acquire a campaign slot without blocking the poll loop
            if self._campaign_sem._value == 0:
                logger.debug("All campaign slots busy, deferring remaining campaigns")
                break

            self._active.add(campaign.id)
            await self._campaign_sem.acquire()
            asyncio.ensure_future(self._run_campaign(campaign, WORKER_ID))

    async def _run_campaign(self, campaign, worker_id: str) -> None:
        try:
            processor = CampaignProcessor(campaign, self._http_sem, worker_id)
            await processor.run()
        except Exception:
            logger.exception("Unexpected error in campaign %d", campaign.id)
        finally:
            self._active.discard(campaign.id)
            self._campaign_sem.release()
