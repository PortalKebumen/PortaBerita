@php
    $seo = $article->seoMeta;
    $featured = $article->getFirstMedia('featured');
    $img = $featured ? $featured->getUrl() : null;
    // Pratinjau tautan media sosial: JPEG 1200x630 (gambar OG khusus bila diisi, kalau tidak gambar utama)
    $ogMedia = $article->getFirstMedia('og') ?? $featured;
    $socialReady = $ogMedia && $ogMedia->hasGeneratedConversion('social');
    $socialImage = $ogMedia ? ($socialReady ? $ogMedia->getUrl('social') : $ogMedia->getUrl()) : null;
    $imgAlt = $featured?->getCustomProperty('alt_text') ?: $article->title;
    $imgCaption = $featured?->getCustomProperty('caption');
    $desc = $seo?->meta_description ?: ($article->excerpt ?: \Illuminate\Support\Str::limit(trim(strip_tags($article->content)), 155));
    $url = $article->publicUrl();
@endphp

<x-layouts.public
    :title="$seo?->meta_title ?: $article->title"
    :description="$desc"
    :image="$socialImage"
    :image-width="$socialReady ? 1200 : null"
    :image-height="$socialReady ? 630 : null"
    :image-alt="$imgAlt"
    :published-time="$article->published_at?->toIso8601String()"
    :modified-time="$article->updated_at?->toIso8601String()"
    :section="$article->category?->name"
    :author="$article->author?->name"
    type="article"
    :canonical="$url"
    :noindex="$isPreview || (bool) $seo?->noindex">

    @push('head')
        @unless ($isPreview)
            <x-seo.news-article-schema :article="$article" />
        @endunless
    @endpush

    @if ($isPreview)
        <div class="bg-amber-100 px-4 py-2 text-center text-sm font-semibold text-amber-900">
            Pratinjau: artikel ini belum terbit dan hanya terlihat oleh redaksi.
        </div>
    @endif

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">

            <article class="lg:col-span-2">
                {{-- Breadcrumb --}}
                <nav class="flex flex-wrap items-center gap-1.5 text-xs text-[#848CA3]" aria-label="Breadcrumb">
                    <a href="{{ route('public.beranda') }}" class="hover:text-brand-600">Beranda</a>
                    @if ($article->category?->parent)
                        <span>/</span>
                        <a href="{{ route('public.kategori', $article->category->parent->slug) }}" class="hover:text-brand-600">{{ $article->category->parent->name }}</a>
                        <span>/</span>
                        <a href="{{ route('public.kategori.sub', [$article->category->parent->slug, $article->category->slug]) }}" class="hover:text-brand-600">{{ $article->category->name }}</a>
                    @elseif ($article->category)
                        <span>/</span>
                        <a href="{{ route('public.kategori', $article->category->slug) }}" class="hover:text-brand-600">{{ $article->category->name }}</a>
                    @endif
                </nav>

                @if ($article->is_breaking)
                    <span class="mt-3 inline-block rounded bg-accent-500 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">Breaking News</span>
                @endif
                @if ($article->is_advertorial)
                    <span class="mt-3 inline-block rounded bg-[#E4E8EF] px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-[#3B4152]">Advertorial</span>
                @endif

                <h1 class="mt-3 text-2xl font-extrabold leading-tight text-[#171B28] sm:text-4xl">{{ $article->title }}</h1>

                {{-- Byline + bagikan (atas) --}}
                <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-y border-[#E4E8EF] py-3">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($article->author)
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700" aria-hidden="true">
                                {{ \Illuminate\Support\Str::of($article->author->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
                            </span>
                        @endif
                        <div class="min-w-0 text-[13px] leading-snug text-[#5B6378]">
                            @if ($article->author)
                                <a href="{{ $article->author->profileUrl() }}" class="block font-semibold text-[#171B28] hover:text-brand-600">{{ $article->author->name }}</a>
                            @endif
                            <span class="flex flex-wrap items-center gap-x-1.5">
                                @if ($article->published_at)
                                    <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->translatedFormat('l, d F Y, H:i') }} WIB</time>
                                    <span aria-hidden="true">&middot;</span>
                                @endif
                                <span>{{ number_format($article->views_count, 0, ',', '.') }}x dibaca</span>
                            </span>
                        </div>
                    </div>

                    @unless ($isPreview)
                        <x-public.share-buttons :url="$url" :title="$article->title" />
                    @endunless
                </div>

                @if ($img)
                    <figure class="mt-5">
                        <x-responsive-image :media="$featured" :alt="$imgAlt" priority sizes="(min-width: 1024px) 800px, 100vw"
                            class="aspect-[16/9] w-full rounded-card object-cover" />
                        @if ($imgCaption)
                            <figcaption class="mt-2 text-[13px] leading-snug text-[#5B6378]">{{ $imgCaption }}</figcaption>
                        @endif
                    </figure>
                @endif

                <div class="mt-6 text-[17px] leading-8 text-[#2A3040]
                    [&_p]:mb-5 [&_h2]:mb-3 [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-bold
                    [&_h3]:mb-2 [&_h3]:mt-6 [&_h3]:text-xl [&_h3]:font-bold
                    [&_a]:text-brand-600 [&_a]:underline
                    [&_ul]:mb-5 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-5 [&_ol]:list-decimal [&_ol]:pl-6
                    [&_img]:my-5 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-lg
                    [&_blockquote]:my-5 [&_blockquote]:border-l-4 [&_blockquote]:border-brand-500 [&_blockquote]:pl-4 [&_blockquote]:italic
                    [&_figure]:my-5 [&_figcaption]:mt-1 [&_figcaption]:text-sm [&_figcaption]:text-[#848CA3]">
                    {!! $article->content !!}
                </div>

                <x-public.ad-slot placement="inline_artikel" height="h-28" :placeholder="false" class="my-6" />

                {{-- Bagikan (bawah) --}}
                @unless ($isPreview)
                    <x-public.share-buttons :url="$url" :title="$article->title" class="mt-6 border-t border-[#E4E8EF] pt-5" />
                @endunless

                {{-- Terkait --}}
                @if ($relatedArticles->isNotEmpty())
                    <section class="mt-10">
                        <h2 class="border-b-2 border-brand-600 pb-2 text-lg font-bold text-[#171B28]">Berita Terkait</h2>
                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            @foreach ($relatedArticles as $item)
                                <x-public.article-card :article="$item" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </article>

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

                @if ($latestArticles->isNotEmpty())
                    <section class="rounded-card border border-[#E4E8EF] bg-white p-4">
                        <h2 class="mb-3 border-b-2 border-brand-600 pb-2 text-base font-bold text-[#171B28]">Berita Terbaru</h2>
                        @foreach ($latestArticles as $item)
                            <x-public.article-card :article="$item" variant="compact" />
                        @endforeach
                    </section>
                @endif

                <x-public.follow-us />

                <x-public.sticky-ad />
            </aside>
        </div>
    </div>
</x-layouts.public>