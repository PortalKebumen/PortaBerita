<x-layouts.public :title="$penulis->name" :description="$penulis->bio">
    <div class="bg-brand-900">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:text-left">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-white/10 text-2xl font-bold text-white ring-4 ring-white/10">
                    {{ strtoupper(substr($penulis->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white sm:text-2xl">{{ $penulis->name }}</h1>
                    <p class="mt-1 text-sm text-brand-300">
                        {{ $penulis->getRoleNames()->first() ?? 'Jurnalis' }} — PortalKebumen.com
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="rounded-card border border-[#E4E8EF] bg-white p-5">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-[#171B28]">Tentang</h2>
                    <p class="mt-3 text-sm leading-relaxed text-[#6C7387]">
                        {{ $penulis->bio ?: 'Penulis belum menambahkan bio.' }}
                    </p>
                    <dl class="mt-5 space-y-2 border-t border-[#E4E8EF] pt-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-[#848CA3]">Total Artikel</dt>
                            <dd class="font-semibold text-[#171B28]">{{ count($articles) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#848CA3]">Bergabung</dt>
                            <dd class="font-semibold text-[#171B28]">{{ $penulis->created_at->translatedFormat('F Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="mb-4 flex items-center justify-between border-b border-[#E4E8EF] pb-3">
                    <h2 class="text-lg font-bold text-[#171B28]">Artikel oleh {{ $penulis->name }}</h2>
                </div>

                @forelse ($articles as $article)
                    {{-- akan diisi setelah modul Artikel tersedia --}}
                @empty
                    <div class="flex flex-col items-center justify-center gap-2 rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] px-6 py-16 text-center">
                        <p class="text-sm text-[#848CA3]">Penulis ini belum memiliki artikel yang dipublikasikan.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.public>