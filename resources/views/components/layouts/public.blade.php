@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — PortalKebumen.com' : 'PortalKebumen.com — Cepat, Akurat, Terpercaya' }}</title>
    @if($description)
        <meta name="description" content="{{ $description }}">
    @endif

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-[#F1F3F7] text-[#171B28] antialiased">

    <header class="sticky top-0 z-30 bg-white border-b border-[#CDD3DF]">
        {{-- Baris 1: logo + search  --}}
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('public.beranda') }}" class="shrink-0">
                <img src="{{ asset('images/logo-full.png') }}" alt="PortalKebumen.com" class="h-9 w-auto max-w-[200px] object-contain sm:h-10 sm:max-w-[240px]">
            </a>

            <div class="hidden flex-1 sm:block">
                <form action="{{ route('public.beranda') }}" method="GET" class="relative mx-auto max-w-md">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#848CA3]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="search" name="q" placeholder="Cari berita, topik, atau tokoh..."
                           class="w-full rounded-full border border-[#E4E8EF] bg-[#F1F3F7] py-2 pl-9 pr-3 text-sm text-[#171B28] transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                </form>
            </div>

            <div class="ml-auto flex items-center gap-1 sm:ml-0">
                {{-- <label for="mobile-search-toggle" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-[#3B4152] hover:bg-[#F1F3F7] sm:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </label>

                <a href="{{ route('login') }}" aria-label="Login Redaksi" class="hidden h-10 w-10 items-center justify-center rounded-full text-[#3B4152] hover:bg-[#F1F3F7] sm:flex">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </a> --}}

                {{-- <label for="mobile-nav-toggle" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-[#3B4152] hover:bg-[#F1F3F7] lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </label> --}}
            </div>
        </div>

        {{-- Baris 2: nav kategori, berdiri sendiri jadi tidak perlu hitung lebar vs kolom search.
             Maksimal 7 tampil langsung, sisanya masuk dropdown "Lainnya" (statis, tanpa JS ukur lebar). --}}
        <div class="hidden border-t border-[#CDD3DF] lg:block">
            <div class="mx-auto flex max-w-7xl items-center gap-1 px-4 py-1.5 sm:px-6 lg:px-8">
                @foreach (\App\Http\Controllers\Public\PublicController::kategoriNavVisible(7) as $item)
                    <a href="{{ route('public.kategori', $item['slug']) }}"
                       class="shrink-0 whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold text-[#3B4152] transition hover:bg-[#F1F3F7] hover:text-brand-600">
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @php($overflowNav = \App\Http\Controllers\Public\PublicController::kategoriNavOverflow(7))
                @if (count($overflowNav))
                    <div class="relative shrink-0" data-dropdown>
                        <button type="button" data-dropdown-btn
                                class="flex cursor-pointer items-center gap-1 whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold text-[#3B4152] transition hover:bg-[#F1F3F7] hover:text-brand-600">
                            Lainnya
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div data-dropdown-menu class="absolute left-0 top-full z-40 mt-1 hidden w-52 rounded-lg border border-[#E4E8EF] bg-white py-1.5 shadow-lg">
                            @foreach ($overflowNav as $item)
                                <a href="{{ route('public.kategori', $item['slug']) }}"
                                   class="block px-4 py-2 text-sm font-medium text-[#3B4152] hover:bg-[#F1F3F7] hover:text-brand-600">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <input type="checkbox" id="mobile-search-toggle" class="peer/search hidden">
        <div class="hidden border-t border-[#CDD3DF] bg-white px-4 py-3 peer-checked/search:block sm:hidden">
            <form action="{{ route('public.beranda') }}" method="GET">
                <input type="search" name="q" placeholder="Cari berita..."
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

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-14 bg-brand-900 text-[#B9C3E6]">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-11 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div>
                <a href="{{ route('public.beranda') }}" class="inline-block">
                    <img src="{{ asset('images/logo-full.png') }}" alt="PortalKebumen.com" class="h-12 w-auto max-w-[260px] object-contain sm:h-14">
                </a>
                <div class="mt-4 flex items-center gap-3">
                    @foreach ([
                        'facebook' => 'M22 12a10 10 0 10-11.5 9.9v-7H8v-2.9h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.3c-1.2 0-1.6.8-1.6 1.6v1.9h2.8L15.9 15h-2.3v7A10 10 0 0022 12z',
                        'instagram' => 'M12 2c2.7 0 3.1 0 4.1.1 1.1 0 1.8.2 2.5.5.7.3 1.2.6 1.7 1.1.5.5.9 1 1.1 1.7.3.7.5 1.4.5 2.5.1 1 .1 1.4.1 4.1s0 3.1-.1 4.1c0 1.1-.2 1.8-.5 2.5-.3.7-.6 1.2-1.1 1.7-.5.5-1 .9-1.7 1.1-.7.3-1.4.5-2.5.5-1 .1-1.4.1-4.1.1s-3.1 0-4.1-.1c-1.1 0-1.8-.2-2.5-.5-.7-.3-1.2-.6-1.7-1.1-.5-.5-.9-1-1.1-1.7-.3-.7-.5-1.4-.5-2.5C2 15.1 2 14.7 2 12s0-3.1.1-4.1c0-1.1.2-1.8.5-2.5.3-.7.6-1.2 1.1-1.7.5-.5 1-.9 1.7-1.1.7-.3 1.4-.5 2.5-.5C8.9 2 9.3 2 12 2zm0 5a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.2 3.2 0 110-6.4 3.2 3.2 0 010 6.4zm5.2-8.4a1.2 1.2 0 100-2.4 1.2 1.2 0 000 2.4z',
                        'tiktok' => 'M16.6 5.8a4.3 4.3 0 01-3-3.8h-3v13.6a2.6 2.6 0 11-2.3-2.6v-3a5.6 5.6 0 105.3 5.6V9.3a7.3 7.3 0 004 1.2V7.4a4.3 4.3 0 01-1-1.6z',
                    ] as $network => $path)
                        <a href="#" aria-label="{{ ucfirst($network) }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-[#B9C3E6] transition hover:bg-white/20 hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="{{ $path }}"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-[13px] font-bold uppercase tracking-wide text-white">Redaksi</h3>
                <ul class="mt-3 space-y-2.5 text-sm">
                    <li><a href="#" class="hover:text-white">Tentang Kami</a></li>
                    <li><a href="#" class="hover:text-white">Pedoman Media Siber</a></li>
                    <li><a href="#" class="hover:text-white">Kode Etik Jurnalistik</a></li>
                    <li><a href="#" class="hover:text-white">Hubungi Kami</a></li>
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
                    <li><a href="#" class="hover:text-white">Pasang Iklan (Rate Card)</a></li>
                    <li><a href="#" class="hover:text-white">Kirim Berita / Siaran Pers</a></li>
                    <li><a href="#" class="hover:text-white">Kebijakan Privasi</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-[#223258]">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-4 text-xs text-[#7C88AE] sm:flex-row sm:px-6 lg:px-8">
                <span>&copy; {{ now()->year }} PortalKebumen.com</span>
            </div>
        </div>
    </footer>

</body>
</html>