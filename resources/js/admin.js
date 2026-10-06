import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.flatpickr = flatpickr;

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
    // Livewire mengganti markup modal setiap render; semua handler diikat lewat
    // delegasi di document agar tetap berlaku untuk node yang baru dibuat.
    window.pkModal = {
        open: function (id) {
            var modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        },
        close: function (id) {
            var modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    };
    document.addEventListener('click', function (e) {
        if (!(e.target instanceof Element)) return;

        var opener = e.target.closest('[data-modal-open]');
        if (opener) {
            var modal = document.getElementById(opener.getAttribute('data-modal-open'));
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            return;
        }

        var closer = e.target.closest('[data-modal-close]');
        if (closer) {
            var target = closer.closest('.modal-overlay');
            if (target) closeModal(target);
            return;
        }

        if (e.target.classList.contains('modal-overlay')) closeModal(e.target);
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

  
});