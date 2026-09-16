<x-layouts.admin title="Dashboard">
    <div class="grid grid-cols-12 gap-5 sm:gap-6 mb-6">
        @can('articles.view')
        <div class="col-span-12 sm:col-span-6 lg:col-span-3 bg-white border border-[#E4E8EF] rounded-2xl px-5 py-5 shadow-sm">
            <div class="text-[12.5px] text-[#6C7387] font-semibold">Total Artikel</div>
            <div class="font-mono text-[30px] font-semibold mt-2">0</div>
            <div class="text-[11.5px] text-[#848CA3] mt-1.5">Menunggu modul Artikel</div>
        </div>
        @endcan
        @can('articles.approve')
        <div class="col-span-12 sm:col-span-6 lg:col-span-3 bg-white border border-[#E4E8EF] rounded-2xl px-5 py-5 shadow-sm">
            <div class="text-[12.5px] text-[#6C7387] font-semibold">Pending Review</div>
            <div class="font-mono text-[30px] font-semibold mt-2 text-warning">0</div>
            <div class="text-[11.5px] text-[#848CA3] mt-1.5">Menunggu modul Artikel</div>
        </div>
        @endcan
        @can('ads.view')
        <div class="col-span-12 sm:col-span-6 lg:col-span-3 bg-white border border-[#E4E8EF] rounded-2xl px-5 py-5 shadow-sm">
            <div class="text-[12.5px] text-[#6C7387] font-semibold">Iklan Aktif</div>
            <div class="font-mono text-[30px] font-semibold mt-2 text-info">0</div>
            <div class="text-[11.5px] text-[#848CA3] mt-1.5">Menunggu modul Iklan</div>
        </div>
        @endcan
        @can('users.view')
        <div class="col-span-12 sm:col-span-6 lg:col-span-3 bg-white border border-[#E4E8EF] rounded-2xl px-5 py-5 shadow-sm">
            <div class="text-[12.5px] text-[#6C7387] font-semibold">Total Pengguna</div>
            <div class="font-mono text-[30px] font-semibold mt-2 text-success">{{ $totalPengguna }}</div>
            <div class="text-[11.5px] text-[#848CA3] mt-1.5">Akun terdaftar di sistem</div>
        </div>
        @endcan
    </div>

    <div class="grid grid-cols-12 gap-5 sm:gap-6">
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
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-[13px] text-[#848CA3]">
                                Belum ada artikel — tabel ini otomatis terisi begitu modul Artikel aktif.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @endcan
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-5 sm:gap-6">
            @can('ads.view')
            <div class="bg-white border border-[#E4E8EF] rounded-2xl shadow-sm">
                <div class="flex justify-between items-center px-5 py-4 border-b border-[#E4E8EF]">
                    <h2 class="text-[15px] font-bold">Iklan Akan Berakhir</h2>
                    <a href="{{ route('admin.iklan.index') }}" class="text-[12.5px] font-semibold text-brand-600">Kelola</a>
                </div>
                <div class="px-5 py-8 text-center text-[13px] text-[#848CA3]">
                    Belum ada data iklan.
                </div>
            </div>

            @endcan
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
