<aside class="side" id="sidebar">
    <div class="side-brand">
        <div class="brand">
            <div class="brand-mark">
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                    <rect x="1" y="1" width="30" height="30" rx="8" fill="var(--ink)" />
                    <path d="M10 20c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="var(--accent)" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M7 20c0-5 4-9 9-9s9 4 9 9" stroke="var(--accent)" stroke-width="1.8" stroke-linecap="round" opacity="0.55" />
                    <circle cx="16" cy="20" r="2" fill="var(--accent)" />
                </svg>
            </div>
            <div class="brand-text side-brand-text">
                <div class="brand-name">SMS<span class="brand-dot">·</span>Intelix</div>
                <div class="brand-sub">Grupo Concentra</div>
            </div>
        </div>
        <button class="side-collapse" id="sidebar-toggle" title="Colapsar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </button>
    </div>

    <div class="side-search side-brand-text" onclick="openSearch()" role="button" aria-label="Abrir búsqueda">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <span>Buscar…</span>
        <kbd id="search-kbd"></kbd>
    </div>

    {{-- Modal de búsqueda (Fix 2) --}}
    <div id="search-modal" style="display:none" class="modal-bg" onclick="if(event.target===this)closeSearch()">
        <div class="modal" style="max-width:480px">
            <div class="modal-h" style="gap:8px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;color:var(--ink-3)"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input id="search-input" type="text" placeholder="Buscar módulo, página o acción…" autocomplete="off"
                    style="border:none;box-shadow:none;padding:0;font-size:15px;flex:1;background:transparent;outline:none;color:var(--ink)"/>
                <kbd style="font-family:var(--font-mono);font-size:10px;padding:2px 5px;background:var(--bg-muted);border:1px solid var(--line);border-radius:4px;color:var(--ink-3);flex-shrink:0">Esc</kbd>
            </div>
            <div id="search-results" class="modal-b" style="min-height:80px;display:flex;align-items:center;justify-content:center;color:var(--ink-4);font-size:13px">
                Escribe para buscar…
            </div>
        </div>
    </div>

    <nav class="side-nav">
        <a href="{{ route('home') }}" class="side-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <svg class="side-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Inicio</span>
        </a>
    </nav>

    @if(isset($asideMenus))
        @foreach($asideMenus as $menu)
            <div class="side-section">{{ $menu->name }}</div>
            <nav class="side-nav">
                @if($menu->subMenus->isEmpty())
                    <a href="{{ Route::has($menu->route) ? route($menu->route) : '#' }}" class="side-item {{ request()->routeIs($menu->route.'*') ? 'active' : '' }}">
                        <i class="{{ $menu->icon }} side-ico"></i>
                        <span>{{ $menu->name }}</span>
                    </a>
                @else
                    @php $isOpen = $menu->subMenus->contains(fn($sub) => request()->routeIs($sub->route)); @endphp
                    <button class="side-item {{ $isOpen ? 'active' : '' }}" onclick="toggleSubMenu('submenu-{{ $menu->id }}')">
                        <i class="{{ $menu->icon }} side-ico"></i>
                        <span>{{ $menu->name }}</span>
                        <svg class="side-ico" style="margin-left:auto" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div id="submenu-{{ $menu->id }}" style="{{ $isOpen ? 'display:block' : 'display:none' }}">
                        @foreach($menu->subMenus as $subMenu)
                            <a href="{{ Route::has($subMenu->route) ? route($subMenu->route) : '#' }}" class="side-item side-item-sub {{ request()->routeIs($subMenu->route.'*') ? 'active' : '' }}">
                                <span>{{ $subMenu->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </nav>
        @endforeach
    @endif

    <div class="side-foot">
        <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(strstr(Auth::user()->name ?? ' ', ' '), 1, 1)) }}</div>
        <div class="side-foot-meta side-brand-text">
            <b>{{ Auth::user()->name ?? '' }}</b>
            <span>{{ Auth::user()->activeRole?->name ?? (Auth::user()->roles->first()?->name ?? 'Sin rol') }}</span>
        </div>
    </div>
</aside>
