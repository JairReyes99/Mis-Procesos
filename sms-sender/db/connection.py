"""
Thread-safe pyodbc connection pool for SQL Server.
All blocking DB calls should be wrapped with asyncio.to_thread().
"""
import asyncio
import queue
import threading
from contextlib import contextmanager

import pyodbc

from config.settings import settings
from utils.logger import get_logger

logger = get_logger(__name__)


def _build_conn_string() -> str:
    parts = [
        "DRIVER={ODBC Driver 17 for SQL Server}",
        f"SERVER={settings.DB_SERVER}",
        f"DATABASE={settings.DB_NAME}",
    ]
    if settings.DB_TRUSTED:
        parts.append("Trusted_Connection=yes")
    else:
        parts.append(f"UID={settings.DB_USER}")
        parts.append(f"PWD={settings.DB_PASSWORD}")
    # Fast fail on network issues
    parts.append("ConnectRetryCount=2")
    parts.append("ConnectRetryInterval=5")
    return ";".join(parts)


class ConnectionPool:
    def __init__(self, size: int = 5) -> None:
        self._pool: queue.Queue[pyodbc.Connection] = queue.Queue(maxsize=size)
        self._lock = threading.Lock()
        self._conn_string = _build_conn_string()
        self._size = size
        self._created = 0

    def _make_connection(self) -> pyodbc.Connection:
        conn = pyodbc.connect(self._conn_string, autocommit=False)
        return conn

    @contextmanager
    def get(self):
        conn = self._acquire()
        try:
            yield conn
        except Exception:
            # On error try to rollback and discard the connection
            try:
                conn.rollback()
            except Exception:
                pass
            try:
                conn.close()
            except Exception:
                pass
            with self._lock:
                self._created -= 1
            conn = None
            raise
        finally:
            if conn is not None:
                try:
                    self._pool.put_nowait(conn)
                except queue.Full:
                    conn.close()
                    with self._lock:
                        self._created -= 1

    def _acquire(self) -> pyodbc.Connection:
        try:
            return self._pool.get_nowait()
        except queue.Empty:
            pass

        # C-07: reserve slot INSIDE lock, create connection OUTSIDE lock so
        # slow pyodbc.connect() does not block other threads from checking.
        should_create = False
        with self._lock:
            if self._created < self._size:
                self._created += 1
                should_create = True

        if should_create:
            try:
                return self._make_connection()
            except Exception:
                with self._lock:
                    self._created -= 1
                raise

        # Pool exhausted — block until a connection is returned
        try:
            return self._pool.get(timeout=30)
        except queue.Empty:
            raise TimeoutError(
                f"DB pool exhausted after 30s (pool_size={self._size}). "
                "Increase DB_POOL_SIZE in .env."
            )

    async def aget(self):
        """Async context manager wrapper."""
        return self.get()

    def close_all(self) -> None:
        while not self._pool.empty():
            try:
                self._pool.get_nowait().close()
            except Exception:
                pass


# Singleton pool, initialised lazily
_pool: ConnectionPool | None = None


def get_pool() -> ConnectionPool:
    global _pool
    if _pool is None:
        _pool = ConnectionPool(size=settings.DB_POOL_SIZE)
        logger.info("DB pool initialised (size=%d, server=%s, db=%s)",
                    settings.DB_POOL_SIZE, settings.DB_SERVER, settings.DB_NAME)
    return _pool


async def run_query(fn, *args, **kwargs):
    """Run a blocking DB function in a thread pool."""
    return await asyncio.to_thread(fn, *args, **kwargs)
