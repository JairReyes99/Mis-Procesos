"""
no_send_rules evaluator.

Rules format (from campaigns.no_send_rules JSON):
  [{"from": "21:00", "to": "07:00"}, {"from": "13:00", "to": "14:00"}]

Times are in 24-hour HH:MM format, server-local timezone.
Ranges are [from, to) — inclusive start, exclusive end.
A range where from > to crosses midnight (e.g. 21:00 → 07:00).
"""
from __future__ import annotations

from datetime import datetime, time, timedelta


# ── helpers ───────────────────────────────────────────────────────────────────

def _parse(s: str) -> time:
    h, m = s.strip().split(":")
    return time(int(h), int(m), 0)


def _in_range(t: time, from_t: time, to_t: time) -> bool:
    """
    Returns True if t falls in [from_t, to_t).
    Handles midnight-crossing ranges (from_t > to_t).
    """
    if from_t < to_t:
        # Same-day range: e.g. 13:00 – 14:00
        return from_t <= t < to_t
    else:
        # Midnight-crossing range: e.g. 21:00 – 07:00
        # Blocked when t >= 21:00 OR t < 07:00
        return t >= from_t or t < to_t


def _parse_rules(rules: list[dict]) -> list[tuple[time, time]]:
    result = []
    for r in rules:
        try:
            result.append((_parse(r["from"]), _parse(r["to"])))
        except (KeyError, ValueError):
            pass
    return result


# ── public API ────────────────────────────────────────────────────────────────

def is_blocked(rules: list[dict], now: datetime) -> bool:
    """Return True if the current moment falls inside any no-send window."""
    if not rules:
        return False
    t = now.time().replace(second=0, microsecond=0)
    for from_t, to_t in _parse_rules(rules):
        if _in_range(t, from_t, to_t):
            return True
    return False


def seconds_until_clear(rules: list[dict], now: datetime) -> int:
    """
    Return seconds until we exit ALL overlapping blocked windows.

    Algorithm: jump forward to the end of each blocking window in turn until
    we land on a free slot. Handles chains like:
      rule A: 20:00 – 23:00
      rule B: 22:00 – 06:00
    → Effectively blocked 20:00 – 06:00; correctly returns time to 06:00.

    Returns 0 if not currently blocked.
    Max look-ahead: 48 h (safety guard against pathological rule sets).
    """
    parsed = _parse_rules(rules)
    if not parsed:
        return 0

    check = now.replace(second=0, microsecond=0)
    deadline = now + timedelta(hours=48)

    while check < deadline:
        t = check.time()
        blocking: tuple[time, time] | None = None

        for from_t, to_t in parsed:
            if _in_range(t, from_t, to_t):
                blocking = (from_t, to_t)
                break

        if blocking is None:
            # Found a clear moment
            return max(0, int((check - now).total_seconds()))

        # Jump to 1 second past the end of this window
        _, to_t = blocking
        candidate = check.replace(hour=to_t.hour, minute=to_t.minute, second=1, microsecond=0)
        if candidate <= check:
            # to_t is earlier in the day (midnight-crossing window) → tomorrow
            candidate += timedelta(days=1)
        check = candidate

    # Pathological rules block everything for 48 h — return max wait
    return int((deadline - now).total_seconds())
