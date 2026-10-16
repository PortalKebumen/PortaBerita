@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'canonical' => null,
    'noindex' => false,
    // Detail gambar & artikel untuk pratinjau share (WhatsApp, Facebook, X, Telegram)
    'imageWidth' => null,
    'imageHeight' => null,
    'imageAlt' => null,
    'publishedTime' => null,
    'modifiedTime' => null,
    'section' => null,
    'author' => null,
])

@php
    $site = \App\Models\SiteSetting::values();

    $siteName = $site['site_name'] ?: 'PortalKebumen.com';
    $tagline = $site['tagline'];
    $logo = $site['logo_url'] ?: asset('images/logo-header.png'); // versi 600px (32 KB) dari logo-full.png (516 KB)

    $fullTitle = $title
        ? $title . ' - ' . $siteName
        : ($site['meta_title'] ?: trim($siteName . ($tagline ? ' - ' . $tagline : '')));
    $metaDescription = $description ?: $site['meta_description'];
    // Gambar pratinjau share: gambar halaman > gambar default di Pengaturan > logo. Selalu URL absolut
    // (crawler WhatsApp/Facebook tidak mengerti URL relatif) dan https bila situs diakses lewat https.
    $ogImage = $image ?: $site['og_image_url'];
    if (! $ogImage) {
        // 1200x630, 36 KB: logo besar (>300 KB, rasio 6:1) tidak ditampilkan WhatsApp sebagai pratinjau
        $ogImage = asset('images/og-default.jpg');
        [$imageWidth, $imageHeight] = [1200, 630];
    }
    if (! preg_match('#^https?://#i', $ogImage)) {
        $ogImage = url($ogImage);
    }
    if (request()->isSecure()) {
        $ogImage = preg_replace('#^http://#i', 'https://', $ogImage);
    }
    $ogImageType = match (strtolower(pathinfo(parse_url($ogImage, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => null,
    };
    $shareTitle = $title ?: $siteName;
    $canonicalUrl = $canonical ?: url()->current();
    $htmlLang = ($site['locale'] ?? 'id') === 'en' ? 'en' : 'id';
    $ogLocale = $htmlLang === 'en' ? 'en_US' : 'id_ID';

    // Tautan sosial: hanya yang diisi di Pengaturan
    $socials = collect(\App\Models\SiteSetting::socialLinks());

    $currentUrl = url()->current();

    // Menu dari Admin > Menu. Navbar kosong = jatuh ke daftar kategori otomatis.
    $navItems = \App\Models\MenuItem::tree('navbar');
    if (! $navItems) {
        $navItems = collect(\App\Http\Controllers\Public\PublicController::kategoriNav())->map(fn ($c) => [
            'label' => $c['label'],
            'url' => route('public.kategori', $c['slug']),
            'new_tab' => false,
            'type' => 'category',
            'children' => collect($c['children'])->map(fn ($ch) => [
                'label' => $ch['label'],
                'url' => route('public.kategori.sub', [$c['slug'], $ch['slug']]),
                'new_tab' => false,
                'type' => 'category',
                'children' => [],
            ])->all(),
        ])->all();
    }
    $isActiveUrl = fn (?string $url) => $url && ($currentUrl === $url || str_starts_with($currentUrl, rtrim($url, '/') . '/'));

    $footerColumns = \App\Models\MenuItem::tree('footer');
    $footerBottom = \App\Models\MenuItem::tree('footer_bottom');
    $footerPopular = \App\Http\Controllers\Public\PublicController::footerPopular();
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    @if ($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    {{-- Open Graph & Twitter --}}
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $shareTitle }}">
    @if ($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    {{-- Lebar & tinggi gambar wajib agar gambar langsung tampil sejak share pertama --}}
    <meta property="og:image" content="{{ $ogImage }}">
    @if (str_starts_with($ogImage, 'https://'))
        <meta property="og:image:secure_url" content="{{ $ogImage }}">
    @endif
    @if ($ogImageType)
        <meta property="og:image:type" content="{{ $ogImageType }}">
    @endif
    @if ($imageWidth && $imageHeight)
        <meta property="og:image:width" content="{{ $imageWidth }}">
        <meta property="og:image:height" content="{{ $imageHeight }}">
    @endif
    <meta property="og:image:alt" content="{{ $imageAlt ?: $shareTitle }}">
    @if ($type === 'article')
        @if ($publishedTime)<meta property="article:published_time" content="{{ $publishedTime }}">@endif
        @if ($modifiedTime)<meta property="article:modified_time" content="{{ $modifiedTime }}">@endif
        @if ($section)<meta property="article:section" content="{{ $section }}">@endif
        @if ($author)<meta property="article:author" content="{{ $author }}">@endif
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $shareTitle }}">
    @if ($metaDescription)
        <meta name="twitter:description" content="{{ $metaDescription }}">
    @endif
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $imageAlt ?: $shareTitle }}">

    {{-- Google Analytics & verifikasi Search Console dari Pengaturan --}}
    <x-seo.site-head />

    {{-- Schema situs (hanya beranda) --}}
    @if (request()->routeIs('public.beranda'))
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'NewsMediaOrganization',
                    '@id' => url('/') . '#organization',
                    'name' => $siteName,
                    'url' => url('/'),
                    'logo' => $site['logo_url'] ? ['@type' => 'ImageObject', 'url' => $site['logo_url']] : null,
                    'sameAs' => $socials->reject(fn ($s) => $s['chat'])->pluck('url')->values()->all() ?: null,
                    'email' => $site['editorial_email'],
                    'telephone' => $site['phone'],
                ],
                [
                    '@type' => 'WebSite',
                    'name' => $siteName,
                    'url' => url('/'),
                    'inLanguage' => $htmlLang === 'en' ? 'en-US' : 'id-ID',
                    'publisher' => ['@id' => url('/') . '#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('public.cari') . '?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-[#F1F3F7] text-[#171B28] antialiased">

    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:shadow-lg">
        Lewati ke konten
    </a>

    {{-- Bar atas: tanggal + tagline --}}
    <div class="hidden bg-brand-900 text-[#B9C3E6] md:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-1.5 text-[12px] sm:px-6 lg:px-8">
            <span>{{ now()->translatedFormat('l, d F Y') }}</span>
            @if ($tagline)
                <span class="truncate">{{ $tagline }}</span>
            @endif
        </div>
    </div>

    <header class="sticky top-0 z-30 border-b border-[#CDD3DF] bg-white">
        {{-- Baris 1: logo + pencarian + tombol mobile --}}
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('public.beranda') }}" class="shrink-0">
                <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-9 w-auto max-w-[200px] object-contain sm:h-10 sm:max-w-[240px]">
            </a>

            <div class="hidden flex-1 sm:block">
                <form action="{{ route('public.cari') }}" method="GET" class="relative mx-auto max-w-md" role="search">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#848CA3]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="Cari berita, topik, atau tokoh..." aria-label="Cari berita"
                           class="w-full rounded-full border border-[#E4E8EF] bg-[#F1F3F7] py-2 pl-9 pr-3 text-sm text-[#171B28] transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                </form>
            </div>

            <div class="ml-auto flex items-center gap-1 sm:ml-0">
                <label for="mobile-search-toggle" aria-label="Cari"
                       class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-[#3B4152] hover:bg-[#F1F3F7] sm:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </label>

                <label for="mobile-nav-toggle" aria-label="Menu kategori"
                       class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-[#3B4152] hover:bg-[#F1F3F7] lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </label>
            </div>
        </div>

        {{-- Baris 2: menu utama (desktop), dari Admin > Menu lokasi Navbar.
             Item yang tidak muat di lebar kontainer otomatis pindah ke tombol ≡ di kanan (lihat skrip di bawah). --}}
        <div class="hidden border-t border-[#CDD3DF] lg:block">
            <nav data-priority-nav class="mx-auto flex max-w-7xl items-center gap-1 px-4 py-1.5 sm:px-6 lg:px-8" aria-label="Menu utama">
                <ul data-nav-list class="flex min-w-0 flex-1 flex-wrap items-center gap-1">
                    @foreach ($navItems as $i => $item)
                        @php($active = $isActiveUrl($item['url']) || collect($item['children'])->contains(fn ($c) => $isActiveUrl($c['url'])))
                        <li data-nav-item="{{ $i }}" class="group/dd relative shrink-0">
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                                   @if ($currentUrl === $item['url']) aria-current="page" @endif
                                   @if (count($item['children'])) aria-haspopup="true" @endif
                                   class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold transition hover:bg-[#F1F3F7] hover:text-brand-600 {{ count($item['children']) ? 'group-hover/dd:bg-[#F1F3F7] group-hover/dd:text-brand-600' : '' }} {{ $active ? 'bg-[#F1F3F7] text-brand-600' : 'text-[#3B4152]' }}">
                                    {{ $item['label'] }}
                                </a>
                            @else
                                <span class="block cursor-default whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold text-[#3B4152] group-hover/dd:bg-[#F1F3F7] group-hover/dd:text-brand-600">{{ $item['label'] }}</span>
                            @endif

                            @if (count($item['children']))
                                <div class="absolute left-0 top-full z-40 hidden pt-1 group-focus-within/dd:block group-hover/dd:block">
                                    <div class="min-w-52 rounded-lg border border-[#E4E8EF] bg-white py-2 shadow-lg">
                                        @foreach ($item['children'] as $child)
                                            <a href="{{ $child['url'] ?? '#' }}" @if ($child['new_tab']) target="_blank" rel="noopener" @endif
                                               @if ($currentUrl === $child['url']) aria-current="page" @endif
                                               class="block whitespace-nowrap px-4 py-1.5 text-sm font-semibold transition hover:text-brand-600 {{ $currentUrl === $child['url'] ? 'text-brand-600' : 'text-[#3B4152]' }}">
                                                {{ $child['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                {{-- Tombol ≡: menampung item yang tidak muat --}}
                <div data-nav-more hidden class="group relative shrink-0">
                    <button type="button" aria-haspopup="true" aria-label="Menu lainnya" title="Menu lainnya"
                            class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-md text-[#3B4152] transition hover:bg-[#F1F3F7] hover:text-brand-600 group-focus-within:bg-[#F1F3F7] group-hover:bg-[#F1F3F7] group-hover:text-brand-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div class="absolute right-0 top-full z-40 hidden pt-1 group-focus-within:block group-hover:block">
                        <ul class="max-h-[70vh] w-60 overflow-y-auto rounded-lg border border-[#E4E8EF] bg-white py-1.5 shadow-lg">
                            @foreach ($navItems as $i => $item)
                                <li data-more-item="{{ $i }}" hidden>
                                    @if ($item['url'])
                                        <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                                           class="block px-4 py-2 text-sm font-semibold hover:bg-[#F1F3F7] hover:text-brand-600 {{ $isActiveUrl($item['url']) ? 'text-brand-600' : 'text-[#3B4152]' }}">{{ $item['label'] }}</a>
                                    @else
                                        <span class="block px-4 py-2 text-xs font-bold uppercase tracking-wide text-[#848CA3]">{{ $item['label'] }}</span>
                                    @endif
                                    @foreach ($item['children'] as $child)
                                        <a href="{{ $child['url'] ?? '#' }}" @if ($child['new_tab']) target="_blank" rel="noopener" @endif
                                           class="block py-1.5 pl-7 pr-4 text-[13px] font-medium text-[#5B6378] hover:bg-[#F1F3F7] hover:text-brand-600">{{ $child['label'] }}</a>
                                    @endforeach
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </nav>
        </div>

        <script>
            // Navbar "priority+": tampilkan item sebanyak yang muat, sisanya masuk ke tombol ≡.
            (function () {
                var nav = document.querySelector('[data-priority-nav]');
                if (!nav) return;
                var list = nav.querySelector('[data-nav-list]');
                var more = nav.querySelector('[data-nav-more]');
                var items = Array.prototype.slice.call(list.querySelectorAll('[data-nav-item]'));
                var moreItems = Array.prototype.slice.call(more.querySelectorAll('[data-more-item]'));

                list.classList.remove('flex-wrap');

                var layout = function () {
                    if (!nav.offsetParent) return; // navbar desktop sedang disembunyikan (layar kecil)

                    items.forEach(function (li) { li.hidden = false; });
                    more.hidden = true;
                    if (list.scrollWidth <= list.clientWidth) {
                        moreItems.forEach(function (li) { li.hidden = true; });
                        return;
                    }

                    more.hidden = false;
                    var limit = list.clientWidth;
                    var overflow = false;
                    items.forEach(function (li, i) {
                        overflow = overflow || (li.offsetLeft - list.offsetLeft + li.offsetWidth > limit);
                        li.hidden = overflow;
                        moreItems[i].hidden = !overflow;
                    });
                };

                var timer;
                window.addEventListener('resize', function () {
                    clearTimeout(timer);
                    timer = setTimeout(layout, 100);
                });
                if (document.fonts && document.fonts.ready) document.fonts.ready.then(layout);
                layout();
            })();
        </script>

        {{-- Panel mobile: pencarian & kategori --}}
        <input type="checkbox" id="mobile-search-toggle" class="peer/search hidden">
        <div class="hidden border-t border-[#CDD3DF] bg-white px-4 py-3 peer-checked/search:block sm:hidden">
            <form action="{{ route('public.cari') }}" method="GET" role="search">
                <input id="header-search-mobile" type="search" name="q" value="{{ request('q') }}" placeholder="Cari berita..." aria-label="Cari berita"
                       class="w-full rounded-full border border-[#E4E8EF] bg-[#F1F3F7] px-4 py-2 text-sm focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
            </form>
        </div>

        <input type="checkbox" id="mobile-nav-toggle" class="peer/nav hidden">
        <nav class="hidden max-h-[70vh] overflow-y-auto border-t border-[#CDD3DF] bg-white px-4 py-2 peer-checked/nav:block lg:hidden" aria-label="Menu utama">
            @foreach ($navItems as $item)
                @if (count($item['children']))
                    <details class="group/m" @if ($isActiveUrl($item['url']) || collect($item['children'])->contains(fn ($c) => $isActiveUrl($c['url']))) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-3 py-2.5 text-sm font-medium text-[#3B4152] hover:bg-[#F1F3F7] [&::-webkit-details-marker]:hidden">
                            {{ $item['label'] }}
                            <svg class="h-4 w-4 text-[#848CA3] transition group-open/m:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <div class="mb-1 ml-3 border-l-2 border-[#E4E8EF] pl-2">
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" class="block rounded-md px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-[#F1F3F7]">Semua {{ $item['label'] }}</a>
                            @endif
                            @foreach ($item['children'] as $child)
                                <a href="{{ $child['url'] ?? '#' }}" @if ($child['new_tab']) target="_blank" rel="noopener" @endif
                                   class="block rounded-md px-3 py-2 text-sm text-[#5B6378] hover:bg-[#F1F3F7] hover:text-brand-600">{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    </details>
                @elseif ($item['url'])
                    <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                       class="block rounded-md px-3 py-2.5 text-sm font-medium text-[#3B4152] hover:bg-[#F1F3F7] hover:text-brand-600">{{ $item['label'] }}</a>
                @endif
            @endforeach
        </nav>
    </header>

    {{-- Breaking news (artikel ditandai Breaking, 48 jam terakhir) --}}
    <x-public.breaking-ticker />

    {{-- Iklan header --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-public.ad-slot placement="header" height="h-24" :placeholder="false" class="mt-4" />
    </div>

    <main id="konten" class="flex-1">
        {{ $slot }}
    </main>

    {{-- Iklan footer --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-public.ad-slot placement="footer" height="h-24" :placeholder="false" class="mt-8" />
    </div>

    <footer class="mt-14 bg-brand-900 text-[#B9C3E6]">
        {{-- 1. Identitas + kolom menu footer (Admin > Menu lokasi Footer) --}}
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-12 lg:px-8">
            <div class="lg:col-span-4">
                <a href="{{ route('public.beranda') }}" class="inline-block">
                    <img src="{{ asset('images/logo-header.png') }}" alt="{{ $siteName }}" class="h-12 w-auto max-w-[260px] object-contain sm:h-14">
                </a>

                @if ($site['meta_description'] || $tagline)
                    <p class="mt-4 max-w-sm text-sm leading-relaxed">{{ $site['meta_description'] ?: $tagline }}</p>
                @endif

                <ul class="mt-5 space-y-2.5 text-sm">
                    @if ($site['address'])
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.7 8.3a5.7 5.7 0 10-11.4 0c0 4.3 5.7 10.7 5.7 10.7s5.7-6.4 5.7-10.7z"/><circle cx="12" cy="8.3" r="2"/></svg>
                            <span>{{ $site['address'] }}</span>
                        </li>
                    @endif
                    @if ($site['editorial_email'])
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9 6 9-6"/></svg>
                            <a href="mailto:{{ $site['editorial_email'] }}" class="hover:text-white">{{ $site['editorial_email'] }}</a>
                        </li>
                    @endif
                    @if ($site['phone'])
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></svg>
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['phone']) }}" class="hover:text-white">{{ $site['phone'] }}</a>
                        </li>
                    @endif
                </ul>

                @if ($socials->isNotEmpty())
                    <p class="mt-6 text-[12px] font-bold uppercase tracking-wider text-white">Ikuti Kami</p>
                    <div class="mt-2.5 flex flex-wrap items-center gap-2.5">
                        @foreach ($socials as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}" title="{{ $social['label'] }}"
                               style="--brand: {{ $social['color'] }}"
                               class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:-translate-y-0.5 hover:bg-(--brand)">
                                <x-public.social-icon :name="$social['key']" />
                            </a>
                        @endforeach
                    </div>
                @endif

            </div>

            @if (count($footerColumns))
                <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-8 {{ count($footerColumns) >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
                    @foreach ($footerColumns as $column)
                        <div>
                            @if ($column['url'] && ! count($column['children']))
                                <a href="{{ $column['url'] }}" @if ($column['new_tab']) target="_blank" rel="noopener" @endif class="text-[13px] font-bold uppercase tracking-wider text-white hover:text-accent-400">{{ $column['label'] }}</a>
                            @else
                                <h3 class="text-[13px] font-bold uppercase tracking-wider text-white">{{ $column['label'] }}</h3>
                            @endif
                            <span class="mt-2 block h-0.5 w-8 rounded bg-accent-500"></span>
                            @if (count($column['children']))
                                <ul class="mt-4 space-y-2.5 text-sm">
                                    @foreach ($column['children'] as $link)
                                        <li>
                                            <a href="{{ $link['url'] ?? '#' }}" @if ($link['new_tab']) target="_blank" rel="noopener" @endif
                                               class="transition hover:pl-1 hover:text-white">{{ $link['label'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 2. Terpopuler pekan ini --}}
        @if (count($footerPopular))
            <div class="border-t border-white/10 bg-black/15">
                <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-[13px] font-bold uppercase tracking-wider text-white">Terpopuler Pekan Ini</h3>
                        <a href="{{ route('public.cari', ['urut' => 'terpopuler']) }}" class="text-xs font-semibold text-accent-400 hover:text-white">Lihat semua &rarr;</a>
                    </div>
                    <ol class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($footerPopular as $i => $pop)
                            <li>
                                <a href="{{ $pop['url'] }}" class="group flex gap-3">
                                    <span class="relative h-16 w-20 shrink-0 overflow-hidden rounded-md bg-white/10">
                                        @if ($pop['image'])
                                            <img src="{{ $pop['image'] }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @endif
                                        <span class="absolute left-0 top-0 flex h-6 w-6 items-center justify-center rounded-br-md bg-accent-500 text-xs font-extrabold text-white">{{ $i + 1 }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        @if ($pop['category'])
                                            <span class="block text-[10.5px] font-bold uppercase tracking-wide text-accent-400">{{ $pop['category'] }}</span>
                                        @endif
                                        <span class="mt-0.5 line-clamp-2 text-sm font-semibold leading-snug text-white group-hover:underline">{{ $pop['title'] }}</span>
                                        @if ($pop['date'])
                                            <span class="mt-1 block text-[11px] text-[#7C88AE]">{{ $pop['date'] }}</span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endif

        {{-- 3. Baris bawah: hak cipta + menu Footer Bawah --}}
        <div class="border-t border-white/10 bg-[#081529]">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-4 text-xs text-[#7C88AE] sm:px-6 md:flex-row lg:px-8">
                <span>&copy; {{ now()->year }} {{ $siteName }}. Hak cipta dilindungi undang-undang.</span>
                <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5">
                    @foreach ($footerBottom as $link)
                        @if ($link['url'])
                            <a href="{{ $link['url'] }}" @if ($link['new_tab']) target="_blank" rel="noopener" @endif class="hover:text-white">{{ $link['label'] }}</a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>