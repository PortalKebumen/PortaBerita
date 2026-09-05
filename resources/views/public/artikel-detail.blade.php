<x-layouts.public :title="$article->title" :description="$article->excerpt">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-6 flex items-center gap-2 text-sm text-[#848CA3]">
            <a href="{{ route('public.beranda') }}" class="hover:text-brand-700">Beranda</a>
            <span>/</span>
            <a href="{{ route('public.kategori', $article->category->slug) }}" class="hover:text-brand-700">{{ $article->category->label }}</a>
            <span>/</span>
            <span class="truncate text-[#3B4152]">{{ $article->title }}</span>
        </nav>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <header>
                    <x-public.rubric-tag :label="$article->category->label" />
                    <h1 class="mt-3 text-2xl font-bold leading-tight text-[#171B28] sm:text-3xl">
                        {{ $article->title }}
                    </h1>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-y border-[#E4E8EF] py-4">
                        <a href="{{ route('public.byline', $article->author) }}" class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr($article->author->name, 0, 1)) }}
                            </div>
                            <div class="text-sm">
                                <p class="font-semibold text-[#171B28]">{{ $article->author->name }}</p>
                                <p class="text-[#848CA3]">{{ $article->published_at->translatedFormat('d F Y, H:i') }} WIB</p>
                            </div>
                        </a>

                        <div class="flex items-center gap-2">
                            @foreach (['Facebook', 'Twitter', 'WhatsApp', 'Salin tautan'] as $channel)
                                <button type="button" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-[#E4E8EF] text-[#6C7387] transition hover:border-brand-500 hover:text-brand-700" aria-label="Bagikan via {{ $channel }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68a3 3 0 110-2.68m0 2.68l6.64 3.98m-6.64-6.66l6.64-3.98m0 0a3 3 0 105.32-2.79 3 3 0 00-5.32 2.79zm0 10.64a3 3 0 105.32 2.79 3 3 0 00-5.32-2.79z"/>
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </header>

                <figure class="mt-6">
                    <div class="flex aspect-video w-full items-center justify-center rounded-card bg-[#F1F3F7] text-sm text-[#848CA3]">
                        Gambar utama artikel
                    </div>
                    <figcaption class="mt-2 text-xs text-[#848CA3]">Keterangan foto akan tampil di sini.</figcaption>
                </figure>

                <div class="mt-6 space-y-4 text-[15px] leading-relaxed text-[#171B28] [&_h2]:mt-8 [&_h2]:text-xl [&_h2]:font-bold [&_a]:text-brand-700 [&_a]:underline [&_img]:rounded-card">
                    {!! $article->body !!}
                </div>

                <x-public.ad-slot class="mt-8" />
            </div>

            <aside class="space-y-6 lg:col-span-4">
                <x-public.ad-slot height="h-60" />

                <div class="rounded-card border border-[#E4E8EF] bg-white p-5">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-[#171B28]">Artikel Terkait</h3>
                    <ul class="mt-4 space-y-4">
                        @forelse ($relatedArticles as $related)
                            {{-- akan diisi setelah modul Artikel tersedia --}}
                        @empty
                            <li class="text-sm text-[#848CA3]">Belum ada artikel terkait.</li>
                        @endforelse
                    </ul>
                </div>
            </aside>
        </div>

        <section class="mt-12">
            <div class="mb-4 flex items-center justify-between border-b border-[#E4E8EF] pb-3">
                <h2 class="text-lg font-bold text-[#171B28]">Berita Terkait</h2>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @for ($i = 0; $i < 4; $i++)
                    <div class="flex aspect-[4/3] items-center justify-center rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] text-center text-xs text-[#848CA3]">
                        Artikel
                    </div>
                @endfor
            </div>
        </section>
    </div>
</x-layouts.public>