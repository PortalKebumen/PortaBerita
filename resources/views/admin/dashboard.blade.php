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

    {{-- ===== Ringkasan analitik 14 hari ===== --}}
    @if ($contentAnalytics)
        @php $ca = $contentAnalytics; @endphp
        <div class="grid grid-cols-12 gap-5 sm:gap-6 mb-6">
            <div class="col-span-12 lg:col-span-8 bg-white border border-[#E4E8EF] rounded-2xl shadow-sm p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-[15px] font-bold">{{ $ca['own'] ? 'Tayangan Artikel Saya' : 'Tayangan Situs' }} · 14 Hari Terakhir</h2>
                        <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="font-body text-[26px] font-semibold leading-none">{{ number_format($ca['summary']['views'], 0, ',', '.') }}</span>
                            <x-admin.trend-badge :change="$ca['summary']['change']" suffix="vs 14 hari sebelumnya" />
                        </div>
                    </div>
                    @can('view-analytics')
                        <a href="{{ route('admin.analitik.index') }}" class="text-[12.5px] font-semibold text-brand-600 whitespace-nowrap">Laporan Lengkap &rarr;</a>
                    @endcan
                </div>
                <x-admin.bar-chart :data="$ca['daily']" unit="tayangan" height="h-64" />
            </div>

            <div class="col-span-12 lg:col-span-4 bg-white border border-[#E4E8EF] rounded-2xl shadow-sm">
                <div class="px-5 py-4 border-b border-[#E4E8EF]">
                    <h2 class="text-[15px] font-bold">{{ $ca['own'] ? 'Artikel Saya Terpopuler' : 'Terpopuler' }} · 14 Hari</h2>
                </div>
                @forelse ($ca['top'] as $item)
                    <a href="{{ url('/artikel/' . $item->slug) }}" target="_blank" class="flex items-start gap-3 px-5 py-3 hover:bg-[#F7F8FA] {{ ! $loop->last ? 'border-b border-[#E4E8EF]' : '' }}">
                        <span class="w-5 shrink-0 text-[18px] font-extrabold leading-none text-[#CDD3DF]">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-[13px] font-semibold leading-snug line-clamp-2">{{ $item->title }}</div>
                            <div class="text-[11px] text-[#848CA3] mt-1">{{ number_format($item->views, 0, ',', '.') }} tayangan @unless ($ca['own']) · {{ $item->author_name ?? '-' }} @endunless</div>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-[13px] text-[#848CA3]">Belum ada tayangan dalam 14 hari terakhir.</div>
                @endforelse
            </div>
        </div>
    @endif

    @if (! $contentAnalytics && $adsAnalytics)
        <div class="bg-white border border-[#E4E8EF] rounded-2xl shadow-sm p-5 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-[15px] font-bold">Impresi Iklan · 14 Hari Terakhir</h2>
                    <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="font-body text-[26px] font-semibold leading-none">{{ number_format($adsAnalytics['summary']['impressions'], 0, ',', '.') }}</span>
                        <x-admin.trend-badge :change="$adsAnalytics['summary']['impressions_change']" suffix="vs 14 hari sebelumnya" />
                    </div>
                </div>
                <a href="{{ route('admin.analitik.index', ['tab' => 'iklan']) }}" class="text-[12.5px] font-semibold text-brand-600 whitespace-nowrap">Laporan Lengkap &rarr;</a>
            </div>
            <x-admin.bar-chart :data="$adsAnalytics['daily']" unit="impresi" height="h-48" />
        </div>
    @endif

    <div class="grid grid-cols-12 gap-5 sm:gap-6">
        {{-- ===== Kolom kiri: Artikel Terbaru ===== --}}
        @can('articles.view')
        <div class="col-span-12 lg:col-span-8 bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
            <div class="flex justify-between items-center px-5 py-4 border-b border-[#E4E8EF]">
                <h2 class="text-[15px] font-bold">Artikel Terbaru</h2>
                <a href="{{ route('admin.artikel.index') }}" class="text-[12.5px] font-semibold text-brand-600">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full xl:min-w-[560px] border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-5 py-2.5 border-b border-[#E4E8EF]">Judul</th>
                            <th class="max-xl:hidden text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#E4E8EF] whitespace-nowrap">Penulis</th>
                            <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#E4E8EF] whitespace-nowrap">Status</th>
                            <th class="max-xl:hidden text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#E4E8EF] whitespace-nowrap">Tanggal</th>
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
                                    <div class="xl:hidden mt-1 text-[11.5px] text-[#848CA3]">{{ $article->author?->name ?? '-' }} · {{ ($article->published_at ?? $article->created_at)->format('d/m') }}</div>
                                </td>
                                <td class="max-xl:hidden px-3 py-3 text-[13px] whitespace-nowrap">{{ $article->author?->name ?? '-' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="badge-{{ $article->status->badge() }}">
                                        <span class="badge-dot"></span>{{ ucfirst($article->status->value) }}
                                    </span>
                                </td>
                                <td class="max-xl:hidden px-3 py-3 font-mono text-[12.5px] whitespace-nowrap">
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
        {{-- Tanpa kolom artikel (mis. Ads Manager), kartu kanan dibentangkan jadi grid selebar penuh --}}
        <div @class(['col-span-12 gap-5 sm:gap-6', 'lg:col-span-4 flex flex-col' => auth()->user()->can('articles.view'), 'grid lg:grid-cols-2 items-start' => ! auth()->user()->can('articles.view')])>
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

            {{-- Performa Iklan 14 hari --}}
            @if ($adsAnalytics)
                @php $as = $adsAnalytics['summary']; @endphp
                <div class="bg-white border border-[#E4E8EF] rounded-2xl shadow-sm">
                    <div class="flex justify-between items-center px-5 py-4 border-b border-[#E4E8EF]">
                        <h2 class="text-[15px] font-bold">Performa Iklan · 14 Hari</h2>
                        <a href="{{ route('admin.analitik.index', ['tab' => 'iklan']) }}" class="text-[12.5px] font-semibold text-brand-600">Detail</a>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-[#E4E8EF] border-b border-[#E4E8EF] text-center">
                        @foreach ([['Impresi', number_format($as['impressions'], 0, ',', '.')], ['Klik', number_format($as['clicks'], 0, ',', '.')], ['CTR', number_format($as['ctr'], 2, ',', '.') . '%']] as [$label, $value])
                            <div class="px-2 py-3">
                                <div class="text-[11px] font-semibold text-[#6C7387]">{{ $label }}</div>
                                <div class="font-body text-[18px] font-semibold mt-0.5">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                    @forelse ($adsAnalytics['top'] as $row)
                        <div class="flex items-center justify-between gap-3 px-5 py-2.5 {{ ! $loop->last ? 'border-b border-[#E4E8EF]' : '' }}">
                            <div class="min-w-0">
                                <div class="text-[13px] font-semibold truncate">{{ $row['ad']->advertiser_name }}</div>
                                <div class="text-[11px] text-[#848CA3]">{{ \App\Models\Advertisement::PLACEMENTS[$row['ad']->placement]['label'] ?? $row['ad']->placement }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-mono text-[12.5px] font-semibold">{{ number_format($row['impressions'], 0, ',', '.') }}</div>
                                <div class="text-[11px] text-[#848CA3]">CTR {{ number_format($row['ctr'], 2, ',', '.') }}%</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-[13px] text-[#848CA3]">Belum ada impresi dalam 14 hari terakhir.</div>
                    @endforelse
                </div>
            @endif

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