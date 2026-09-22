<x-layouts.admin title="Iklan">
    {{-- Widget Statistik KPI (Top Row) --}}
    <div class="grid grid-cols-12 gap-5 sm:gap-6 mb-6">
        {{-- Card 1: Iklan Aktif --}}
        <div class="col-span-12 sm:col-span-6 lg:col-span-4 bg-white border border-[#E4E8EF] rounded-2xl px-6 py-5 shadow-sm">
            <div class="text-[13px] text-[#6C7387] font-semibold">Iklan Aktif</div>
            <div class="font-mono text-[34px] font-bold mt-2 text-emerald-600 leading-none">
                {{ $activeAdsCount ?? 0 }}
            </div>
        </div>

        {{-- Card 2: Total Impresi Bulan Ini --}}
        <div class="col-span-12 sm:col-span-6 lg:col-span-4 bg-white border border-[#E4E8EF] rounded-2xl px-6 py-5 shadow-sm">
            <div class="text-[13px] text-[#6C7387] font-semibold">Total Impresi Bulan Ini</div>
            <div class="font-mono text-[34px] font-bold mt-2 text-gray-900 leading-none">
                {{ \App\Models\Advertisement::formatMetricNumber($totalImpressionsMonth ?? 0) }}
            </div>
        </div>

        {{-- Card 3: Akan Berakhir < 7 Hari --}}
        <div class="col-span-12 sm:col-span-6 lg:col-span-4 bg-white border border-[#E4E8EF] rounded-2xl px-6 py-5 shadow-sm">
            <div class="text-[13px] text-[#6C7387] font-semibold">Akan Berakhir &lt; 7 Hari</div>
            <div class="font-mono text-[34px] font-bold mt-2 text-amber-500 leading-none">
                {{ $expiringSoonCount ?? 0 }}
            </div>
        </div>
    </div>

    {{-- Toolbar: Search + Filter Penempatan + Filter Status + Tombol Tambah Iklan --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
        <form action="{{ route('admin.iklan.index') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1">
            {{-- Input Search --}}
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-[#848CA3]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke-width="2"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"></line>
                    </svg>
                </span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama pengiklan..."
                    class="w-full pl-10 pr-4 py-2.5 text-[13px] bg-white border border-[#E4E8EF] rounded-xl placeholder-[#848CA3] focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm transition">
            </div>

            {{-- Dropdown Filter Penempatan --}}
            <select name="placement" onchange="this.form.submit()"
                class="px-3.5 py-2.5 text-[13px] font-medium bg-white border border-[#E4E8EF] rounded-xl text-gray-700 focus:outline-none focus:border-brand-600 shadow-sm transition">
                <option value="all">Semua Penempatan</option>
                <option value="header" {{ request('placement') === 'header' ? 'selected' : '' }}>Header</option>
                <option value="sidebar" {{ request('placement') === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                <option value="inline_artikel" {{ request('placement') === 'inline_artikel' ? 'selected' : '' }}>Inline Artikel</option>
                <option value="footer" {{ request('placement') === 'footer' ? 'selected' : '' }}>Footer</option>
            </select>

            {{-- Dropdown Filter Status --}}
            <select name="status" onchange="this.form.submit()"
                class="px-3.5 py-2.5 text-[13px] font-medium bg-white border border-[#E4E8EF] rounded-xl text-gray-700 focus:outline-none focus:border-brand-600 shadow-sm transition">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Selesai / Kadaluarsa</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </form>

        {{-- Tombol + Tambah Iklan --}}
        <button type="button" onclick="openAddModal()"
            class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-brand-900 hover:bg-brand-800 text-white font-semibold text-[13px] rounded-xl shadow-sm transition shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19" stroke-width="2.5" stroke-linecap="round"></line>
                <line x1="5" y1="12" x2="19" y2="12" stroke-width="2.5" stroke-linecap="round"></line>
            </svg>
            <span>Tambah Iklan</span>
        </button>
    </div>

    {{-- Kartu Tabel Iklan --}}
    <div class="bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] border-collapse">
                <thead>
                    <tr class="border-b border-[#E4E8EF] bg-gray-50/40">
                        <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-6 py-3.5">PENGIKLAN</th>
                        <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-5 py-3.5">PENEMPATAN</th>
                        <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-5 py-3.5">PERIODE</th>
                        <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-4 py-3.5">STATUS</th>
                        <th class="text-right text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-4 py-3.5">IMPRESI</th>
                        <th class="text-right text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-4 py-3.5">KLIK</th>
                        <th class="text-right text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-6 py-3.5">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4E8EF]">
                    @forelse ($advertisements as $ad)
                        @php
                            $effectiveStatus = $ad->effective_status;
                            $avatarGradients = [
                                'from-blue-900 to-indigo-900',
                                'from-amber-600 to-orange-700',
                                'from-emerald-600 to-teal-800',
                                'from-amber-700 to-yellow-800',
                                'from-blue-600 to-cyan-700',
                                'from-rose-600 to-red-800',
                            ];
                            $gradientClass = $avatarGradients[$ad->id % count($avatarGradients)];
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition">
                            {{-- Kolom Pengiklan (Avatar Gradien/Banner Thumbnail + Nama + Kontak) --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3.5">
                                    @if ($ad->banner_url)
                                        <img src="{{ $ad->banner_url }}" alt="{{ $ad->advertiser_name }}" class="w-12 h-9 rounded-lg object-cover border border-[#E4E8EF] shrink-0">
                                    @else
                                        <div class="w-12 h-9 rounded-lg bg-gradient-to-br {{ $gradientClass }} flex items-center justify-center text-white/90 text-xs font-bold shrink-0 shadow-inner">
                                            {{ strtoupper(substr($ad->advertiser_name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="text-[13.5px] font-bold text-gray-900 truncate">
                                            {{ $ad->advertiser_name }}
                                        </div>
                                        <div class="text-[11.5px] text-[#848CA3] truncate">
                                            {{ $ad->advertiser_contact ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Kolom Penempatan --}}
                            <td class="px-5 py-4 text-[13px] font-medium text-gray-800 whitespace-nowrap">
                                @if ($ad->placement === 'header')
                                    Header
                                @elseif ($ad->placement === 'sidebar')
                                    Sidebar
                                @elseif ($ad->placement === 'inline_artikel')
                                    Inline Artikel
                                @elseif ($ad->placement === 'footer')
                                    Footer
                                @else
                                    {{ ucfirst($ad->placement) }}
                                @endif
                            </td>

                            {{-- Kolom Periode --}}
                            <td class="px-5 py-4 text-[12.5px] text-gray-700 whitespace-nowrap font-medium">
                                {{ $ad->start_date ? $ad->start_date->translatedFormat('d M') : '' }} – {{ $ad->end_date ? $ad->end_date->translatedFormat('d M Y') : '' }}
                            </td>

                            {{-- Kolom Status --}}
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if ($effectiveStatus === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @elseif ($effectiveStatus === 'scheduled')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11.5px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        Terjadwal
                                    </span>
                                @elseif ($effectiveStatus === 'expired')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11.5px] font-semibold bg-gray-100 text-gray-700 border border-gray-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        Selesai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11.5px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            {{-- Kolom Impresi --}}
                            <td class="px-4 py-4 text-right text-[13px] font-mono text-gray-800 whitespace-nowrap font-semibold">
                                {{ $effectiveStatus === 'scheduled' ? '—' : \App\Models\Advertisement::formatMetricNumber($ad->impressions_count ?? 0) }}
                            </td>

                            {{-- Kolom Klik --}}
                            <td class="px-4 py-4 text-right text-[13px] font-mono text-gray-800 whitespace-nowrap font-semibold">
                                {{ $effectiveStatus === 'scheduled' ? '—' : \App\Models\Advertisement::formatMetricNumber($ad->clicks_count ?? 0) }}
                            </td>

                            {{-- Kolom Aksi (Edit & Hapus) --}}
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    {{-- Tombol Edit --}}
                                    <button type="button"
                                        onclick='openEditModal(@json($ad), "{{ $ad->banner_url ?? "" }}")'
                                        title="Edit Iklan"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-brand-600 hover:bg-[#F1F3F7] transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    {{-- Tombol Hapus --}}
                                    <button type="button"
                                        onclick="confirmDeleteAd({{ $ad->id }}, '{{ addslashes($ad->advertiser_name) }}')"
                                        title="Hapus Iklan"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center text-[13px] text-[#848CA3]">
                                Belum ada iklan yang ditambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($advertisements->hasPages())
            <div class="px-6 py-3.5 border-t border-[#E4E8EF]">
                {{ $advertisements->links() }}
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- ============================ MODAL WINDOWS ============================== --}}
    {{-- ========================================================================= --}}

    {{-- 1. Modal Tambah Iklan Baru (Sesuai Mockup Gambar 2) --}}
    <div id="modal-add-ad" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[560px] w-full p-6 sm:p-7 shadow-2xl relative max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-[#E4E8EF] mb-5">
                <h3 class="text-[17px] font-bold text-gray-900">Tambah Iklan Baru</h3>
                <button type="button" onclick="closeModal('modal-add-ad')" class="w-7 h-7 rounded-lg flex items-center justify-center text-[#848CA3] hover:text-gray-800 hover:bg-gray-100 text-xl font-bold leading-none">&times;</button>
            </div>

            <form action="{{ route('admin.advertisements.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Banner Iklan Picker Area --}}
                <div class="mb-5">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Banner Iklan</label>
                    <div class="border border-[#E4E8EF] rounded-xl p-3.5 flex items-center justify-between gap-3 bg-[#FAFBFC]">
                        <div class="flex items-center gap-3 min-w-0">
                            {{-- Thumbnail Icon / Preview Box --}}
                            <div id="add-banner-preview-container" class="w-14 h-11 rounded-lg bg-[#EFF2F6] border border-[#E4E8EF] flex items-center justify-center shrink-0 overflow-hidden text-[#848CA3]">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.8"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 21" stroke-width="1.8"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div id="add-banner-label" class="text-[13px] font-semibold text-gray-800 truncate">Belum ada banner dipilih</div>
                                <div class="text-[11px] text-[#848CA3]">Rekomendasi 970&times;250px (Header) atau 300&times;250px (Sidebar)</div>
                            </div>
                        </div>

                        {{-- Tombol Pilih Gambar (Hidden file input) --}}
                        <input type="file" name="banner" id="add-banner-file" accept="image/*" class="hidden" onchange="previewBanner(this, 'add-banner-preview-container', 'add-banner-label')">
                        <button type="button" onclick="document.getElementById('add-banner-file').click()"
                            class="px-4 py-2 text-[12.5px] font-semibold text-gray-700 bg-[#E8EDF5] hover:bg-[#D9E1ED] rounded-xl transition shrink-0">
                            Pilih Gambar
                        </button>
                    </div>
                </div>

                {{-- Row: Nama Pengiklan & Kontak Pengiklan --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Nama Pengiklan <span class="text-rose-500">*</span></label>
                        <input type="text" name="advertiser_name" required placeholder="mis. Bank Kebumen Sejahtera"
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Kontak Pengiklan</label>
                        <input type="text" name="advertiser_contact" placeholder="Email / No. HP"
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>
                </div>

                {{-- Target URL --}}
                <div class="mb-4">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Target URL <span class="text-rose-500">*</span></label>
                    <input type="url" name="target_url" required placeholder="https://"
                        class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>

                {{-- Row: Penempatan & Status --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Penempatan <span class="text-rose-500">*</span></label>
                        <select name="placement" required class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                            <option value="header">Header</option>
                            <option value="sidebar">Sidebar</option>
                            <option value="inline_artikel">Inline Artikel</option>
                            <option value="footer">Footer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Status <span class="text-rose-500">*</span></label>
                        <select name="status" required class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                            <option value="active">Aktif</option>
                            <option value="scheduled">Terjadwal</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>

                {{-- Row: Tanggal Mulai & Tanggal Berakhir --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Tanggal Mulai <span class="text-rose-500">*</span></label>
                        <input type="date" name="start_date" required value="{{ date('Y-m-d') }}"
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Tanggal Berakhir <span class="text-rose-500">*</span></label>
                        <input type="date" name="end_date" required value="{{ date('Y-m-d', strtotime('+14 days')) }}"
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                    </div>
                </div>

                {{-- Tombol Batal & Simpan Iklan --}}
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#E4E8EF]">
                    <button type="button" onclick="closeModal('modal-add-ad')"
                        class="px-4 py-2.5 text-[13px] font-semibold text-gray-600 bg-[#F1F3F7] hover:bg-[#E4E8EF] rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-[13px] font-semibold text-white bg-brand-900 hover:bg-brand-800 rounded-xl transition shadow-sm">
                        Simpan Iklan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Modal Edit Iklan --}}
    <div id="modal-edit-ad" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[560px] w-full p-6 sm:p-7 shadow-2xl relative max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-[#E4E8EF] mb-5">
                <h3 class="text-[17px] font-bold text-gray-900">Edit Iklan</h3>
                <button type="button" onclick="closeModal('modal-edit-ad')" class="w-7 h-7 rounded-lg flex items-center justify-center text-[#848CA3] hover:text-gray-800 hover:bg-gray-100 text-xl font-bold leading-none">&times;</button>
            </div>

            <form id="form-edit-ad" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Banner Iklan Picker Area --}}
                <div class="mb-5">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Banner Iklan</label>
                    <div class="border border-[#E4E8EF] rounded-xl p-3.5 flex items-center justify-between gap-3 bg-[#FAFBFC]">
                        <div class="flex items-center gap-3 min-w-0">
                            {{-- Thumbnail Icon / Preview Box --}}
                            <div id="edit-banner-preview-container" class="w-14 h-11 rounded-lg bg-[#EFF2F6] border border-[#E4E8EF] flex items-center justify-center shrink-0 overflow-hidden text-[#848CA3]">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.8"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 21" stroke-width="1.8"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div id="edit-banner-label" class="text-[13px] font-semibold text-gray-800 truncate">Banner saat ini</div>
                                <div class="text-[11px] text-[#848CA3]">Klik Pilih Gambar untuk mengganti banner</div>
                            </div>
                        </div>

                        {{-- Tombol Ganti Gambar --}}
                        <input type="file" name="banner" id="edit-banner-file" accept="image/*" class="hidden" onchange="previewBanner(this, 'edit-banner-preview-container', 'edit-banner-label')">
                        <button type="button" onclick="document.getElementById('edit-banner-file').click()"
                            class="px-4 py-2 text-[12.5px] font-semibold text-gray-700 bg-[#E8EDF5] hover:bg-[#D9E1ED] rounded-xl transition shrink-0">
                            Pilih Gambar
                        </button>
                    </div>
                </div>

                {{-- Row: Nama Pengiklan & Kontak Pengiklan --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Nama Pengiklan <span class="text-rose-500">*</span></label>
                        <input type="text" id="edit-advertiser-name" name="advertiser_name" required
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Kontak Pengiklan</label>
                        <input type="text" id="edit-advertiser-contact" name="advertiser_contact"
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>
                </div>

                {{-- Target URL --}}
                <div class="mb-4">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Target URL <span class="text-rose-500">*</span></label>
                    <input type="url" id="edit-target-url" name="target_url" required
                        class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>

                {{-- Row: Penempatan & Status --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Penempatan <span class="text-rose-500">*</span></label>
                        <select id="edit-placement" name="placement" required class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                            <option value="header">Header</option>
                            <option value="sidebar">Sidebar</option>
                            <option value="inline_artikel">Inline Artikel</option>
                            <option value="footer">Footer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Status <span class="text-rose-500">*</span></label>
                        <select id="edit-status" name="status" required class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                            <option value="active">Aktif</option>
                            <option value="scheduled">Terjadwal</option>
                            <option value="expired">Selesai / Kadaluarsa</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>

                {{-- Row: Tanggal Mulai & Tanggal Berakhir --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Tanggal Mulai <span class="text-rose-500">*</span></label>
                        <input type="date" id="edit-start-date" name="start_date" required
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Tanggal Berakhir <span class="text-rose-500">*</span></label>
                        <input type="date" id="edit-end-date" name="end_date" required
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                    </div>
                </div>

                {{-- Tombol Batal & Simpan Iklan --}}
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#E4E8EF]">
                    <button type="button" onclick="closeModal('modal-edit-ad')"
                        class="px-4 py-2.5 text-[13px] font-semibold text-gray-600 bg-[#F1F3F7] hover:bg-[#E4E8EF] rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-[13px] font-semibold text-white bg-brand-900 hover:bg-brand-800 rounded-xl transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Modal Konfirmasi Hapus Iklan --}}
    <div id="modal-delete-ad" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[420px] w-full p-6 shadow-2xl relative text-center">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 mx-auto flex items-center justify-center mb-3.5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-[16px] font-bold text-gray-900 mb-1">Hapus Iklan</h3>
            <p id="delete-ad-message" class="text-[13px] text-[#848CA3] mb-6">Apakah Anda yakin ingin menghapus data iklan ini?</p>

            <form id="form-delete-ad" method="POST" class="flex items-center justify-center gap-3">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeModal('modal-delete-ad')" class="flex-1 px-4 py-2.5 text-[13px] font-semibold text-gray-600 bg-[#F1F3F7] hover:bg-[#E4E8EF] rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2.5 text-[13px] font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>

    {{-- 4. Modal Pop-up Alert Feedback (Sukses & Error) --}}
    <div id="modal-alert-popup" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[400px] w-full p-6 shadow-2xl relative text-center">
            <div id="alert-icon-container" class="w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-4">
                {{-- Diisi secara dinamis oleh JS --}}
            </div>
            <h3 id="alert-modal-title" class="text-[17px] font-bold text-gray-900 mb-1.5">Pemberitahuan</h3>
            <div id="alert-modal-body" class="text-[13px] text-[#848CA3] mb-6 leading-relaxed"></div>

            <button type="button" onclick="closeModal('modal-alert-popup')" id="alert-modal-btn"
                class="w-full px-5 py-2.5 text-[13px] font-semibold text-white rounded-xl transition shadow-sm bg-brand-900 hover:bg-brand-800">
                Tutup
            </button>
        </div>
    </div>

    {{-- Script Interaktivitas Modal & Banner Preview --}}
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function openAddModal() {
            openModal('modal-add-ad');
        }

        function openEditModal(ad, bannerUrl) {
            const form = document.getElementById('form-edit-ad');
            form.action = `/admin/advertisements/${ad.id}`;

            document.getElementById('edit-advertiser-name').value = ad.advertiser_name || '';
            document.getElementById('edit-advertiser-contact').value = ad.advertiser_contact || '';
            document.getElementById('edit-target-url').value = ad.target_url || '';
            document.getElementById('edit-placement').value = ad.placement || 'header';
            document.getElementById('edit-status').value = ad.status || 'active';

            if (ad.start_date) {
                document.getElementById('edit-start-date').value = ad.start_date.substring(0, 10);
            }
            if (ad.end_date) {
                document.getElementById('edit-end-date').value = ad.end_date.substring(0, 10);
            }

            const previewContainer = document.getElementById('edit-banner-preview-container');
            const label = document.getElementById('edit-banner-label');

            if (bannerUrl) {
                previewContainer.innerHTML = `<img src="${bannerUrl}" class="w-full h-full object-cover">`;
                label.textContent = 'Banner saat ini telah terpasang';
            } else {
                previewContainer.innerHTML = `
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.8"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path d="M21 15l-5-5L5 21" stroke-width="1.8"/>
                    </svg>
                `;
                label.textContent = 'Belum ada banner dipilih';
            }

            openModal('modal-edit-ad');
        }

        function confirmDeleteAd(id, name) {
            const form = document.getElementById('form-delete-ad');
            form.action = `/admin/advertisements/${id}`;

            document.getElementById('delete-ad-message').innerHTML = `Apakah Anda yakin ingin menghapus iklan "<strong>${name}</strong>"? Tindakan ini tidak dapat dibatalkan.`;
            openModal('modal-delete-ad');
        }

        function previewBanner(input, containerId, labelId) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const container = document.getElementById(containerId);
                    container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    document.getElementById(labelId).textContent = file.name;
                };

                reader.readAsDataURL(file);
            }
        }

        function showModalAlert(title, messageHtml, type = 'success') {
            const iconContainer = document.getElementById('alert-icon-container');
            const titleEl = document.getElementById('alert-modal-title');
            const bodyEl = document.getElementById('alert-modal-body');
            const btnEl = document.getElementById('alert-modal-btn');

            titleEl.textContent = title;
            bodyEl.innerHTML = messageHtml;

            if (type === 'success') {
                iconContainer.className = 'w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-4 bg-emerald-100 text-emerald-600';
                iconContainer.innerHTML = `<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
                btnEl.className = 'w-full px-5 py-2.5 text-[13px] font-semibold text-white rounded-xl transition shadow-sm bg-emerald-600 hover:bg-emerald-700';
            } else {
                iconContainer.className = 'w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-4 bg-rose-100 text-rose-600';
                iconContainer.innerHTML = `<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>`;
                btnEl.className = 'w-full px-5 py-2.5 text-[13px] font-semibold text-white rounded-xl transition shadow-sm bg-rose-600 hover:bg-rose-700';
            }

            openModal('modal-alert-popup');
        }

        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success'))
                showModalAlert('Berhasil!', {!! json_encode(session('success')) !!}, 'success');
            @endif

            @if (session('error'))
                showModalAlert('Perhatian', {!! json_encode(session('error')) !!}, 'error');
            @endif

            @if ($errors->any())
                const errorsList = `<ul class="text-left list-disc list-inside space-y-1">` +
                    @json($errors->all()).map(err => `<li>${err}</li>`).join('') +
                    `</ul>`;
                showModalAlert('Terjadi Kesalahan', errorsList, 'error');
            @endif
        });
    </script>
</x-layouts.admin>
