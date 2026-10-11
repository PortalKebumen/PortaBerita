@php
    $seo = $article->seoMeta;
    $img = $article->getFirstMediaUrl('featured');
    $desc = $seo?->meta_description ?: ($article->excerpt ?: \Illuminate\Support\Str::limit(trim(strip_tags($article->content)), 155));
    $url = $article->publicUrl();
    $shareTitle = urlencode($article->title);
    $shareUrl = urlencode($url);
@endphp

<x-layouts.public
    :title="$seo?->meta_title ?: $article->title"
    :description="$desc"
    :image="$img ?: null"
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

                @if ($article->is_advertorial)
                    <span class="mt-3 inline-block rounded bg-[#E4E8EF] px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-[#3B4152]">Advertorial</span>
                @endif

                <h1 class="mt-3 text-2xl font-extrabold leading-tight text-[#171B28] sm:text-4xl">{{ $article->title }}</h1>

                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-[#5B6378]">
                    @if ($article->author)
                        <a href="{{ route('public.byline', $article->author) }}" class="font-semibold text-[#171B28] hover:text-brand-600">{{ $article->author->name }}</a>
                        <span>&middot;</span>
                    @endif
                    @if ($article->published_at)
                        <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->translatedFormat('l, d F Y, H:i') }} WIB</time>
                    @endif
                    <span>&middot;</span>
                    <span>{{ number_format($article->views_count, 0, ',', '.') }}x dibaca</span>
                </div>

                @if ($img)
                    <figure class="mt-5">
                        <img src="{{ $img }}" alt="{{ $article->title }}" class="w-full rounded-card object-cover">
                    </figure>
                @endif

                @if ($article->excerpt)
                    <p class="mt-5 text-lg font-medium leading-relaxed text-[#3B4152]">{{ $article->excerpt }}</p>
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

                {{-- Bagikan --}}
                <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-[#E4E8EF] pt-5 text-sm">
                    <span class="font-semibold text-[#3B4152]">Bagikan:</span>
                    <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-[#25D366] px-3.5 py-1.5 font-semibold text-white hover:brightness-95">WhatsApp</a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-[#1877F2] px-3.5 py-1.5 font-semibold text-white hover:brightness-95">Facebook</a>
                    <a href="https://twitter.com/intent/tweet?text={{ $shareTitle }}&url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-[#171B28] px-3.5 py-1.5 font-semibold text-white hover:brightness-95">X</a>
                </div>

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
                <div class="lg:sticky lg:top-28 space-y-6">
                    <x-public.ad-slot placement="sidebar" height="h-64" :placeholder="false" />
                </div>
            </aside>
        </div>
    </div>
</x-layouts.public>