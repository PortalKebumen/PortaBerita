<x-layouts.public :title="$kategori['label']">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-6 flex items-center gap-2 text-sm text-[#848CA3]">
            <a href="{{ route('public.beranda') }}" class="hover:text-brand-700">Beranda</a>
            <span>/</span>
            <span class="text-[#3B4152]">{{ $kategori['label'] }}</span>
        </nav>

        <h1 class="text-2xl font-bold text-[#171B28]">{{ $kategori['label'] }}</h1>

        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($articles as $article)
                {{-- akan diisi setelah modul Artikel tersedia --}}
            @empty
                <div class="col-span-full flex flex-col items-center justify-center gap-2 rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] px-6 py-16 text-center">
                    <p class="text-sm text-[#848CA3]">Belum ada artikel pada kategori ini.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.public>