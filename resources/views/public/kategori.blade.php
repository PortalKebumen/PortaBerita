<x-layouts.public :title="$kategori['label']">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-6 flex items-center gap-2 text-sm text-[#848CA3] flex-wrap">
            <a href="{{ route('public.beranda') }}" class="hover:text-brand-700">Beranda</a>
            <span>/</span>
            @if (!empty($kategori['parent']))
                <a href="{{ route('public.kategori', $kategori['parent']['slug']) }}" class="hover:text-brand-700">
                    {{ $kategori['parent']['label'] }}
                </a>
                <span>/</span>
            @endif
            <span class="text-[#3B4152] font-medium">{{ $kategori['label'] }}</span>
        </nav>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-[#CDD3DF] pb-4">
            <div>
                <h1 class="text-2xl font-bold text-[#171B28]">{{ $kategori['label'] }}</h1>
                @if (!empty($kategori['parent']))
                    <p class="text-xs text-[#848CA3] mt-1">Sub-kategori dari <span class="font-semibold text-brand-700">{{ $kategori['parent']['label'] }}</span></p>
                @endif
            </div>

            {{-- Jika ini kategori utama dan memiliki sub-kategori, tampilkan daftar sub-kategori --}}
            @if (isset($category) && $category->children && $category->children->count())
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-[#848CA3]">Sub:</span>
                    @foreach ($category->children as $child)
                        <a href="{{ route('public.kategori.sub', ['parent' => $category->slug, 'sub' => $child->slug]) }}"
                           class="inline-block rounded-full bg-[#E4E8EF] px-3 py-1 text-xs font-semibold text-[#3B4152] transition hover:bg-brand-600 hover:text-white">
                            {{ $child->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($articles as $article)
                <div class="rounded-card border border-[#E4E8EF] bg-white p-4 shadow-sm">
                    <h3 class="font-bold text-sm text-[#171B28] mb-2">{{ $article->title }}</h3>
                    <p class="text-xs text-[#848CA3] line-clamp-2">{{ $article->excerpt }}</p>
                </div>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center gap-2 rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] px-6 py-16 text-center">
                    <p class="text-sm text-[#848CA3]">Belum ada artikel pada kategori ini.</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($articles, 'hasPages') && $articles->hasPages())
            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>