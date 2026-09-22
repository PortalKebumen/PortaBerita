document.addEventListener('DOMContentLoaded', () => {
    initDropdowns();
});

function initDropdowns() {
    document.querySelectorAll('[data-dropdown]').forEach((wrapper) => {
        const btn = wrapper.querySelector('[data-dropdown-btn]');
        const menu = wrapper.querySelector('[data-dropdown-menu]');
        if (!btn || !menu) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !menu.classList.contains('hidden');
            document.querySelectorAll('[data-dropdown-menu]').forEach((m) => m.classList.add('hidden'));
            menu.classList.toggle('hidden', isOpen);
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown-menu]').forEach((m) => m.classList.add('hidden'));
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-dropdown-menu]').forEach((m) => m.classList.add('hidden'));
        }
    });
}