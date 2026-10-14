<x-layouts.public>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        @if ($featured->isEmpty())
            <div class="rounded-card border border-dashed border-[#CDD3DF] bg-white p-10 text-center text-sm text-[#848CA3]">
                Belum ada artikel yang terbit.
            </div>
        @else
            {{-- Headline + sorotan --}}
            <section class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-public.article-card :article="$featured->first()" variant="hero" />
                </div>
                @if ($featured->count() > 1)
                    <aside class="rounded-card border border-[#E4E8EF] bg-white p-4">
                        <h2 class="mb-3 border-b-2 border-brand-600 pb-2 text-base font-bold text-[#171B28]">Sorotan</h2>
                        @foreach ($featured->slice(1) as $item)
                            <x-public.article-card :article="$item" variant="compact" />
                        @endforeach
                    </aside>
                @endif
            </section>

            <div class="mt-8 grid gap-8 lg:grid-cols-3">
                <div class="space-y-10 lg:col-span-2">

                    {{-- Terbaru --}}
                    @if ($latest->isNotEmpty())
                        <section>
                            <h2 class="border-b-2 border-brand-600 pb-2 text-lg font-bold text-[#171B28]">Berita Terbaru</h2>
                            <div class="mt-4 space-y-4">
                                @foreach ($latest as $item)
                                    <x-public.article-card :article="$item" variant="row" />
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Blok per kategori --}}
                    @foreach ($kategoriBlocks as $blok)
                        <section>
                            <div class="flex items-end justify-between border-b-2 border-brand-600 pb-2">
                                <h2 class="text-lg font-bold text-[#171B28]">{{ $blok['category']->name }}</h2>
                                <a href="{{ route('public.kategori', $blok['category']->slug) }}" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua &rarr;</a>
                            </div>
                            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                                @foreach ($blok['articles'] as $item)
                                    <x-public.article-card :article="$item" />
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                {{-- Sidebar --}}
                <aside class="space-y-6">
                    <x-public.ad-slot placement="sidebar" height="h-64" :placeholder="false" />

                    @if ($popular->isNotEmpty())
                        <section class="rounded-card border border-[#E4E8EF] bg-white p-4">
                            <h2 class="mb-3 border-b-2 border-brand-600 pb-2 text-base font-bold text-[#171B28]">Terpopuler</h2>
                            <ol class="space-y-3">
                                @foreach ($popular as $item)
                                    <li class="group flex gap-3">
                                        <span class="w-6 shrink-0 text-2xl font-extrabold leading-none text-[#CDD3DF] group-hover:text-brand-600">{{ $loop->iteration }}</span>
                                        <a href="{{ $item->publicUrl() }}" class="text-sm font-semibold leading-snug text-[#171B28] group-hover:text-brand-600">{{ $item->title }}</a>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif
                </aside>
            </div>
        @endif
    </div>
</x-layouts.public>