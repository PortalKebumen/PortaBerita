@props(['article', 'variant' => 'card'])

@php
    $img = $article->getFirstMediaUrl('featured', 'medium');
    $url = $article->publicUrl();
    $catName = $article->category?->name;
    $time = $article->published_at?->translatedFormat('d M Y, H:i');
@endphp

@if ($variant === 'hero')
    <article class="group relative overflow-hidden rounded-card bg-brand-900">
        <a href="{{ $url }}" class="block">
            @if ($img)
                <img src="{{ $img }}" alt="{{ $article->title }}" class="aspect-[16/9] w-full object-cover transition duration-300 group-hover:scale-[1.02]">
            @else
                <div class="aspect-[16/9] w-full bg-gradient-to-br from-brand-900 to-brand-600"></div>
            @endif
            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/50 to-transparent p-5 pt-16 sm:p-7 sm:pt-24">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($catName)
                        <span class="rounded bg-accent-500 px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wide text-white">{{ $catName }}</span>
                    @endif
                    @if ($article->is_advertorial)
                        <span class="rounded bg-white/90 px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wide text-[#3B4152]">Advertorial</span>
                    @endif
                </div>
                <h2 class="mt-2 text-xl font-extrabold leading-tight text-white sm:text-3xl">{{ $article->title }}</h2>
                @if ($time)
                    <p class="mt-2 text-xs text-white/75">{{ $time }}</p>
                @endif
            </div>
        </a>
    </article>

@elseif ($variant === 'row')
    <article class="group flex gap-4 rounded-card border border-[#E4E8EF] bg-white p-3">
        <a href="{{ $url }}" class="block w-32 shrink-0 overflow-hidden rounded-md sm:w-48">
            @if ($img)
                <img src="{{ $img }}" alt="{{ $article->title }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-[1.04]">
            @else
                <div class="aspect-[4/3] w-full bg-gradient-to-br from-brand-900 to-brand-600"></div>
            @endif
        </a>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-wide">
                @if ($catName)
                    <span class="text-brand-600">{{ $catName }}</span>
                @endif
                @if ($article->is_advertorial)
                    <span class="rounded bg-[#F1F3F7] px-1.5 py-0.5 text-[#3B4152]">Advertorial</span>
                @endif
            </div>
            <h3 class="mt-1 text-[15px] font-bold leading-snug text-[#171B28] group-hover:text-brand-600 sm:text-base">
                <a href="{{ $url }}">{{ $article->title }}</a>
            </h3>
            @if ($article->excerpt)
                <p class="mt-1 hidden text-sm text-[#5B6378] sm:line-clamp-2 sm:block">{{ $article->excerpt }}</p>
            @endif
            <p class="mt-2 text-xs text-[#848CA3]">
                {{ $article->author?->name }}@if ($article->author && $time) &middot; @endif{{ $time }}
            </p>
        </div>
    </article>

@elseif ($variant === 'compact')
    <article class="group flex gap-3 border-b border-[#E4E8EF] py-3 last:border-0 last:pb-0 first:pt-0">
        <div class="min-w-0 flex-1">
            @if ($catName)
                <span class="text-[10.5px] font-bold uppercase tracking-wide text-brand-600">{{ $catName }}</span>
            @endif
            <h3 class="mt-0.5 text-sm font-semibold leading-snug text-[#171B28] group-hover:text-brand-600">
                <a href="{{ $url }}">{{ $article->title }}</a>
            </h3>
            @if ($time)
                <p class="mt-1 text-[11px] text-[#848CA3]">{{ $time }}</p>
            @endif
        </div>
        @if ($img)
            <a href="{{ $url }}" class="block h-16 w-20 shrink-0 overflow-hidden rounded-md">
                <img src="{{ $img }}" alt="" loading="lazy" class="h-full w-full object-cover">
            </a>
        @endif
    </article>

@else
    <article class="group overflow-hidden rounded-card border border-[#E4E8EF] bg-white">
        <a href="{{ $url }}" class="block overflow-hidden">
            @if ($img)
                <img src="{{ $img }}" alt="{{ $article->title }}" loading="lazy" class="aspect-[16/10] w-full object-cover transition duration-300 group-hover:scale-[1.04]">
            @else
                <div class="aspect-[16/10] w-full bg-gradient-to-br from-brand-900 to-brand-600"></div>
            @endif
        </a>
        <div class="p-4">
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-wide">
                @if ($catName)
                    <span class="text-brand-600">{{ $catName }}</span>
                @endif
                @if ($article->is_advertorial)
                    <span class="rounded bg-[#F1F3F7] px-1.5 py-0.5 text-[#3B4152]">Advertorial</span>
                @endif
            </div>
            <h3 class="mt-1 text-[15px] font-bold leading-snug text-[#171B28] group-hover:text-brand-600">
                <a href="{{ $url }}">{{ $article->title }}</a>
            </h3>
            @if ($time)
                <p class="mt-2 text-xs text-[#848CA3]">{{ $time }}</p>
            @endif
        </div>
    </article>
@endif