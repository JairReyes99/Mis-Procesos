<header class="top">
    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()" aria-label="Abrir menú" title="Abrir menú">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="crumbs">
        @hasSection('breadcrumb')
            @yield('breadcrumb')
        @else
            <b>@yield('title', 'Inicio')</b>
        @endif
    </div>
    <div class="top-actions">
        @if(isset($currentCompany) && $currentCompany)
        <div class="company-stats">
            <div class="company-stat">
                <span class="company-stat-label">Empresa</span>
                <span class="company-stat-value">{{ $currentCompany->name }}</span>
            </div>
            <div class="company-stat-divider"></div>
            <div class="company-stat">
                <span class="company-stat-label">Saldo disponible</span>
                <span class="company-stat-value company-stat-balance">${{ number_format($currentCompany->balance, 2) }} MXN</span>
            </div>
        </div>
        @endif
        <div class="user-chip" id="user-chip" onclick="toggleUserMenu(event)">
            <span class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(strstr(Auth::user()->name ?? ' ', ' '), 1, 1)) }}</span>
            <span class="user-chip-meta">
                <b>{{ Auth::user()->name ?? 'Usuario' }}</b>
                <span>{{ Auth::user()->activeRole?->name ?? (Auth::user()->roles->first()?->name ?? 'Sin rol') }}</span>
            </span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            <div class="user-chip-dropdown" id="user-dropdown" style="display:none" onclick="event.stopPropagation()">
                <a href="{{ route('profile.index') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Mi perfil
                </a>
                @if(isset($currentCompany) && $currentCompany)
                <a href="#">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    {{ $currentCompany->name }}
                </a>
                @endif
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
