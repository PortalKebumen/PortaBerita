document.addEventListener('DOMContentLoaded', function () {
    // ---------- Keyboard shortcut: Cmd/Ctrl + K fokus ke search ----------
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            var input = document.getElementById('global-search');
            if (input) input.focus();
        }
    });

    // ---------- Sidebar collapse (desktop) ----------
    var sidebar = document.getElementById('app-sidebar');
    var collapseBtn = document.querySelector('[data-sidebar-collapse]');
    if (sidebar && collapseBtn) {
        var collapseIcon = collapseBtn.querySelector('[data-collapse-icon]');
        var sidebarCollapsed = false; // status "resmi" tersimpan, terpisah dari tampilan sementara saat hover

        function applyVisual(collapsed) {
            sidebar.classList.toggle('w-20', collapsed);
            sidebar.classList.toggle('w-64', !collapsed);
            sidebar.querySelectorAll('.sidebar-label').forEach(function (el) {
                el.classList.toggle('hidden', collapsed);
            });
            sidebar.querySelectorAll('.sidebar-collapsed-only').forEach(function (el) {
                el.classList.toggle('hidden', !collapsed);
            });
            sidebar.querySelectorAll('.sidebar-nav-item').forEach(function (el) {
                el.classList.toggle('justify-center', collapsed);
                el.classList.toggle('px-0', collapsed);
            });
            if (collapseIcon) collapseIcon.classList.toggle('rotate-180', collapsed);
        }

        function setCollapsed(collapsed) {
            sidebarCollapsed = collapsed;
            applyVisual(collapsed);
            try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        }

        collapseBtn.addEventListener('click', function () {
            setCollapsed(!sidebarCollapsed);
        });

        // Hover untuk melebar sementara saat dalam keadaan ciut
        sidebar.addEventListener('mouseenter', function () {
            if (sidebarCollapsed) applyVisual(false);
        });
        sidebar.addEventListener('mouseleave', function () {
            if (sidebarCollapsed) applyVisual(true);
        });

        var savedState = null;
        try { savedState = localStorage.getItem('sidebarCollapsed'); } catch (e) {}
        if (savedState === '1' && window.innerWidth >= 1024) {
            setCollapsed(true);
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth < 1024 && sidebarCollapsed) {
                setCollapsed(false);
            }
        });
    }

    // ---------- Modal ----------
    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = document.getElementById(btn.getAttribute('data-modal-open'));
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal(overlay);
        });
        overlay.querySelectorAll('[data-modal-close]').forEach(function (btn) {
            btn.addEventListener('click', function () { closeModal(overlay); });
        });
    });
    function closeModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.flex').forEach(closeModal);
        }
    });

    // ---------- Tabs ----------
    document.querySelectorAll('[data-tabs]').forEach(function (tabGroup) {
        var buttons = tabGroup.querySelectorAll('[data-tab-btn]');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-tab-btn');
                buttons.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                var container = tabGroup.parentElement;
                container.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== target);
                });
            });
        });
    });

    // ---------- Dropdown ----------
    document.querySelectorAll('[data-dropdown]').forEach(function (dd) {
        var btn = dd.querySelector('[data-dropdown-btn]');
        var menu = dd.querySelector('[data-dropdown-menu]');
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });
        menu.querySelectorAll('.dropdown-item').forEach(function (item) {
            item.addEventListener('click', function () { menu.classList.add('hidden'); });
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) { m.classList.add('hidden'); });
    });
});