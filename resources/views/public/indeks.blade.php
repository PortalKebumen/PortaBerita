@php
    $dateLabel = $date->translatedFormat('l, d F Y');
    $pageTitle = $isToday ? 'Indeks Berita Hari Ini' : 'Indeks Berita ' . $date->translatedFormat('d F Y');
    $description = 'Daftar semua berita PortalKebumen yang terbit pada ' . $dateLabel . '.';
    $prev = $date->copy()->subDay();
    $next = $date->copy()->addDay();
    $dateUrl = fn ($d) => $d->isToday() ? route('public.indeks') : route('public.indeks', ['tanggal' => $d->toDateString()]);
@endphp

{{-- Tanggal tanpa berita tidak diindeks --}}
<x-layouts.public :title="$pageTitle" :description="$description" :noindex="$articles->isEmpty()" :canonical="$dateUrl($date)">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                <header class="border-b-2 border-brand-600 pb-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#6C7387]">Indeks Berita</p>
                    <h1 class="mt-1 text-xl font-extrabold text-[#171B28] sm:text-2xl">{{ $dateLabel }}</h1>
                </header>

                {{-- Navigasi tanggal --}}
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <a href="{{ $dateUrl($prev) }}" class="rounded-lg border border-[#CDD3DF] bg-white px-3 py-2 text-sm font-semibold text-[#3B4152] hover:border-brand-600 hover:text-brand-600">&larr; {{ $prev->translatedFormat('d M') }}</a>

                    <form method="GET" action="{{ route('public.indeks') }}" class="flex items-center gap-2">
                        <label for="tanggal" class="sr-only">Pilih tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}"
                               class="rounded-lg border border-[#CDD3DF] bg-white px-3 py-2 text-sm text-[#171B28]">
                        <button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">Lihat</button>
                    </form>

                    @if ($isToday)
                        <span class="rounded-lg border border-[#E4E8EF] px-3 py-2 text-sm font-semibold text-[#9AA3B5]" aria-disabled="true">{{ $next->translatedFormat('d M') }} &rarr;</span>
                    @else
                        <a href="{{ $dateUrl($next) }}" class="rounded-lg border border-[#CDD3DF] bg-white px-3 py-2 text-sm font-semibold text-[#3B4152] hover:border-brand-600 hover:text-brand-600">{{ $next->translatedFormat('d M') }} &rarr;</a>
                    @endif
                </div>

                @if ($articles->isEmpty())
                    <div class="mt-6 rounded-card border border-dashed border-[#CDD3DF] bg-white px-6 py-16 text-center">
                        <p class="text-sm text-[#6C7387]">Belum ada berita yang terbit pada tanggal ini.</p>
                    </div>
                @else
                    <p class="mt-5 text-sm text-[#5B6378]">{{ $articles->count() }} berita</p>
                    <ol class="mt-2 divide-y divide-[#E4E8EF] rounded-card border border-[#E4E8EF] bg-white">
                        @foreach ($articles as $item)
                            <li class="flex gap-4 px-4 py-3">
                                <time datetime="{{ $item->published_at?->toIso8601String() }}" class="w-12 shrink-0 pt-0.5 font-mono text-sm font-semibold text-brand-600">{{ $item->published_at?->format('H:i') }}</time>
                                <div class="min-w-0">
                                    @if ($item->category)
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-[#6C7387]">{{ $item->category->name }}</span>
                                    @endif
                                    <a href="{{ $item->publicUrl() }}" class="block text-[15px] font-semibold leading-snug text-[#171B28] hover:text-brand-600">{{ $item->title }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <aside class="space-y-6">
                <x-public.ad-slot placement="sidebar" height="h-64" :placeholder="false" />

                @if ($popularArticles->isNotEmpty())
                    <section class="rounded-card border border-[#E4E8EF] bg-white p-4">
                        <h2 class="mb-3 border-b-2 border-brand-600 pb-2 text-base font-bold text-[#171B28]">Terpopuler</h2>
                        <ol class="space-y-3">
                            @foreach ($popularArticles as $item)
                                <li class="group flex gap-3">
                                    <span class="w-6 shrink-0 text-2xl font-extrabold leading-none text-[#CDD3DF] group-hover:text-brand-600">{{ $loop->iteration }}</span>
                                    <a href="{{ $item->publicUrl() }}" class="text-sm font-semibold leading-snug text-[#171B28] group-hover:text-brand-600">{{ $item->title }}</a>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <x-public.follow-us />
            </aside>
        </div>
    </div>
</x-layouts.public>
