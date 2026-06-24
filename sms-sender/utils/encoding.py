import math

# GSM 7-bit basic character set
_GSM7_BASIC = (
    "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1bÆæßÉ "
    "!\"#¤%&'()*+,-./"
    "0123456789:;<=>?"
    "¡ABCDEFGHIJKLMNOPQRSTUVWXYZ"
    "ÄÖÑÜ§¿"
    "abcdefghijklmnopqrstuvwxyz"
    "äöñüà"
)
# Extended characters count as 2 septets each
_GSM7_EXT = set("^{}\\[~]|€")

_GSM7_SET = set(_GSM7_BASIC)


def detect_encoding(text: str) -> tuple[str, int]:
    """
    Returns (encoding, segment_count).

    encoding: 'GSM7' | 'UCS2'
    segment_count: number of SMS credits this message consumes.
    """
    is_gsm = all(c in _GSM7_SET or c in _GSM7_EXT for c in text)

    if is_gsm:
        # Extended chars each take 2 septets
        length = sum(2 if c in _GSM7_EXT else 1 for c in text)
        if length <= 160:
            return "GSM7", 1
        return "GSM7", math.ceil(length / 153)
    else:
        length = len(text)
        if length <= 70:
            return "UCS2", 1
        return "UCS2", math.ceil(length / 67)
