<x-layouts.public title="Beranda">
    <div class="bg-accent-600 text-white">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2 text-sm sm:px-6 lg:px-8">
            <span class="shrink-0 rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide">Terkini</span>
            <p class="truncate">Belum ada berita terbaru.</p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

            <div class="lg:col-span-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <div class="flex flex-col items-center justify-center gap-3 rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] px-6 py-16 text-center">
                            <x-public.rubric-tag label="Berita Utama" />
                            <p class="max-w-sm text-sm text-[#848CA3]">Belum ada artikel yang dipublikasikan. Artikel utama akan tampil di sini setelah modul Artikel tersedia.</p>
                        </div>
                    </div>
                    @for ($i = 0; $i < 2; $i++)
                        <div class="flex flex-col items-center justify-center gap-2 rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] px-4 py-10 text-center">
                            <p class="text-sm text-[#848CA3]">Artikel akan tampil di sini.</p>
                        </div>
                    @endfor
                </div>

                <x-public.ad-slot class="mt-8" />

                <section class="mt-10">
                    <div class="mb-4 flex items-center justify-between border-b border-[#E4E8EF] pb-3">
                        <h2 class="text-lg font-bold text-[#171B28]">Potensi Daerah</h2>
                        <a href="{{ route('public.kategori', 'potensi-daerah') }}" class="text-sm font-medium text-brand-700 hover:underline">Lihat semua</a>
                    </div>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="flex aspect-[4/3] items-center justify-center rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] text-center text-xs text-[#848CA3]">
                                Artikel
                            </div>
                        @endfor
                    </div>
                </section>

                <section class="mt-10">
                    <div class="mb-4 flex items-center justify-between border-b border-[#E4E8EF] pb-3">
                        <h2 class="text-lg font-bold text-[#171B28]">Wisata &amp; Budaya</h2>
                        <a href="{{ route('public.kategori', 'wisata-budaya') }}" class="text-sm font-medium text-brand-700 hover:underline">Lihat semua</a>
                    </div>
                    <div class="space-y-4">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="flex items-center gap-4 rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] p-4 text-sm text-[#848CA3]">
                                <div class="h-16 w-24 shrink-0 rounded-md bg-[#E4E8EF]"></div>
                                <span>Artikel akan tampil di sini.</span>
                            </div>
                        @endfor
                    </div>
                </section>
            </div>

            <aside class="space-y-6 lg:col-span-4">
                <x-public.ad-slot height="h-60" />

                <div class="rounded-card border border-[#E4E8EF] bg-white p-5">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-[#171B28]">Artikel Populer</h3>
                    <ol class="mt-4 space-y-4">
                        @forelse ($popularArticles as $item)
                            {{-- akan diisi setelah modul Artikel tersedia --}}
                        @empty
                            <li class="text-sm text-[#848CA3]">Belum ada data artikel populer.</li>
                        @endforelse
                    </ol>
                </div>

                <x-public.ad-slot height="h-60" />
            </aside>
        </div>
    </div>
</x-layouts.public>