@php
    $description = $page->meta_description
        ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $page->content))), 155);
@endphp

<x-layouts.public :title="$page->meta_title ?: $page->title" :description="$description" :canonical="$page->url()" :noindex="$isPreview">
    @if ($isPreview)
        <div class="bg-amber-100 px-4 py-2 text-center text-sm font-semibold text-amber-900">
            Pratinjau: halaman ini masih draft dan hanya terlihat oleh pengelola.
        </div>
    @endif

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <article class="min-w-0 lg:col-span-2">
                <nav class="flex flex-wrap items-center gap-1.5 text-xs text-[#848CA3]" aria-label="Breadcrumb">
                    <a href="{{ route('public.beranda') }}" class="hover:text-brand-600">Beranda</a>
                    <span>/</span>
                    <span class="font-medium text-[#3B4152]">{{ $page->title }}</span>
                </nav>

                <div class="mt-3 rounded-card border border-[#E4E8EF] bg-white p-5 sm:p-8">
                    <h1 class="text-2xl font-extrabold leading-tight text-[#171B28] sm:text-3xl">{{ $page->title }}</h1>
                    <p class="mt-2 text-xs text-[#848CA3]">Diperbarui {{ $page->updated_at->translatedFormat('d F Y') }}</p>
                    <span class="mt-4 block h-1 w-12 rounded bg-accent-500"></span>

                    <div class="mt-6 text-[16px] leading-8 text-[#2A3040]
                        [&_p]:mb-5 [&_h2]:mb-3 [&_h2]:mt-8 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-[#171B28]
                        [&_h3]:mb-2 [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-bold [&_h3]:text-[#171B28]
                        [&_a]:text-brand-600 [&_a]:underline
                        [&_ul]:mb-5 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-5 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:mb-1.5
                        [&_img]:my-5 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-lg
                        [&_blockquote]:my-5 [&_blockquote]:border-l-4 [&_blockquote]:border-brand-500 [&_blockquote]:pl-4 [&_blockquote]:italic
                        [&_table]:my-5 [&_table]:w-full [&_td]:border [&_td]:border-[#E4E8EF] [&_td]:px-3 [&_td]:py-2 [&_th]:border [&_th]:border-[#E4E8EF] [&_th]:bg-[#F1F3F7] [&_th]:px-3 [&_th]:py-2 [&_th]:text-left">
                        {!! $page->content !!}
                    </div>
                </div>
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

                <x-public.follow-us />
            </aside>
        </div>
    </div>
</x-layouts.public>
