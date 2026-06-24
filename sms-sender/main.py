"""
Entry point.

Usage:
    python main.py

Environment variables are loaded from .env automatically.
"""
import asyncio
import sys

from core.worker import Worker


def main() -> None:
    try:
        asyncio.run(Worker().start())
    except KeyboardInterrupt:
        pass
    sys.exit(0)


if __name__ == "__main__":
    main()
