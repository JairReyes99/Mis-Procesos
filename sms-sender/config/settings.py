from pydantic import field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

_KNOWN_WEAK_SECRETS = {"", "secret_sms_intelix_2026", "changeme", "secret", "test"}


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    # ── Database (SQL Server) ─────────────────────────────────────────────────
    DB_SERVER: str = "localhost"
    DB_NAME: str = "sms_intelix"
    DB_USER: str = ""
    DB_PASSWORD: str = ""
    # Set True to use Windows Authentication (ignores DB_USER/DB_PASSWORD)
    DB_TRUSTED: bool = False
    # C-07: min safe = WORKER_MAX_CAMPAIGNS * (HTTP_chunks_per_batch + 3)
    DB_POOL_SIZE: int = 20

    # ── Worker behaviour ──────────────────────────────────────────────────────
    WORKER_POLL_INTERVAL: int = 5
    WORKER_MAX_CAMPAIGNS: int = 3
    WORKER_HTTP_CONCURRENCY: int = 20
    WORKER_DB_BATCH_SIZE: int = 500
    # Seconds before a campaign with a stale worker_id can be reclaimed
    WORKER_STALE_MINUTES: int = 5

    # ── Laravel webhook ───────────────────────────────────────────────────────
    LARAVEL_BASE_URL: str = "http://sms-intelix.test"
    # C-04: No default — worker refuses to start without an explicit secret
    LARAVEL_WEBHOOK_SECRET: str

    # ── Directo SMS ───────────────────────────────────────────────────────────
    DIRECTO_BASE_URL: str = "https://smsrp.directo.com/rest"
    DIRECTO_TOKEN: str = ""
    DIRECTO_USERNAME: str = ""
    DIRECTO_PASSWORD: str = ""

    # ── Japifone ──────────────────────────────────────────────────────────────
    JAPIFONE_BASE_URL: str = ""
    JAPIFONE_API_KEY: str = ""

    # ── Logging ───────────────────────────────────────────────────────────────
    LOG_LEVEL: str = "INFO"

    @field_validator("LARAVEL_WEBHOOK_SECRET")
    @classmethod
    def secret_must_be_strong(cls, v: str) -> str:
        if v in _KNOWN_WEAK_SECRETS or len(v) < 32:
            raise ValueError(
                "LARAVEL_WEBHOOK_SECRET must be a random value of at least 32 characters. "
                "Generate with: python -c \"import secrets; print(secrets.token_hex(32))\""
            )
        return v


settings = Settings()
