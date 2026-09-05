@props(['title' => null, 'breadcrumbs' => []])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' - Admin PortalKebumen' : 'Admin PortalKebumen' }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
</head>
<body class="bg-[#F7F8FA] text-[#171B28] font-sans antialiased">
    <div class="flex min-h-screen">

        <input type="checkbox" id="sidebar-toggle" class="peer hidden">
        <label for="sidebar-toggle" class="hidden peer-checked:block lg:hidden fixed inset-0 bg-black/40 z-30" aria-hidden="true"></label>

        {{-- ===== SIDEBAR ===== --}}
        <aside id="app-sidebar" class="w-64 shrink-0 bg-brand-900 text-[#B9C3E6] flex flex-col fixed inset-y-0 left-0 z-40 -translate-x-full peer-checked:translate-x-0 transition-[transform,width] duration-200 lg:translate-x-0 lg:static overflow-y-auto overflow-x-hidden">

            <div class="flex items-center justify-between gap-2 px-5 h-16 lg:h-[72px] shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                    <img src="{{ asset('images/logo-full.png') }}" alt="PortalKebumen" class="sidebar-label h-8 w-auto max-w-[168px] object-contain shrink-0">
                    <img src="{{ asset('icon.png') }}" alt="PortalKebumen" class="hidden sidebar-collapsed-only h-8 w-8 shrink-0">
                </a>
                <label for="sidebar-toggle" aria-label="Tutup menu" class="lg:hidden w-8 h-8 rounded-lg flex items-center justify-center cursor-pointer text-[#B9C3E6] hover:bg-white/10 shrink-0">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </label>
            </div>

            @php
                $menuGroups = [
                    [
                        'label' => null,
                        'items' => [
                            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'permission' => 'dashboard.view'],
                        ],
                    ],
                    [
                        'label' => 'Konten',
                        'items' => [
                            ['route' => 'admin.artikel.index', 'label' => 'Artikel', 'icon' => 'artikel', 'permission' => 'articles.view'],
                            ['route' => 'admin.kategori-tag.index', 'label' => 'Kategori & Tag', 'icon' => 'kategori', 'permission' => 'categories.view'],
                            ['route' => 'admin.media-library.index', 'label' => 'Media Library', 'icon' => 'media', 'permission' => 'media.view'],
                        ],
                    ],
                    [
                        'label' => 'Monetisasi',
                        'items' => [
                            ['route' => 'admin.iklan.index', 'label' => 'Iklan', 'icon' => 'iklan', 'permission' => 'ads.view'],
                        ],
                    ],
                    [
                        'label' => 'Manajemen',
                        'items' => [
                            ['route' => 'admin.pengguna-role.index', 'label' => 'Pengguna & Role', 'icon' => 'pengguna', 'permission' => 'users.view'],
                            ['route' => 'admin.activity-log.index', 'label' => 'Activity Log', 'icon' => 'activity', 'permission' => 'activity-log.view'],
                        ],
                    ],
                ];
                $menuGroups = collect($menuGroups)->map(function ($group) {
                    $group['items'] = array_filter($group['items'], fn ($item) => auth()->user()->can($item['permission']));

                    return $group;
                })->filter(fn ($group) => count($group['items']));
                $initials = collect(explode(' ', auth()->user()->name))
                    ->map(fn ($w) => mb_substr($w, 0, 1))
                    ->take(2)->implode('');
            @endphp

            <nav class="flex flex-col flex-1 px-3 py-4">
                <div class="sidebar-label px-3 pb-2 text-[10.5px] font-bold uppercase tracking-wider text-[#7C88AE]">Menu</div>

                @foreach ($menuGroups as $group)
                    @if ($group['label'])
                        <div class="sidebar-label px-3 pt-5 pb-1.5 text-[10.5px] font-bold uppercase tracking-wider text-[#7C88AE]">
                            {{ $group['label'] }}
                        </div>
                    @endif
                    <div class="flex flex-col gap-0.5">
                        @foreach ($group['items'] as $item)
                            <a href="{{ route($item['route']) }}"
                            title="{{ $item['label'] }}"
                            class="sidebar-nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13.5px] font-medium transition-colors
                                    {{ request()->routeIs($item['route'])
                                            ? 'bg-white/10 text-white font-semibold'
                                            : 'text-[#B9C3E6] hover:bg-white/[0.05] hover:text-white' }}">
                                <x-admin-menu-icon :name="$item['icon']" />
                                <span class="sidebar-label truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach

                @can('settings.view')
                <div class="border-t border-white/10 mt-auto pt-3">
                    <a href="{{ route('admin.pengaturan.index') }}"
                    title="Pengaturan"
                    class="sidebar-nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13.5px] font-medium transition-colors
                            {{ request()->routeIs('admin.pengaturan.index')
                                    ? 'bg-white/10 text-white font-semibold'
                                    : 'text-[#B9C3E6] hover:bg-white/[0.05] hover:text-white' }}">
                        <x-admin-menu-icon name="pengaturan" />
                        <span class="sidebar-label truncate">Pengaturan</span>
                    </a>
                </div>
                @endcan
            </nav>

            <div class="flex items-center gap-2.5 px-4 py-4 border-t border-white/10 shrink-0">
                <div class="w-9 h-9 rounded-full bg-brand-700 text-white text-[11px] font-bold flex items-center justify-center shrink-0">
                    {{ $initials }}
                </div>
                <div class="sidebar-label min-w-0">
                    <div class="text-white text-[13px] font-semibold truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-[#7C88AE] mt-0.5 truncate">{{ auth()->user()->getRoleNames()->first() ?? '—' }}</div>
                </div>
            </div>
        </aside>

        {{-- ===== MAIN ===== --}}
        <div class="flex-1 min-w-0 flex flex-col">
            <header class="bg-white border-b border-[#E4E8EF] h-16 lg:h-[72px] flex items-center gap-3 px-4 sm:px-6 lg:px-8 shrink-0 sticky top-0 z-20">
                <label for="sidebar-toggle" aria-label="Buka menu" class="lg:hidden w-9 h-9 rounded-lg bg-[#F1F3F7] flex items-center justify-center cursor-pointer shrink-0">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#171B28" stroke-width="2" stroke-linecap="round">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </label>

                <button type="button" data-sidebar-collapse aria-label="Ciutkan menu" class="hidden lg:flex w-9 h-9 rounded-lg bg-[#F1F3F7] items-center justify-center hover:bg-[#E4E8EF] transition-colors cursor-pointer shrink-0">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#171B28" stroke-width="2" stroke-linecap="round">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                @can('articles.view')
                <form action="{{ route('admin.artikel.index') }}" method="GET" class="hidden md:flex items-center gap-2 bg-[#F1F3F7] rounded-lg px-3.5 py-2.5 flex-1 max-w-md">
                    <svg class="shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2">
                        <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input id="global-search" type="text" name="q" class="bg-transparent outline-none text-sm w-full placeholder:text-[#848CA3]" placeholder="Cari atau ketik perintah...">
                    <kbd class="hidden sm:inline-flex items-center gap-0.5 text-[10.5px] font-semibold text-[#848CA3] bg-white border border-[#CDD3DF] rounded px-1.5 py-0.5 shrink-0">⌘K</kbd>
                </form>

                <button type="button" data-modal-open="modal-search" aria-label="Cari" class="md:hidden w-9 h-9 rounded-lg bg-[#F1F3F7] flex items-center justify-center shrink-0">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#171B28" stroke-width="2">
                        <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>

                @endcan
                <div class="flex items-center gap-2 sm:gap-3 ml-auto shrink-0">
                    <div class="relative inline-block" data-dropdown>
                        <button type="button" data-dropdown-btn aria-label="Notifikasi" class="w-9 h-9 rounded-lg bg-[#F1F3F7] flex items-center justify-center hover:bg-[#E4E8EF] transition-colors">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#171B28" stroke-width="2">
                                <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.7 21a2 2 0 01-3.4 0"/>
                            </svg>
                        </button>
                        <div class="hidden absolute right-0 mt-2 w-72 rounded-xl bg-white border border-[#E4E8EF] shadow-lg py-1.5 z-30" data-dropdown-menu>
                            <div class="px-4 py-3 border-b border-[#E4E8EF] text-[13px] font-semibold">Notifikasi</div>
                            <div class="px-4 py-8 text-center text-[13px] text-[#848CA3]">Belum ada notifikasi.</div>
                        </div>
                    </div>

                    <div class="hidden sm:block w-px h-6 bg-[#E4E8EF]"></div>

                    <div class="relative inline-block" data-dropdown>
                        <button type="button" data-dropdown-btn class="flex items-center gap-2 sm:gap-2.5 rounded-lg px-1.5 py-1 hover:bg-[#F1F3F7] transition-colors">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-brand-700 text-white text-[11px] font-bold flex items-center justify-center shrink-0">
                                {{ $initials }}
                            </div>
                            <span class="hidden sm:block text-[13px] font-semibold max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                            <svg class="hidden sm:block shrink-0" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </button>

                        <div class="hidden absolute right-0 mt-2 w-56 rounded-xl bg-white border border-[#E4E8EF] shadow-lg py-1.5 z-30" data-dropdown-menu>
                            <div class="px-3.5 py-2.5 border-b border-[#E4E8EF]">
                                <div class="text-[13px] font-semibold truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[11.5px] text-[#848CA3] truncate">{{ auth()->user()->email }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-[13px] text-danger hover:bg-danger-bg transition-colors rounded-b-xl">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                                        <path d="M16 17l5-5-5-5"/>
                                        <path d="M21 12H9"/>
                                    </svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-8 w-full max-w-[1440px] mx-auto">
                <div class="mb-6">
                    @if (count($breadcrumbs))
                        <nav class="flex items-center gap-2 text-[12.5px] flex-wrap mb-2">
                            <a href="{{ route('admin.dashboard') }}" class="text-[#848CA3] hover:text-brand-600">Dashboard</a>
                            @foreach ($breadcrumbs as $crumb)
                                <span class="text-[#CDD3DF]">/</span>
                                @if (!$loop->last && isset($crumb['url']))
                                    <a href="{{ $crumb['url'] }}" class="text-[#848CA3] hover:text-brand-600">{{ $crumb['label'] }}</a>
                                @else
                                    <span class="font-semibold text-[#171B28]">{{ $crumb['label'] }}</span>
                                @endif
                            @endforeach
                        </nav>
                    @endif
                    <h1 class="text-2xl font-bold text-[#171B28]">{{ $title }}</h1>
                </div>

                {{ $slot }}
            </main>
        </div>
    </div>

    @can('articles.view')
    <div class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40" id="modal-search">
        <div class="bg-white rounded-2xl max-w-[420px] w-full p-5 shadow-xl">
            <h3 class="text-[15px] font-bold mb-3">Cari</h3>
            <form action="{{ route('admin.artikel.index') }}" method="GET">
                <div class="flex items-center gap-2 bg-[#F1F3F7] rounded-lg px-3 py-2.5 mb-4">
                    <svg class="shrink-0" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2">
                        <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" name="q" class="bg-transparent outline-none text-sm w-full" placeholder="Cari artikel..." autofocus>
                </div>
                <div class="flex gap-2.5">
                    <button type="button" data-modal-close class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1">Cari</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</body>
</html>
