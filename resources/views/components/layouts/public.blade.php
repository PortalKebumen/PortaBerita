@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'canonical' => null,
    'noindex' => false,
])

@php
    $site = \App\Models\SiteSetting::values();

    $siteName = $site['site_name'] ?: 'PortalKebumen.com';
    $tagline = $site['tagline'];
    $logo = $site['logo_url'] ?: asset('images/logo-full.png');

    $fullTitle = $title
        ? $title . ' - ' . $siteName
        : ($site['meta_title'] ?: trim($siteName . ($tagline ? ' - ' . $tagline : '')));
    $metaDescription = $description ?: $site['meta_description'];
    $ogImage = $image ?: $site['og_image_url'];
    $canonicalUrl = $canonical ?: url()->current();
    $htmlLang = ($site['locale'] ?? 'id') === 'en' ? 'en' : 'id';
    $ogLocale = $htmlLang === 'en' ? 'en_US' : 'id_ID';

    // Tautan sosial: hanya yang diisi di Pengaturan
    $socials = collect(\App\Models\SiteSetting::socialLinks());

    $currentUrl = url()->current();
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
    <meta property="og:title" content="{{ $title ?: $siteName }}">
    @if ($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title ?: $siteName }}">

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

        {{-- Baris 2: menu kategori (desktop). 7 tampil, sisanya di "Lainnya" (CSS murni) --}}
        <div class="hidden border-t border-[#CDD3DF] lg:block">
            <div class="mx-auto flex max-w-7xl items-center gap-1 px-4 py-1.5 sm:px-6 lg:px-8">
                @foreach (\App\Http\Controllers\Public\PublicController::kategoriNavVisible(7) as $item)
                    @php
                        $parentUrl = route('public.kategori', $item['slug']);
                        // Aktif juga saat membuka salah satu sub-kategorinya (/kategori/{induk}/{sub})
                        $active = $currentUrl === $parentUrl || str_starts_with($currentUrl, $parentUrl . '/');
                    @endphp

                    @if (count($item['children']))
                        <div class="group/dd relative shrink-0">
                            <a href="{{ $parentUrl }}" aria-haspopup="true"
                               @if ($currentUrl === $parentUrl) aria-current="page" @endif
                               class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold transition hover:bg-[#F1F3F7] hover:text-brand-600 group-hover/dd:bg-[#F1F3F7] group-hover/dd:text-brand-600 {{ $active ? 'bg-[#F1F3F7] text-brand-600' : 'text-[#3B4152]' }}">
                                {{ $item['label'] }}
                            </a>
                            <div class="absolute left-0 top-full z-40 hidden pt-1 group-focus-within/dd:block group-hover/dd:block">
                                <div class="min-w-52 rounded-lg border border-[#E4E8EF] bg-white py-2 shadow-lg">
                                    @foreach ($item['children'] as $child)
                                        @php($childUrl = route('public.kategori.sub', [$item['slug'], $child['slug']]))
                                        <a href="{{ $childUrl }}"
                                           @if ($currentUrl === $childUrl) aria-current="page" @endif
                                           class="block whitespace-nowrap px-4 py-1.5 text-sm font-semibold transition hover:text-brand-600 {{ $currentUrl === $childUrl ? 'text-brand-600' : 'text-[#3B4152]' }}">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ $parentUrl }}"
                           @if ($active) aria-current="page" @endif
                           class="shrink-0 whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold transition hover:bg-[#F1F3F7] hover:text-brand-600 {{ $active ? 'bg-[#F1F3F7] text-brand-600' : 'text-[#3B4152]' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach

                @php($overflowNav = \App\Http\Controllers\Public\PublicController::kategoriNavOverflow(7))
                @if (count($overflowNav))
                    <div class="group relative shrink-0">
                        <button type="button" aria-haspopup="true"
                                class="flex cursor-pointer items-center gap-1 whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold text-[#3B4152] transition hover:bg-[#F1F3F7] hover:text-brand-600">
                            Lainnya
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="absolute left-0 top-full z-40 hidden pt-1 group-focus-within:block group-hover:block">
                            <div class="max-h-[70vh] w-56 overflow-y-auto rounded-lg border border-[#E4E8EF] bg-white py-1.5 shadow-lg">
                                @foreach ($overflowNav as $item)
                                    @if (! $loop->first && count($item['children']))
                                        <div class="my-1 border-t border-[#EEF1F5]"></div>
                                    @endif
                                    <a href="{{ route('public.kategori', $item['slug']) }}"
                                       class="block px-4 py-2 text-sm font-semibold text-[#3B4152] hover:bg-[#F1F3F7] hover:text-brand-600">
                                        {{ $item['label'] }}
                                    </a>
                                    @foreach ($item['children'] as $child)
                                        <a href="{{ route('public.kategori.sub', [$item['slug'], $child['slug']]) }}"
                                           class="block py-1.5 pl-7 pr-4 text-[13px] font-medium text-[#5B6378] hover:bg-[#F1F3F7] hover:text-brand-600">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Panel mobile: pencarian & kategori --}}
        <input type="checkbox" id="mobile-search-toggle" class="peer/search hidden">
        <div class="hidden border-t border-[#CDD3DF] bg-white px-4 py-3 peer-checked/search:block sm:hidden">
            <form action="{{ route('public.cari') }}" method="GET" role="search">
                <input id="header-search-mobile" type="search" name="q" value="{{ request('q') }}" placeholder="Cari berita..." aria-label="Cari berita"
                       class="w-full rounded-full border border-[#E4E8EF] bg-[#F1F3F7] px-4 py-2 text-sm focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
            </form>
        </div>

        <input type="checkbox" id="mobile-nav-toggle" class="peer/nav hidden">
        <nav class="hidden max-h-[70vh] overflow-y-auto border-t border-[#CDD3DF] bg-white px-4 py-2 peer-checked/nav:block lg:hidden">
            @foreach (\App\Http\Controllers\Public\PublicController::kategoriNav() as $item)
                @if (count($item['children']))
                    @php($parentUrl = route('public.kategori', $item['slug']))
                    <details class="group/m" @if ($currentUrl === $parentUrl || str_starts_with($currentUrl, $parentUrl . '/')) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-3 py-2.5 text-sm font-medium text-[#3B4152] hover:bg-[#F1F3F7] [&::-webkit-details-marker]:hidden">
                            {{ $item['label'] }}
                            <svg class="h-4 w-4 text-[#848CA3] transition group-open/m:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <div class="mb-1 ml-3 border-l-2 border-[#E4E8EF] pl-2">
                            <a href="{{ $parentUrl }}" class="block rounded-md px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-[#F1F3F7]">Semua {{ $item['label'] }}</a>
                            @foreach ($item['children'] as $child)
                                <a href="{{ route('public.kategori.sub', [$item['slug'], $child['slug']]) }}"
                                   class="block rounded-md px-3 py-2 text-sm text-[#5B6378] hover:bg-[#F1F3F7] hover:text-brand-600">
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @else
                    <a href="{{ route('public.kategori', $item['slug']) }}"
                       class="block rounded-md px-3 py-2.5 text-sm font-medium text-[#3B4152] hover:bg-[#F1F3F7] hover:text-brand-600">
                        {{ $item['label'] }}
                    </a>
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
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-11 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div>
                <a href="{{ route('public.beranda') }}" class="inline-block">
                    <img src="{{ asset('images/logo-full.png') }}" alt="{{ $siteName }}" class="h-12 w-auto max-w-[260px] object-contain sm:h-14">
                </a>

                @if ($tagline)
                    <p class="mt-3 text-sm text-[#B9C3E6]">{{ $tagline }}</p>
                @endif

                <ul class="mt-4 space-y-2 text-sm">
                    @if ($site['address'])
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#7C88AE]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.7 8.3a5.7 5.7 0 10-11.4 0c0 4.3 5.7 10.7 5.7 10.7s5.7-6.4 5.7-10.7z"/><circle cx="12" cy="8.3" r="2"/></svg>
                            <span>{{ $site['address'] }}</span>
                        </li>
                    @endif
                    @if ($site['editorial_email'])
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#7C88AE]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9 6 9-6"/></svg>
                            <a href="mailto:{{ $site['editorial_email'] }}" class="hover:text-white">{{ $site['editorial_email'] }}</a>
                        </li>
                    @endif
                    @if ($site['phone'])
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#7C88AE]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></svg>
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['phone']) }}" class="hover:text-white">{{ $site['phone'] }}</a>
                        </li>
                    @endif
                </ul>

                @if ($socials->isNotEmpty())
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        @foreach ($socials as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}"
                               class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-[#B9C3E6] transition hover:bg-white/20 hover:text-white">
                                <x-public.social-icon :name="$social['key']" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h3 class="text-[13px] font-bold uppercase tracking-wide text-white">Redaksi</h3>
                <ul class="mt-3 space-y-2.5 text-sm">
                    {{-- TODO: arahkan ke halaman statis begitu dibuat --}}
                    <li><a href="#" class="hover:text-white">Tentang Kami</a></li>
                    <li><a href="#" class="hover:text-white">Pedoman Media Siber</a></li>
                    <li><a href="#" class="hover:text-white">Kode Etik Jurnalistik</a></li>
                    @if ($site['editorial_email'])
                        <li><a href="mailto:{{ $site['editorial_email'] }}" class="hover:text-white">Hubungi Kami</a></li>
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="text-[13px] font-bold uppercase tracking-wide text-white">Kategori</h3>
                <ul class="mt-3 space-y-2.5 text-sm">
                    @foreach (\App\Http\Controllers\Public\PublicController::kategoriNavVisible(5) as $item)
                        <li><a href="{{ route('public.kategori', $item['slug']) }}" class="hover:text-white">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="text-[13px] font-bold uppercase tracking-wide text-white">Layanan</h3>
                <ul class="mt-3 space-y-2.5 text-sm">
                    @if ($site['editorial_email'])
                        <li><a href="mailto:{{ $site['editorial_email'] }}?subject=Pasang%20Iklan" class="hover:text-white">Pasang Iklan (Rate Card)</a></li>
                        <li><a href="mailto:{{ $site['editorial_email'] }}?subject=Kirim%20Berita%20%2F%20Siaran%20Pers" class="hover:text-white">Kirim Berita / Siaran Pers</a></li>
                    @endif
                    <li><a href="#" class="hover:text-white">Kebijakan Privasi</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-[#223258]">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-4 text-xs text-[#7C88AE] sm:flex-row sm:px-6 lg:px-8">
                <span>&copy; {{ now()->year }} {{ $siteName }}. Hak cipta dilindungi.</span>
                <a href="{{ route('login') }}" class="hover:text-white">Login Redaksi</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>