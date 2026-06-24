from __future__ import annotations

from abc import ABC, abstractmethod
from dataclasses import dataclass, field


@dataclass
class SmsMessage:
    recipient_id: int
    phone: str          # E.164 format, e.g. 5215512345678
    message: str
    sender: str         # From-name / short-code (max 16 alphanum)


@dataclass
class SmsResult:
    recipient_id: int
    success: bool
    provider_message_id: str | None = None
    error: str | None = None


@dataclass
class BulkResult:
    results: list[SmsResult] = field(default_factory=list)

    @property
    def sent(self) -> int:
        return sum(1 for r in self.results if r.success)

    @property
    def failed(self) -> int:
        return sum(1 for r in self.results if not r.success)


class BaseProvider(ABC):
    """
    Abstract SMS provider. Implement send_bulk() for each carrier.
    BULK_SIZE controls how many messages are sent per API call.
    """
    BULK_SIZE: int = 50

    @abstractmethod
    async def send_bulk(
        self,
        messages: list[SmsMessage],
        campaign_label: str,
    ) -> BulkResult:
        """Send up to BULK_SIZE messages. Must handle retries internally."""
        ...

    async def close(self) -> None:
        """Clean up resources (e.g. aiohttp session). Override if needed."""
