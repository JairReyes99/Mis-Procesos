"""
Directo SMS provider.

API base: https://smsrp.directo.com/rest

Auth options (prefer token):
  - Bearer token: Authorization: Bearer <token>
  - User/pass:    ?username=X&password=Y in query string

Endpoints used:
  POST /bulk_send_sms?message_label=<label>
       Body: [{from, to, message}, ...]  (max 50 per call)
       Response: {sentCount, rejectedCount, startRow, endRow, totalCount}

  GET  /send_sms?from=X&to=Y&message=Z[&username=U&password=P]
       Response: {message_id, message_text}  (single segment)
                 [{dnis, message_id, segment_num, message_text}, ...]  (multi-seg)

Status codes:
  200 OK, 400 Bad Request, 401 Unauthorized, 402 Payment Required,
  406 Not Acceptable, 429 Too Many Requests, 500 Server Error
"""
from __future__ import annotations

import asyncio
import logging

import aiohttp

from config.settings import settings
from providers.base import BaseProvider, BulkResult, SmsMessage, SmsResult
from utils.logger import get_logger

logger = get_logger(__name__)

_RETRY_STATUSES = {429, 500, 502, 503, 504}
_MAX_RETRIES = 3
_RETRY_BASE_DELAY = 2.0   # seconds (doubles each attempt)


class DirectoProvider(BaseProvider):
    BULK_SIZE = 50

    def __init__(self) -> None:
        self._session: aiohttp.ClientSession | None = None

    # ── session management ────────────────────────────────────────────────────

    def _get_session(self) -> aiohttp.ClientSession:
        if self._session is None or self._session.closed:
            headers = {"Content-Type": "application/json"}
            kwargs: dict = {
                "base_url": settings.DIRECTO_BASE_URL,
                "headers": headers,
                "timeout": aiohttp.ClientTimeout(total=30, connect=10),
            }
            # C-12: credentials always go in headers, never in query string.
            if settings.DIRECTO_TOKEN:
                headers["Authorization"] = f"Bearer {settings.DIRECTO_TOKEN}"
            elif settings.DIRECTO_USERNAME:
                kwargs["auth"] = aiohttp.BasicAuth(
                    settings.DIRECTO_USERNAME,
                    settings.DIRECTO_PASSWORD,
                )
            self._session = aiohttp.ClientSession(**kwargs)
        return self._session

    async def close(self) -> None:
        if self._session and not self._session.closed:
            await self._session.close()

    # ── auth helpers ──────────────────────────────────────────────────────────

    def _auth_params(self) -> dict:
        # C-12: credentials are set on the session (header/BasicAuth), not query params.
        return {}

    # ── public interface ──────────────────────────────────────────────────────

    async def send_bulk(
        self,
        messages: list[SmsMessage],
        campaign_label: str,
    ) -> BulkResult:
        """
        Send a batch (≤50) via the bulk endpoint.
        Falls back to individual sends only when rejectedCount > 0.
        """
        if not messages:
            return BulkResult()

        payload = [
            {"from": m.sender, "to": m.phone, "message": m.message}
            for m in messages
        ]
        params = {"message_label": campaign_label, **self._auth_params()}

        try:
            data = await self._post_with_retry("/bulk_send_sms", params=params, json=payload)
        except Exception as exc:
            logger.error("Bulk send failed entirely for %d messages: %s", len(messages), exc)
            return BulkResult(results=[
                SmsResult(m.recipient_id, False, error=str(exc)) for m in messages
            ])

        sent_count: int = data.get("sentCount", 0)
        rejected_count: int = data.get("rejectedCount", 0)

        # Happy path — all delivered
        if rejected_count == 0:
            logger.debug("Bulk sent %d messages successfully", sent_count)
            return BulkResult(results=[
                SmsResult(m.recipient_id, True) for m in messages
            ])

        # Some rejected — fall back to individual sends to identify which ones
        logger.warning(
            "Bulk had %d rejections out of %d — retrying individually",
            rejected_count, len(messages),
        )
        results = await asyncio.gather(*[
            self._send_single(m, campaign_label) for m in messages
        ])
        return BulkResult(results=list(results))

    # ── internal helpers ──────────────────────────────────────────────────────

    async def _send_single(self, msg: SmsMessage, label: str) -> SmsResult:
        params = {
            "from": msg.sender,
            "to": msg.phone,
            "message": msg.message,
            "message_label": label,
            **self._auth_params(),
        }
        try:
            data = await self._get_with_retry("/send_sms", params=params)
            # Single-segment response: {message_id, message_text}
            # Multi-segment response:  [{dnis, message_id, segment_num, ...}, ...]
            if isinstance(data, list):
                mid = data[0].get("message_id") if data else None
            else:
                mid = data.get("message_id")
            return SmsResult(msg.recipient_id, True, provider_message_id=mid)
        except Exception as exc:
            logger.warning("Individual send failed for recipient %d: %s", msg.recipient_id, exc)
            return SmsResult(msg.recipient_id, False, error=str(exc))

    async def _post_with_retry(self, path: str, **kwargs) -> dict:
        return await self._request_with_retry("POST", path, **kwargs)

    async def _get_with_retry(self, path: str, **kwargs) -> dict | list:
        return await self._request_with_retry("GET", path, **kwargs)

    async def _request_with_retry(self, method: str, path: str, **kwargs):
        session = self._get_session()
        delay = _RETRY_BASE_DELAY
        last_exc: Exception | None = None

        for attempt in range(1, _MAX_RETRIES + 1):
            try:
                async with session.request(method, path, **kwargs) as resp:
                    if resp.status in _RETRY_STATUSES:
                        body = await resp.text()
                        raise _RetryableError(f"HTTP {resp.status}: {body[:200]}")

                    if resp.status == 401:
                        body = await resp.json(content_type=None)
                        raise PermissionError(f"Auth failed: {body}")

                    if resp.status == 402:
                        raise InsufficientBalanceError("Directo: insufficient balance")

                    resp.raise_for_status()
                    return await resp.json(content_type=None)

            except (aiohttp.ClientConnectionError, asyncio.TimeoutError, _RetryableError) as exc:
                last_exc = exc
                if attempt < _MAX_RETRIES:
                    logger.warning(
                        "Directo %s %s attempt %d/%d failed (%s) — retrying in %.1fs",
                        method, path, attempt, _MAX_RETRIES, exc, delay,
                    )
                    await asyncio.sleep(delay)
                    delay *= 2

        raise last_exc or RuntimeError("Request failed after retries")


class _RetryableError(Exception):
    pass


class InsufficientBalanceError(Exception):
    pass
