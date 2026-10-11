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
        ? $title . ' — ' . $siteName
        : ($site['meta_title'] ?: trim($siteName . ($tagline ? ' — ' . $tagline : '')));
    $metaDescription = $description ?: $site['meta_description'];
    $ogImage = $image ?: $site['og_image_url'];
    $canonicalUrl = $canonical ?: url()->current();
    $htmlLang = ($site['locale'] ?? 'id') === 'en' ? 'en' : 'id';
    $ogLocale = $htmlLang === 'en' ? 'en_US' : 'id_ID';

    // Tautan sosial: hanya yang diisi di Pengaturan
    $waDigits = preg_replace('/\D+/', '', (string) $site['whatsapp']);
    if ($waDigits !== '' && str_starts_with($waDigits, '0')) {
        $waDigits = '62' . substr($waDigits, 1);
    }

    $socials = collect([
        'Facebook' => [$site['facebook'], 'M22 12a10 10 0 10-11.5 9.9v-7H8v-2.9h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.3c-1.2 0-1.6.8-1.6 1.6v1.9h2.8L15.9 15h-2.3v7A10 10 0 0022 12z'],
        'Instagram' => [$site['instagram'], 'M12 2c2.7 0 3.1 0 4.1.1 1.1 0 1.8.2 2.5.5.7.3 1.2.6 1.7 1.1.5.5.9 1 1.1 1.7.3.7.5 1.4.5 2.5.1 1 .1 1.4.1 4.1s0 3.1-.1 4.1c0 1.1-.2 1.8-.5 2.5-.3.7-.6 1.2-1.1 1.7-.5.5-1 .9-1.7 1.1-.7.3-1.4.5-2.5.5-1 .1-1.4.1-4.1.1s-3.1 0-4.1-.1c-1.1 0-1.8-.2-2.5-.5-.7-.3-1.2-.6-1.7-1.1-.5-.5-.9-1-1.1-1.7-.3-.7-.5-1.4-.5-2.5C2 15.1 2 14.7 2 12s0-3.1.1-4.1c0-1.1.2-1.8.5-2.5.3-.7.6-1.2 1.1-1.7.5-.5 1-.9 1.7-1.1.7-.3 1.4-.5 2.5-.5C8.9 2 9.3 2 12 2zm0 5a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.2 3.2 0 110-6.4 3.2 3.2 0 010 6.4zm5.2-8.4a1.2 1.2 0 100-2.4 1.2 1.2 0 000 2.4z'],
        'X (Twitter)' => [$site['x_twitter'], 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'],
        'YouTube' => [$site['youtube'], 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'],
        'WhatsApp' => [$waDigits !== '' ? 'https://wa.me/' . $waDigits : null, 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z'],
    ])->filter(fn ($s) => filled($s[0]));

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
                    'sameAs' => $socials->pluck(0)->reject(fn ($u) => str_contains($u, 'wa.me'))->values()->all() ?: null,
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
                        'target' => route('public.beranda') . '?q={search_term_string}',
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
                <form action="{{ route('public.beranda') }}" method="GET" class="relative mx-auto max-w-md" role="search">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#848CA3]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari berita, topik, atau tokoh..."
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
                    @php($active = $currentUrl === route('public.kategori', $item['slug']))
                    <a href="{{ route('public.kategori', $item['slug']) }}"
                       @if ($active) aria-current="page" @endif
                       class="shrink-0 whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold transition hover:bg-[#F1F3F7] hover:text-brand-600 {{ $active ? 'bg-[#F1F3F7] text-brand-600' : 'text-[#3B4152]' }}">
                        {{ $item['label'] }}
                    </a>
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
                            <div class="w-52 rounded-lg border border-[#E4E8EF] bg-white py-1.5 shadow-lg">
                                @foreach ($overflowNav as $item)
                                    <a href="{{ route('public.kategori', $item['slug']) }}"
                                       class="block px-4 py-2 text-sm font-medium text-[#3B4152] hover:bg-[#F1F3F7] hover:text-brand-600">
                                        {{ $item['label'] }}
                                    </a>
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
            <form action="{{ route('public.beranda') }}" method="GET" role="search">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari berita..."
                       class="w-full rounded-full border border-[#E4E8EF] bg-[#F1F3F7] px-4 py-2 text-sm focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
            </form>
        </div>

        <input type="checkbox" id="mobile-nav-toggle" class="peer/nav hidden">
        <nav class="hidden max-h-[70vh] overflow-y-auto border-t border-[#CDD3DF] bg-white px-4 py-2 peer-checked/nav:block lg:hidden">
            @foreach (\App\Http\Controllers\Public\PublicController::kategoriNav() as $item)
                <a href="{{ route('public.kategori', $item['slug']) }}"
                   class="block rounded-md px-3 py-2.5 text-sm font-medium text-[#3B4152] hover:bg-[#F1F3F7] hover:text-brand-600">
                    {{ $item['label'] }}
                </a>
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
                        @foreach ($socials as $label => [$url, $path])
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}"
                               class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-[#B9C3E6] transition hover:bg-white/20 hover:text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="{{ $path }}"/></svg>
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