@php
    $u = auth()->user();

    $paletteCommands = collect([
        ['group' => 'Navigasi', 'label' => 'Dashboard', 'url' => route('admin.dashboard'), 'icon' => 'nav', 'keywords' => 'beranda home ringkasan', 'can' => 'dashboard.view'],
        ['group' => 'Navigasi', 'label' => 'Artikel', 'url' => route('admin.artikel.index'), 'icon' => 'nav', 'keywords' => 'berita news post tulisan', 'can' => 'articles.view'],
        ['group' => 'Navigasi', 'label' => 'Kategori & Tag', 'url' => route('admin.kategori-tag.index'), 'icon' => 'nav', 'keywords' => 'category tag label', 'can' => 'categories.view'],
        ['group' => 'Navigasi', 'label' => 'Media Library', 'url' => route('admin.media-library.index'), 'icon' => 'nav', 'keywords' => 'gambar foto upload pustaka media', 'can' => 'media.view'],
        ['group' => 'Navigasi', 'label' => 'Iklan', 'url' => route('admin.iklan.index'), 'icon' => 'nav', 'keywords' => 'banner advertisement monetisasi', 'can' => 'ads.view'],
        ['group' => 'Navigasi', 'label' => 'Pengguna & Role', 'url' => route('admin.pengguna-role.index'), 'icon' => 'nav', 'keywords' => 'user akun izin permission', 'can' => 'users.view'],
        ['group' => 'Navigasi', 'label' => 'Activity Log', 'url' => route('admin.activity-log.index'), 'icon' => 'nav', 'keywords' => 'log riwayat audit aktivitas', 'can' => 'activity-log.view'],
        ['group' => 'Navigasi', 'label' => 'Laporan Analitik', 'url' => route('admin.analitik.index'), 'icon' => 'nav', 'keywords' => 'analitik laporan statistik tayangan views kategori penulis iklan ctr impresi klik', 'can' => 'view-analytics'],
        ['group' => 'Navigasi', 'label' => 'Halaman', 'url' => route('admin.halaman.index'), 'icon' => 'nav', 'keywords' => 'page halaman statis tentang kami pedoman redaksi', 'can' => 'pages.view'],
        ['group' => 'Navigasi', 'label' => 'Menu', 'url' => route('admin.menu.index'), 'icon' => 'nav', 'keywords' => 'navbar footer navigasi tautan link menu', 'can' => 'menus.view'],
        ['group' => 'Navigasi', 'label' => 'Pengaturan', 'url' => route('admin.pengaturan.index'), 'icon' => 'nav', 'keywords' => 'setting logo seo sitemap sosial', 'can' => 'settings.view'],

        ['group' => 'Aksi Cepat', 'label' => 'Tulis artikel baru', 'url' => route('admin.artikel.create'), 'icon' => 'plus', 'keywords' => 'tambah buat create new', 'can' => 'articles.create'],
        ['group' => 'Aksi Cepat', 'label' => 'Buat halaman baru', 'url' => route('admin.halaman.index', ['mode' => 'create']), 'icon' => 'plus', 'keywords' => 'tambah page halaman statis create new', 'can' => 'pages.create'],
        ['group' => 'Aksi Cepat', 'label' => 'Lihat antrean review', 'url' => route('admin.artikel.index', ['status' => 'submitted']), 'icon' => 'doc', 'keywords' => 'pending submitted redaktur setujui', 'can' => 'articles.approve'],

        ['group' => 'Akun', 'label' => 'Keluar', 'action' => 'logout', 'icon' => 'logout', 'keywords' => 'logout sign out'],
    ])
        ->filter(fn ($c) => ! isset($c['can']) || $u->can($c['can']))
        ->map(fn ($c) => \Illuminate\Support\Arr::except($c, ['can']))
        ->values();

    $searchUrl = $u->can('articles.view') ? route('admin.pencarian') : null;
    $articlesUrl = $u->can('articles.view') ? route('admin.artikel.index') : null;
@endphp

<div x-data="commandPalette(@js($paletteCommands), @js($searchUrl), @js($articlesUrl))"
    @open-command-palette.window="show()"
    @keydown.window="onGlobalKey($event)">

    <form x-ref="logout" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

    <div x-show="open" x-transition.opacity.duration.150ms style="display: none;"
        class="fixed inset-0 z-[9000] flex items-start justify-center px-4 pt-[10vh] bg-black/40"
        @click.self="hide()"
        role="dialog" aria-modal="true" aria-label="Pencarian dan perintah">

        <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-[#E4E8EF] overflow-hidden">
            {{-- Input --}}
            <div class="flex items-center gap-3 px-4 border-b border-[#E4E8EF]">
                <svg class="shrink-0" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input x-ref="input" x-model="query" type="text" autocomplete="off" spellcheck="false"
                    placeholder="Cari artikel atau ketik perintah..."
                    class="flex-1 py-4 text-[14px] bg-transparent outline-none placeholder:text-[#848CA3]">
                <kbd class="hidden sm:inline-flex text-[10.5px] font-semibold text-[#848CA3] bg-[#F1F3F7] border border-[#CDD3DF] rounded px-1.5 py-0.5">Esc</kbd>
            </div>

            {{-- Hasil --}}
            <div x-ref="list" class="max-h-[360px] overflow-y-auto pb-2">
                <template x-for="(item, i) in results" :key="i + '|' + item.label">
                    <div>
                        <div x-show="i === 0 || results[i - 1].group !== item.group"
                            class="px-4 pt-3 pb-1 text-[10.5px] font-bold uppercase tracking-wider text-[#848CA3]"
                            x-text="item.group"></div>

                        <button type="button"
                            :data-active="active === i"
                            @click="go(item)"
                            @mousemove="active = i"
                            :class="active === i ? 'bg-brand-50' : ''"
                            class="w-full text-left flex items-center gap-3 px-4 py-2.5 transition-colors">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                :class="active === i ? 'bg-white text-brand-600' : 'bg-[#F1F3F7] text-[#6C7387]'">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path :d="icons[item.icon] || icons.nav"></path>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] font-semibold truncate" x-text="item.label"></span>
                                <span x-show="item.hint" class="block text-[11.5px] text-[#848CA3] truncate" x-text="item.hint"></span>
                            </span>
                            <span x-show="active === i" class="hidden sm:block text-[11px] text-[#848CA3] shrink-0">Enter ↵</span>
                        </button>
                    </div>
                </template>

                <div x-show="loading" class="px-4 py-3 text-[12.5px] text-[#848CA3]">Mencari artikel...</div>

                <div x-show="results.length === 0 && !loading" class="px-4 py-10 text-center text-[13px] text-[#848CA3]">
                    Tidak ada hasil untuk "<span x-text="query"></span>".
                </div>
            </div>

            {{-- Footer --}}
            <div class="hidden sm:flex items-center gap-4 px-4 py-2.5 border-t border-[#E4E8EF] bg-[#F7F8FA] text-[11px] text-[#848CA3]">
                <span><kbd class="font-semibold">↑↓</kbd> pilih</span>
                <span><kbd class="font-semibold">Enter</kbd> buka</span>
                <span><kbd class="font-semibold">Esc</kbd> tutup</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('commandPalette', (commands, searchUrl, articlesUrl) => ({
            open: false,
            query: '',
            active: 0,
            articles: [],
            loading: false,
            timer: null,
            controller: null,
            commands,
            searchUrl,
            articlesUrl,
            icons: {
                nav: 'M5 12h14M13 6l6 6-6 6',
                plus: 'M12 5v14M5 12h14',
                doc: 'M6 3h9l5 5v13H6zM9 12h6M9 16h6',
                logout: 'M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9',
            },

            init() {
                this.$watch('query', () => {
                    this.active = 0;
                    this.queueSearch();
                });
                this.$watch('open', (v) => document.body.classList.toggle('overflow-hidden', v));
            },

            show() {
                this.open = true;
                this.$nextTick(() => this.$refs.input.focus());
            },

            hide() {
                this.open = false;
                this.query = '';
                this.articles = [];
                this.active = 0;
                this.loading = false;
                clearTimeout(this.timer);
                if (this.controller) this.controller.abort();
            },

            toggle() {
                this.open ? this.hide() : this.show();
            },

            get filteredCommands() {
                const words = this.query.trim().toLowerCase().split(/\s+/).filter(Boolean);
                if (!words.length) return this.commands;

                return this.commands.filter((c) => {
                    const hay = (c.label + ' ' + (c.keywords || '') + ' ' + c.group).toLowerCase();
                    return words.every((w) => hay.includes(w));
                });
            },

            get results() {
                const q = this.query.trim();
                const list = this.filteredCommands.map((c) => ({ ...c }));

                this.articles.forEach((a) => list.push({
                    group: 'Artikel',
                    label: a.title,
                    hint: a.hint,
                    url: a.url,
                    icon: 'doc',
                }));

                if (q.length >= 2 && this.articlesUrl) {
                    list.push({
                        group: 'Artikel',
                        label: 'Cari semua artikel: "' + q + '"',
                        url: this.articlesUrl + '?q=' + encodeURIComponent(q),
                        icon: 'nav',
                    });
                }

                return list;
            },

            queueSearch() {
                clearTimeout(this.timer);
                if (this.controller) this.controller.abort();

                const q = this.query.trim();
                if (!this.searchUrl || q.length < 2) {
                    this.articles = [];
                    this.loading = false;
                    return;
                }

                this.loading = true;
                this.timer = setTimeout(() => this.fetchArticles(q), 250);
            },

            async fetchArticles(q) {
                this.controller = new AbortController();

                try {
                    const res = await fetch(this.searchUrl + '?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        signal: this.controller.signal,
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const data = await res.json();
                    if (q === this.query.trim()) this.articles = data.articles || [];
                } catch (e) {
                    if (e.name !== 'AbortError' && q === this.query.trim()) this.articles = [];
                } finally {
                    if (q === this.query.trim()) this.loading = false;
                }
            },

            move(dir) {
                const n = this.results.length;
                if (!n) return;

                this.active = (this.active + dir + n) % n;
                this.$nextTick(() => {
                    this.$refs.list.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' });
                });
            },

            choose() {
                const item = this.results[this.active];
                if (item) this.go(item);
            },

            go(item) {
                const url = item.url;
                const action = item.action;
                this.hide();

                if (action === 'logout') {
                    this.$refs.logout.submit();
                    return;
                }

                if (url) window.location.href = url;
            },

            onGlobalKey(e) {
                if (e.isComposing) return;
                const key = e.key.toLowerCase();

                if ((e.metaKey || e.ctrlKey) && key === 'k') {
                    e.preventDefault();
                    this.toggle();
                    return;
                }

                if (!this.open) {
                    const t = e.target;
                    const typing = t && (['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName) || t.isContentEditable);
                    if (key === '/' && !typing && !e.metaKey && !e.ctrlKey && !e.altKey) {
                        e.preventDefault();
                        this.show();
                    }
                    return;
                }

                if (key === 'escape') { e.preventDefault(); this.hide(); }
                else if (key === 'arrowdown') { e.preventDefault(); this.move(1); }
                else if (key === 'arrowup') { e.preventDefault(); this.move(-1); }
                else if (key === 'enter') { e.preventDefault(); this.choose(); }
            },
        }));
    });

    // Tampilkan ⌘K di Mac, Ctrl K di lainnya
    document.addEventListener('DOMContentLoaded', () => {
        const isMac = /Mac|iPhone|iPad/.test(navigator.platform);
        document.querySelectorAll('[data-shortcut]').forEach((el) => {
            el.textContent = isMac ? '⌘K' : 'Ctrl K';
        });
    });
</script>
@endpush