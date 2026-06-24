"""
Maps a provider slug to its implementation class.
Add new providers here as they are implemented.
"""
from __future__ import annotations

from providers.base import BaseProvider
from providers.directo.client import DirectoProvider
from providers.japifone.client import JapifoneProvider

_REGISTRY: dict[str, type[BaseProvider]] = {
    "directo": DirectoProvider,
    "japifone": JapifoneProvider,
}

# One instance per provider per process (they hold an aiohttp session)
_instances: dict[str, BaseProvider] = {}


def get_provider(slug: str) -> BaseProvider:
    slug = slug.lower()
    if slug not in _REGISTRY:
        raise ValueError(
            f"Unknown SMS provider '{slug}'. "
            f"Available: {list(_REGISTRY.keys())}"
        )
    if slug not in _instances:
        _instances[slug] = _REGISTRY[slug]()
    return _instances[slug]


async def close_all() -> None:
    for instance in _instances.values():
        await instance.close()
    _instances.clear()
