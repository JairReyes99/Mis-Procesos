"""
Japifone SMS provider — stub pending official API documentation.
Implement send_bulk() once credentials and endpoints are confirmed.
"""
from __future__ import annotations

from providers.base import BaseProvider, BulkResult, SmsMessage
from utils.logger import get_logger

logger = get_logger(__name__)


class JapifoneProvider(BaseProvider):
    BULK_SIZE = 100  # update once docs are available

    async def send_bulk(
        self,
        messages: list[SmsMessage],
        campaign_label: str,
    ) -> BulkResult:
        raise NotImplementedError(
            "JapifoneProvider is not yet implemented — waiting for API documentation."
        )
