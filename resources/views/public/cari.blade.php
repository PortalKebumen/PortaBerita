@use('App\Http\Controllers\Public\PublicController')

@php
    // Tautan yang mempertahankan parameter pencarian saat ini, dengan sebagian diganti/dihapus
    $linkWith = fn (array $changes) => route('public.cari', array_filter(
        array_merge(\Illuminate\Support\Arr::except(request()->query(), ['page']), $changes),
        fn ($v) => filled($v)
    ));
    $resetFilters = $linkWith(['kategori' => null, 'waktu' => null, 'dari' => null, 'sampai' => null]);

    $activeFilters = ($kategori ? 1 : 0) + ($time !== 'semua' ? 1 : 0);
    $title = $q !== '' ? 'Hasil pencarian: ' . $q : 'Pencarian';
    $timeLabel = $time === 'pilih'
        ? ($from?->translatedFormat('d M Y') ?? 'Awal') . ' – ' . ($to?->translatedFormat('d M Y') ?? 'Sekarang')
        : PublicController::SEARCH_TIMES[$time];
@endphp

<x-layouts.public :title="$title" :noindex="true">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">

                {{-- Judul hasil --}}
                <header class="border-b-2 border-brand-600 pb-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#848CA3]">Pencarian</p>
                    <h1 class="mt-1 text-xl font-extrabold text-[#171B28] sm:text-2xl">
                        @if ($q !== '')
                            &ldquo;{{ $q }}&rdquo;
                        @else
                            Semua berita
                        @endif
                    </h1>
                    <p class="mt-1 text-sm text-[#5B6378]">{{ number_format($results->total(), 0, ',', '.') }} berita ditemukan</p>
                </header>

                {{-- Toolbar: filter (modal) + urutan (dropdown) --}}
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <details class="search-dropdown relative">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg border bg-white px-3.5 py-2 text-sm font-semibold transition {{ $activeFilters ? 'border-brand-600 text-brand-700' : 'border-[#CDD3DF] text-[#3B4152] hover:bg-[#F1F3F7]' }} [&::-webkit-details-marker]:hidden">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18l-7 8.5V19l-4 2v-7.5L3 5z"/></svg>
                                Filter
                                <svg class="h-4 w-4 transition [details[open]_&]:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                                @if ($activeFilters)
                                    <span class="rounded-full bg-brand-600 px-1.5 text-[11px] font-bold leading-5 text-white">{{ $activeFilters }}</span>
                                @endif
                            </summary>
                            <form action="{{ route('public.cari') }}" method="GET"
                                  class="group absolute left-0 top-full z-30 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-[#E4E8EF] bg-white shadow-xl">
                                @if ($q !== '') <input type="hidden" name="q" value="{{ $q }}"> @endif
                                @if ($sort !== 'relevan') <input type="hidden" name="urut" value="{{ $sort }}"> @endif

                                <div class="space-y-5 p-4">
                                    <div>
                                        <label for="filter-kategori" class="text-xs font-bold uppercase tracking-wide text-[#848CA3]">Kategori</label>
                                        <select id="filter-kategori" name="kategori"
                                                class="mt-2 w-full rounded-lg border border-[#CDD3DF] bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                                            <option value="">Semua kategori</option>
                                            @foreach ($kategoriOptions as $opt)
                                                @if (count($opt['children']))
                                                    <optgroup label="{{ $opt['label'] }}">
                                                        <option value="{{ $opt['slug'] }}" @selected($kategori?->slug === $opt['slug'])>Semua {{ $opt['label'] }}</option>
                                                        @foreach ($opt['children'] as $child)
                                                            <option value="{{ $child['slug'] }}" @selected($kategori?->slug === $child['slug'])>{{ $child['label'] }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @else
                                                    <option value="{{ $opt['slug'] }}" @selected($kategori?->slug === $opt['slug'])>{{ $opt['label'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>

                                    <fieldset>
                                        <legend class="text-xs font-bold uppercase tracking-wide text-[#848CA3]">Waktu terbit</legend>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach (PublicController::SEARCH_TIMES as $value => $label)
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="waktu" value="{{ $value }}" @checked($time === $value) @if ($value === 'pilih') id="waktu-pilih" @endif class="peer sr-only">
                                                    <span class="inline-block rounded-full border border-[#CDD3DF] px-3.5 py-1.5 text-sm text-[#3B4152] transition hover:border-brand-500 peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="mt-3 hidden grid-cols-2 gap-3 group-has-[#waktu-pilih:checked]:grid">
                                            <label class="text-xs font-semibold text-[#5B6378]">Dari tanggal
                                                <input type="date" name="dari" value="{{ $time === 'pilih' ? $from?->format('Y-m-d') : '' }}" max="{{ now()->format('Y-m-d') }}"
                                                       class="mt-1 w-full rounded-lg border border-[#CDD3DF] px-2.5 py-2 text-sm text-[#171B28]">
                                            </label>
                                            <label class="text-xs font-semibold text-[#5B6378]">Sampai tanggal
                                                <input type="date" name="sampai" value="{{ $time === 'pilih' ? $to?->format('Y-m-d') : '' }}" max="{{ now()->format('Y-m-d') }}"
                                                       class="mt-1 w-full rounded-lg border border-[#CDD3DF] px-2.5 py-2 text-sm text-[#171B28]">
                                            </label>
                                        </div>
                                    </fieldset>
                                </div>

                                <div class="flex items-center justify-between gap-3 border-t border-[#E4E8EF] bg-[#F8F9FB] px-4 py-3">
                                    <a href="{{ $resetFilters }}" class="text-sm font-semibold text-[#5B6378] hover:text-brand-600">Reset</a>
                                    <button type="submit" class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700">Tampilkan hasil</button>
                                </div>
                            </form>
                        </details>

                        @if ($kategori)
                            <a href="{{ $linkWith(['kategori' => null]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700 hover:bg-brand-100" aria-label="Hapus filter kategori {{ $kategori->name }}">
                                {{ $kategori->name }} <span aria-hidden="true" class="text-sm leading-none">&times;</span>
                            </a>
                        @endif
                        @if ($time !== 'semua')
                            <a href="{{ $linkWith(['waktu' => null, 'dari' => null, 'sampai' => null]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700 hover:bg-brand-100" aria-label="Hapus filter waktu">
                                {{ $timeLabel }} <span aria-hidden="true" class="text-sm leading-none">&times;</span>
                            </a>
                        @endif
                    </div>

                    <details class="search-dropdown relative">
                        <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg px-2 py-2 text-sm text-[#5B6378] hover:bg-[#F1F3F7] [&::-webkit-details-marker]:hidden">
                            Urutkan:
                            <span class="font-semibold text-[#171B28]">{{ PublicController::SEARCH_SORTS[$sort] }}</span>
                            <svg class="h-4 w-4 transition [details[open]_&]:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                        </summary>
                        <ul class="absolute right-0 top-full z-30 mt-1 w-48 overflow-hidden rounded-lg border border-[#E4E8EF] bg-white py-1 shadow-lg" role="menu">
                            @foreach (PublicController::SEARCH_SORTS as $value => $label)
                                <li role="none">
                                    <a href="{{ $linkWith(['urut' => $value === 'relevan' ? null : $value]) }}" role="menuitem"
                                       class="flex items-center justify-between px-4 py-2 text-sm {{ $sort === $value ? 'bg-brand-50 font-semibold text-brand-700' : 'text-[#3B4152] hover:bg-[#F1F3F7]' }}">
                                        {{ $label }}
                                        @if ($sort === $value)
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                </div>

                {{-- Hasil --}}
                @if ($results->isEmpty())
                    <div class="mt-6 rounded-card border border-[#E4E8EF] bg-white px-6 py-10 text-center sm:py-12">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5M8.5 8.5l5 5m0-5l-5 5"/></svg>
                        </div>
                        <h2 class="mt-4 text-lg font-bold text-[#171B28]">Belum ada berita yang cocok</h2>
                        <p class="mx-auto mt-1 max-w-md text-sm text-[#5B6378]">
                            @if ($q !== '')
                                Kami tidak menemukan berita untuk &ldquo;<span class="font-semibold">{{ $q }}</span>&rdquo;{{ $activeFilters ? ' dengan filter yang dipilih' : '' }}.
                            @else
                                Tidak ada berita untuk filter yang dipilih.
                            @endif
                            Coba periksa ejaan atau gunakan kata yang lebih umum.
                        </p>
                        <div class="mt-5 flex flex-wrap justify-center gap-2">
                            <button type="button" data-focus-search class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Cari kata lain</button>
                            @if ($activeFilters)
                                <a href="{{ $resetFilters }}" class="rounded-lg border border-[#CDD3DF] px-5 py-2.5 text-sm font-semibold text-[#3B4152] transition hover:bg-[#F1F3F7]">Hapus filter</a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-4 space-y-4">
                        @foreach ($results as $item)
                            <x-public.article-card :article="$item" variant="row" />
                        @endforeach
                    </div>

                    @if ($results->hasPages())
                        <div class="mt-8">{{ $results->links() }}</div>
                    @endif
                @endif

                {{-- Rekomendasi --}}
                @if ($suggestions->isNotEmpty())
                    <section class="mt-10">
                        <h2 class="mb-4 border-b-2 border-brand-600 pb-2 text-lg font-bold text-[#171B28]">Berita Populer Lainnya</h2>
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach ($suggestions as $item)
                                <x-public.article-card :article="$item" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                <x-public.ad-slot placement="sidebar" height="h-64" :placeholder="false" />
                <x-public.follow-us />
            </aside>
        </div>
    </div>


    <script>
        (function () {
            // Dropdown filter & urutan: tutup saat klik di luar atau Esc
            document.addEventListener('click', function (e) {
                document.querySelectorAll('details.search-dropdown[open]').forEach(function (d) {
                    if (!d.contains(e.target)) d.removeAttribute('open');
                });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('details.search-dropdown[open]').forEach(function (d) { d.removeAttribute('open'); });
                }
            });

            // "Cari kata lain": fokus ke kotak pencarian di navbar (desktop atau panel HP)
            document.querySelectorAll('[data-focus-search]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var desktop = document.getElementById('header-search');
                    var mobile = document.getElementById('header-search-mobile');
                    var toggle = document.getElementById('mobile-search-toggle');
                    var target = desktop && desktop.offsetParent !== null ? desktop : mobile;
                    if (target === mobile && toggle) toggle.checked = true;
                    if (!target) return;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    target.focus({ preventScroll: true });
                    target.select();
                });
            });
        })();
    </script>
</x-layouts.public>
