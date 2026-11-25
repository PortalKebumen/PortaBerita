@php
    $pageTitle = 'Berita ' . $tag->name . ($articles->currentPage() > 1 ? ' - Halaman ' . $articles->currentPage() : '');
    $description = 'Kumpulan berita terbaru tentang ' . $tag->name . ' di Kebumen dan sekitarnya.';
@endphp

{{-- Tag dengan kurang dari 2 artikel tidak diindeks (halaman tipis) --}}
<x-layouts.public :title="$pageTitle" :description="$description" :noindex="$articles->total() < 2"
    :canonical="$articles->onFirstPage() ? url()->current() : $articles->url($articles->currentPage())">
    @push('head')
        <x-seo.breadcrumb-schema :items="[
            ['name' => 'Beranda', 'url' => route('public.beranda')],
            ['name' => '#' . $tag->name, 'url' => route('public.tag', $tag->slug)],
        ]" />
    @endpush

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <nav class="flex flex-wrap items-center gap-1.5 text-xs text-[#6C7387]" aria-label="Breadcrumb">
            <a href="{{ route('public.beranda') }}" class="hover:text-brand-600">Beranda</a>
            <span>/</span>
            <span class="font-medium text-[#3B4152]">Tag</span>
        </nav>

        <header class="mt-3 flex flex-wrap items-end justify-between gap-2 border-b-2 border-brand-600 pb-3">
            <div>
                <h1 class="text-2xl font-extrabold text-[#171B28] sm:text-3xl">#{{ $tag->name }}</h1>
                <p class="mt-1 text-sm text-[#5B6378]">{{ $description }}</p>
            </div>
            @if ($articles->total() > 0)
                <span class="text-xs text-[#6C7387]">{{ number_format($articles->total(), 0, ',', '.') }} artikel</span>
            @endif
        </header>

        <div class="mt-6 grid gap-8 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                @if ($articles->isEmpty())
                    <div class="rounded-card border border-dashed border-[#CDD3DF] bg-white px-6 py-16 text-center">
                        <p class="text-sm text-[#6C7387]">Belum ada artikel dengan tag ini.</p>
                        <a href="{{ route('public.beranda') }}" class="mt-3 inline-block text-sm font-semibold text-brand-600 hover:underline">Kembali ke Beranda</a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($articles as $item)
                            <x-public.article-card :article="$item" variant="row" />
                        @endforeach
                    </div>

                    @if ($articles->hasPages())
                        <div class="mt-8">{{ $articles->links() }}</div>
                    @endif
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
