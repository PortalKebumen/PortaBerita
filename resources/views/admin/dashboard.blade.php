<x-layouts.admin title="Dashboard">
    {{-- ===== Baris kartu statistik ===== --}}
    <div class="grid grid-cols-12 gap-5 sm:gap-6 mb-6">
        @can('articles.view')
        <div class="col-span-12 sm:col-span-6 lg:col-span-3 bg-white border border-[#E4E8EF] rounded-2xl px-5 py-5 shadow-sm">
            <div class="text-[12.5px] text-[#6C7387] font-semibold">
                {{ auth()->user()->can('articles.view-any') ? 'Total Artikel' : 'Artikel Saya' }}
            </div>
            <div class="font-body text-[30px] font-semibold mt-2">{{ number_format($articleStats['total']) }}</div>
            <div class="text-[11.5px] text-[#848CA3] mt-1.5">
                {{ number_format($articleStats['published']) }} terbit · {{ number_format($articleStats['draft']) }} draft
            </div>
        </div>

        <div class="col-span-12 sm:col-span-6 lg:col-span-3 bg-white border border-[#E4E8EF] rounded-2xl px-5 py-5 shadow-sm">
            @can('articles.approve')
                <div class="text-[12.5px] text-[#6C7387] font-semibold">Pending Review</div>
                <div class="font-body text-[30px] font-semibold mt-2 text-warning">{{ number_format($articleStats['submitted']) }}</div>
                <div class="text-[11.5px] text-[#848CA3] mt-1.5">Menunggu persetujuan redaktur</div>
            @else
                <div class="text-[12.5px] text-[#6C7387] font-semibold">Perlu Revisi</div>
                <div class="font-body text-[30px] font-semibold mt-2 text-warning">{{ number_format($articleStats['rejected']) }}</div>
                <div class="text-[11.5px] text-[#848CA3] mt-1.5">
                    {{ number_format($articleStats['submitted']) }} sedang direview
                </div>
            @endcan
        </div>
        @endcan

        @can('ads.view')
        <div class="col-span-12 sm:col-span-6 lg:col-span-3 bg-white border border-[#E4E8EF] rounded-2xl px-5 py-5 shadow-sm">
            <div class="text-[12.5px] text-[#6C7387] font-semibold">Iklan Aktif</div>
            <div class="font-body text-[30px] font-semibold mt-2 text-info">{{ $totalIklanAktif ?? 0 }}</div>
            <div class="text-[11.5px] text-[#848CA3] mt-1.5">Sedang tayang saat ini</div>
        </div>
        @endcan

        @can('users.view')
        <div class="col-span-12 sm:col-span-6 lg:col-span-3 bg-white border border-[#E4E8EF] rounded-2xl px-5 py-5 shadow-sm">
            <div class="text-[12.5px] text-[#6C7387] font-semibold">Total Pengguna</div>
            <div class="font-body text-[30px] font-semibold mt-2 text-success">{{ $totalPengguna }}</div>
            <div class="text-[11.5px] text-[#848CA3] mt-1.5">Akun terdaftar di sistem</div>
        </div>
        @endcan
    </div>

    <div class="grid grid-cols-12 gap-5 sm:gap-6">
        {{-- ===== Kolom kiri: Artikel Terbaru ===== --}}
        @can('articles.view')
        <div class="col-span-12 lg:col-span-8 bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
            <div class="flex justify-between items-center px-5 py-4 border-b border-[#E4E8EF]">
                <h2 class="text-[15px] font-bold">Artikel Terbaru</h2>
                <a href="{{ route('admin.artikel.index') }}" class="text-[12.5px] font-semibold text-brand-600">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-5 py-2.5 border-b border-[#E4E8EF]">Judul</th>
                            <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#E4E8EF] whitespace-nowrap">Penulis</th>
                            <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#E4E8EF] whitespace-nowrap">Status</th>
                            <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#E4E8EF] whitespace-nowrap">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentArticles as $article)
                            <tr class="border-b border-[#E4E8EF] last:border-0">
                                <td class="px-5 py-3">
                                    @can('update', $article)
                                        <a href="{{ route('admin.artikel.edit', $article) }}" class="text-[13px] font-semibold leading-snug hover:text-brand-600">{{ $article->title }}</a>
                                    @else
                                        <span class="text-[13px] font-semibold leading-snug">{{ $article->title }}</span>
                                    @endcan
                                </td>
                                <td class="px-3 py-3 text-[13px] whitespace-nowrap">{{ $article->author?->name ?? '-' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="badge-{{ $article->status->badge() }}">
                                        <span class="badge-dot"></span>{{ ucfirst($article->status->value) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 font-mono text-[12.5px] whitespace-nowrap">
                                    {{ ($article->published_at ?? $article->created_at)->format('d/m') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-[13px] text-[#848CA3]">
                                    Belum ada artikel.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endcan

        {{-- ===== Kolom kanan ===== --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-5 sm:gap-6">
            {{-- Antrean Review (redaktur) --}}
            @can('articles.approve')
            <div class="bg-white border border-[#E4E8EF] rounded-2xl shadow-sm">
                <div class="flex justify-between items-center px-5 py-4 border-b border-[#E4E8EF]">
                    <h2 class="text-[15px] font-bold">Antrean Review</h2>
                    <a href="{{ route('admin.artikel.index', ['status' => 'submitted']) }}" class="text-[12.5px] font-semibold text-brand-600">Lihat Semua</a>
                </div>
                @forelse ($pendingReview as $article)
                    <a href="{{ route('admin.artikel.edit', $article) }}" class="block px-5 py-3 hover:bg-[#F7F8FA] {{ !$loop->last ? 'border-b border-[#E4E8EF]' : '' }}">
                        <div class="text-[13px] font-semibold leading-snug truncate">{{ $article->title }}</div>
                        <div class="text-[11px] text-[#848CA3] mt-1">
                            {{ $article->author?->name ?? '-' }} · dikirim {{ $article->updated_at->diffForHumans() }}
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-[13px] text-[#848CA3]">
                        Tidak ada artikel yang menunggu review.
                    </div>
                @endforelse
            </div>
            @endcan

            {{-- Iklan Akan Berakhir --}}
            @can('ads.view')
            <div class="bg-white border border-[#E4E8EF] rounded-2xl shadow-sm">
                <div class="flex justify-between items-center px-5 py-4 border-b border-[#E4E8EF]">
                    <h2 class="text-[15px] font-bold">Iklan Akan Berakhir</h2>
                    <a href="{{ route('admin.iklan.index') }}" class="text-[12.5px] font-semibold text-brand-600">Kelola</a>
                </div>
                @if (isset($expiringAds) && $expiringAds->count() > 0)
                    <div class="divide-y divide-[#E4E8EF]">
                        @foreach ($expiringAds as $ad)
                            <div class="px-5 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-[13px] font-semibold text-gray-900 truncate">{{ $ad->advertiser_name }}</div>
                                    <div class="text-[11px] text-[#848CA3] capitalize">{{ $ad->placement }} · Berakhir: {{ $ad->end_date->translatedFormat('d M Y') }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">
                                    {{ $ad->end_date->diffForHumans() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-5 py-8 text-center text-[13px] text-[#848CA3]">
                        Tidak ada iklan yang akan berakhir dalam 7 hari ke depan.
                    </div>
                @endif
            </div>
            @endcan

            {{-- Aktivitas Terbaru --}}
            @can('activity-log.view')
            <div class="bg-white border border-[#E4E8EF] rounded-2xl shadow-sm">
                <div class="flex justify-between items-center px-5 py-4 border-b border-[#E4E8EF]">
                    <h2 class="text-[15px] font-bold">Aktivitas Terbaru</h2>
                    <a href="{{ route('admin.activity-log.index') }}" class="text-[12.5px] font-semibold text-brand-600">Lihat Log</a>
                </div>
                @forelse ($recentActivities as $activity)
                    <div class="flex justify-between items-start gap-2.5 px-5 py-3 {{ !$loop->last ? 'border-b border-[#E4E8EF]' : '' }}">
                        <div>
                            <div class="text-[12.5px] font-semibold leading-snug">{{ $activity->description }}</div>
                            <div class="text-[11px] text-[#848CA3] mt-1">{{ $activity->causer?->name ?? 'Sistem' }} · {{ $activity->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-[13px] text-[#848CA3]">
                        Belum ada aktivitas tercatat.
                    </div>
                @endforelse
            </div>
            @endcan
        </div>
    </div>
</x-layouts.admin>