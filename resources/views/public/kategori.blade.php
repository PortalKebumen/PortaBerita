@php
    $parent = $category->parent;
    $isFirstPage = $articles->onFirstPage();

    // Halaman 1: 1 headline + 2 sorotan, sisanya daftar. Halaman berikutnya: daftar saja.
    $items = collect($articles->items());
    $headline = $isFirstPage ? $items->first() : null;
    $highlights = $isFirstPage ? $items->slice(1, 2) : collect();
    $listItems = $isFirstPage ? $items->slice(3) : $items;

    $pageTitle = $category->name . ($articles->currentPage() > 1 ? ' - Halaman ' . $articles->currentPage() : '');
    $description = 'Kumpulan berita terkini seputar ' . $category->name
        . ($parent ? ' (' . $parent->name . ')' : '') . ' di Kebumen dan sekitarnya.';
@endphp

<x-layouts.public :title="$pageTitle" :description="$description" :canonical="$articles->onFirstPage() ? url()->current() : $articles->url($articles->currentPage())">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="flex flex-wrap items-center gap-1.5 text-xs text-[#848CA3]" aria-label="Breadcrumb">
            <a href="{{ route('public.beranda') }}" class="hover:text-brand-600">Beranda</a>
            @if ($parent)
                <span>/</span>
                <a href="{{ route('public.kategori', $parent->slug) }}" class="hover:text-brand-600">{{ $parent->name }}</a>
            @endif
            <span>/</span>
            <span class="font-medium text-[#3B4152]">{{ $category->name }}</span>
        </nav>

        {{-- Judul kategori + tab sub-kategori --}}
        <header class="mt-3 border-b-2 border-brand-600">
            <div class="flex flex-wrap items-end justify-between gap-2 pb-3">
                <div>
                    <h1 class="text-2xl font-extrabold text-[#171B28] sm:text-3xl">{{ $category->name }}</h1>
                    <p class="mt-1 text-sm text-[#5B6378]">{{ $description }}</p>
                </div>
                @if ($articles->total() > 0)
                    <span class="text-xs text-[#848CA3]">{{ number_format($articles->total(), 0, ',', '.') }} artikel</span>
                @endif
            </div>

            @if ($subCategories->isNotEmpty())
                <div class="-mb-0.5 flex gap-1 overflow-x-auto whitespace-nowrap text-sm [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @php $isRoot = $category->id === $rootCategory->id; @endphp
                    <a href="{{ route('public.kategori', $rootCategory->slug) }}"
                       @if ($isRoot) aria-current="page" @endif
                       class="shrink-0 border-b-2 px-3 py-2 font-semibold transition {{ $isRoot ? 'border-accent-500 text-brand-700' : 'border-transparent text-[#5B6378] hover:text-brand-600' }}">
                        Semua
                    </a>
                    @foreach ($subCategories as $sub)
                        @php $active = $sub->id === $category->id; @endphp
                        <a href="{{ route('public.kategori.sub', ['parent' => $rootCategory->slug, 'sub' => $sub->slug]) }}"
                           @if ($active) aria-current="page" @endif
                           class="shrink-0 border-b-2 px-3 py-2 font-semibold transition {{ $active ? 'border-accent-500 text-brand-700' : 'border-transparent text-[#5B6378] hover:text-brand-600' }}">
                            {{ $sub->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        <div class="mt-6 grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @if ($items->isEmpty())
                    <div class="rounded-card border border-dashed border-[#CDD3DF] bg-white px-6 py-16 text-center">
                        <p class="text-sm text-[#848CA3]">Belum ada artikel pada kategori ini.</p>
                        <a href="{{ route('public.beranda') }}" class="mt-3 inline-block text-sm font-semibold text-brand-600 hover:underline">Kembali ke Beranda</a>
                    </div>
                @else
                    @if ($headline)
                        <x-public.article-card :article="$headline" variant="hero" />
                    @endif

                    @if ($highlights->isNotEmpty())
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            @foreach ($highlights as $item)
                                <x-public.article-card :article="$item" />
                            @endforeach
                        </div>
                    @endif

                    @if ($listItems->isNotEmpty())
                        <section class="{{ $isFirstPage ? 'mt-8' : '' }}">
                            @if ($isFirstPage)
                                <h2 class="mb-4 border-b-2 border-brand-600 pb-2 text-lg font-bold text-[#171B28]">Berita Lainnya</h2>
                            @endif
                            <div class="space-y-4">
                                @foreach ($listItems as $item)
                                    <x-public.article-card :article="$item" variant="row" />
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($articles->hasPages())
                        <div class="mt-8">{{ $articles->links() }}</div>
                    @endif
                @endif
            </div>

            <aside class="space-y-6">
                <x-public.ad-slot placement="sidebar" height="h-64" :placeholder="false" />

                @if ($popularArticles->isNotEmpty())
                    <section class="rounded-card border border-[#E4E8EF] bg-white p-4">
                        <h2 class="mb-3 border-b-2 border-brand-600 pb-2 text-base font-bold text-[#171B28]">Terpopuler di {{ $category->name }}</h2>
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

                <x-public.sticky-ad />
            </aside>
        </div>
    </div>
</x-layouts.public>
