import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;
window.Alpine = Alpine;
Alpine.start();

// Sidebar toggle
const appShell = document.getElementById('app-shell');
const _isMac = /Mac|iPhone|iPad|iPod/.test(navigator.userAgent);

window.toggleSidebar = function() {
    const collapsed = appShell?.dataset.side === 'collapsed';
    if (appShell) appShell.dataset.side = collapsed ? 'expanded' : 'collapsed';
    localStorage.setItem('sidebar', collapsed ? 'expanded' : 'collapsed');
    // Fix 3: update tooltip to reflect new state
    const btn = document.getElementById('sidebar-toggle');
    if (btn) btn.title = collapsed ? 'Colapsar' : 'Expandir';
};

// Mobile sidebar toggle (Fix 1)
window.toggleMobileSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    const isOpen = sidebar?.classList.contains('mobile-open');
    sidebar?.classList.toggle('mobile-open', !isOpen);
    overlay?.classList.toggle('active', !isOpen);
};

// User dropdown
window.toggleUserMenu = function(e) {
    const dd = document.getElementById('user-dropdown');
    if (!dd) return;
    const isOpen = dd.style.display !== 'none';
    dd.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) {
        setTimeout(() => {
            document.addEventListener('click', function closeDD() {
                dd.style.display = 'none';
                document.removeEventListener('click', closeDD);
            });
        }, 0);
    }
};

// Submenu toggle (Fix 5: preserve parent active state when collapsed on a child page)
window.toggleSubMenu = function(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const isHiding = el.style.display !== 'none';
    el.style.display = isHiding ? 'none' : 'block';
    if (isHiding) {
        const parentBtn = el.previousElementSibling;
        if (parentBtn && el.querySelector('.side-item.active')) {
            parentBtn.classList.add('active');
        }
    }
};

// Search (Fix 2)
window.openSearch = function() {
    const modal = document.getElementById('search-modal');
    if (!modal) return;
    modal.style.display = 'grid';
    document.getElementById('search-input')?.focus();
};
window.closeSearch = function() {
    const modal = document.getElementById('search-modal');
    if (modal) modal.style.display = 'none';
    const input = document.getElementById('search-input');
    if (input) { input.value = ''; }
    const results = document.getElementById('search-results');
    if (results) results.textContent = 'Escribe para buscar…';
};

document.addEventListener('keydown', (e) => {
    if ((_isMac ? e.metaKey : e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        window.openSearch();
    }
    if (e.key === 'Escape') window.closeSearch();
});

// Restore sidebar state
document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('sidebar');
    if (saved && appShell) appShell.dataset.side = saved;

    // Fix 3: set initial tooltip based on saved state
    const toggleBtn = document.getElementById('sidebar-toggle');
    if (toggleBtn && saved === 'collapsed') toggleBtn.title = 'Expandir';

    // Fix 4: set platform-correct shortcut label
    const kbdEl = document.getElementById('search-kbd');
    if (kbdEl) kbdEl.textContent = _isMac ? '⌘K' : 'Ctrl+K';

    // Sidebar toggle button
    document.getElementById('sidebar-toggle')?.addEventListener('click', window.toggleSidebar);

    // Mobile overlay close (Fix 1)
    document.getElementById('mobile-overlay')?.addEventListener('click', () => {
        document.getElementById('sidebar')?.classList.remove('mobile-open');
        document.getElementById('mobile-overlay')?.classList.remove('active');
    });

    // Bootstrap modal shim for jQuery .modal('show') / .modal('hide')
    if (typeof window.jQuery !== 'undefined') {
        (function ($) {
            $.fn.modal = function (action) {
                return this.each(function () {
                    const el = this;
                    if (action === 'show') {
                        el.style.display = 'flex';
                        el.removeAttribute('hidden');
                        document.body.style.overflow = 'hidden';
                        el._modalBackdropHandler = function (e) {
                            if (e.target === el) $(el).modal('hide');
                        };
                        el.addEventListener('click', el._modalBackdropHandler);
                        el.querySelectorAll('[data-dismiss="modal"]').forEach(function (btn) {
                            btn._dismissHandler = function () { $(el).modal('hide'); };
                            btn.addEventListener('click', btn._dismissHandler);
                        });
                    } else if (action === 'hide') {
                        el.style.display = 'none';
                        document.body.style.overflow = '';
                        if (el._modalBackdropHandler) {
                            el.removeEventListener('click', el._modalBackdropHandler);
                        }
                    }
                });
            };
        })(window.jQuery);
    }

    // Collapse shim for jQuery [data-toggle="collapse"]
    if (typeof window.jQuery !== 'undefined') {
        (function ($) {
            $(document).on('click', '[data-toggle="collapse"]', function () {
                var target = $(this).data('target');
                if (target) $(target).slideToggle(200);
            });
        })(window.jQuery);
    }

    window.APP = window.APP || {};
});
