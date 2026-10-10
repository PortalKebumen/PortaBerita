<div class="space-y-6">
    {{-- Widget Statistik KPI (Top Row) --}}
    <div class="grid grid-cols-12 gap-5 sm:gap-6">
        {{-- Card 1: Iklan Aktif --}}
        <div class="col-span-12 sm:col-span-6 lg:col-span-4 card p-6 shadow-sm">
            <div class="text-[13px] text-[#6C7387] font-semibold">Iklan Aktif</div>
            <div class="font-mono text-[34px] font-bold mt-2 text-success leading-none">
                {{ $this->stats['activeAdsCount'] }}
            </div>
            <div class="text-[11.5px] text-[#848CA3] mt-2">Sedang tayang saat ini</div>
        </div>

        {{-- Card 2: Total Impresi Bulan Ini --}}
        <div class="col-span-12 sm:col-span-6 lg:col-span-4 card p-6 shadow-sm">
            <div class="text-[13px] text-[#6C7387] font-semibold">Total Impresi Bulan Ini</div>
            <div class="font-mono text-[34px] font-bold mt-2 text-[#171B28] leading-none">
                {{ $this->stats['totalImpressionsMonth'] }}
            </div>
            <div class="text-[11.5px] text-[#848CA3] mt-2">Akumulasi tayang seluruh banner</div>
        </div>

        {{-- Card 3: Akan Berakhir < 7 Hari --}}
        <div class="col-span-12 sm:col-span-6 lg:col-span-4 card p-6 shadow-sm">
            <div class="text-[13px] text-[#6C7387] font-semibold">Akan Berakhir &lt; 7 Hari</div>
            <div class="font-mono text-[34px] font-bold mt-2 text-warning leading-none">
                {{ $this->stats['expiringSoonCount'] }}
            </div>
            <div class="text-[11.5px] text-[#848CA3] mt-2">Perlu konfirmasi perpanjangan</div>
        </div>
    </div>

    {{-- Toolbar: Search + Filter Penempatan + Filter Status + Tombol Tambah Iklan --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3 flex-1">
            {{-- Search Bar Reaktif --}}
            <div class="relative flex-1 max-w-[320px]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari nama pengiklan..."
                    class="form-input pl-9">
            </div>

            {{-- Filter Penempatan --}}
            <select wire:model.live="placementFilter" class="form-select w-44">
                <option value="all">Semua Penempatan</option>
                <option value="header">Header</option>
                <option value="sidebar">Sidebar</option>
                <option value="inline_artikel">Inline Artikel</option>
                <option value="footer">Footer</option>
            </select>

            {{-- Filter Status --}}
            <select wire:model.live="statusFilter" class="form-select w-44">
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="scheduled">Terjadwal</option>
                <option value="expired">Selesai / Kadaluarsa</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>

        {{-- Tombol + Tambah Iklan --}}
        @can('ads.create')
            <button type="button" wire:click="openAddModal" class="btn-primary shrink-0">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19" stroke-linecap="round"></line>
                    <line x1="5" y1="12" x2="19" y2="12" stroke-linecap="round"></line>
                </svg>
                <span>Tambah Iklan</span>
            </button>
        @endcan
    </div>

    {{-- Tabel Iklan (Gaya Card & Table Pengguna & Role) --}}
    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[#6C7387] border-b border-[#E4E8EF]">
                    <th class="px-5 py-3.5 font-medium">PENGIKLAN</th>
                    <th class="px-4 py-3.5 font-medium">PENEMPATAN</th>
                    <th class="px-4 py-3.5 font-medium">PERIODE</th>
                    <th class="px-4 py-3.5 font-medium">STATUS</th>
                    <th class="px-4 py-3.5 font-medium text-right">IMPRESI</th>
                    <th class="px-4 py-3.5 font-medium text-right">KLIK</th>
                    <th class="px-5 py-3.5 font-medium text-right">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E4E8EF]">
                @forelse ($this->advertisements as $ad)
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
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        {{-- Kolom Pengiklan (Banner Thumbnail / Inisial + Nama + Kontak) --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if ($ad->banner_url)
                                    <img src="{{ $ad->banner_url }}" alt="{{ $ad->advertiser_name }}" class="w-12 h-9 rounded-lg object-cover border border-[#CDD3DF] shrink-0">
                                @else
                                    <div class="w-12 h-9 rounded-lg bg-gradient-to-br {{ $gradientClass }} flex items-center justify-center text-white text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($ad->advertiser_name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-semibold text-[#171B28] truncate">
                                        {{ $ad->advertiser_name }}
                                    </div>
                                    <div class="text-xs text-[#848CA3] truncate">
                                        {{ $ad->advertiser_contact ?: '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Kolom Penempatan --}}
                        <td class="px-4 py-3.5 text-xs text-[#3B4152] font-medium whitespace-nowrap">
                            @if ($ad->placement === 'header')
                                <span class="badge-neutral">Header</span>
                            @elseif ($ad->placement === 'sidebar')
                                <span class="badge-neutral">Sidebar</span>
                            @elseif ($ad->placement === 'inline_artikel')
                                <span class="badge-neutral">Inline Artikel</span>
                            @elseif ($ad->placement === 'footer')
                                <span class="badge-neutral">Footer</span>
                            @else
                                <span class="badge-neutral">{{ ucfirst($ad->placement) }}</span>
                            @endif
                        </td>

                        {{-- Kolom Periode --}}
                        <td class="px-4 py-3.5 text-xs text-[#6C7387] whitespace-nowrap font-medium">
                            {{ $ad->start_date ? $ad->start_date->translatedFormat('d M') : '' }} – {{ $ad->end_date ? $ad->end_date->translatedFormat('d M Y') : '' }}
                        </td>

                        {{-- Kolom Status --}}
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            @if ($effectiveStatus === 'active')
                                <span class="badge-success">
                                    <span class="badge-dot"></span>
                                    Aktif
                                </span>
                            @elseif ($effectiveStatus === 'scheduled')
                                <span class="badge-info">
                                    <span class="badge-dot"></span>
                                    Terjadwal
                                </span>
                            @elseif ($effectiveStatus === 'expired')
                                <span class="badge-neutral">
                                    <span class="badge-dot"></span>
                                    Selesai
                                </span>
                            @else
                                <span class="badge-danger">
                                    <span class="badge-dot"></span>
                                    Nonaktif
                                </span>
                            @endif
                        </td>

                        {{-- Kolom Impresi --}}
                        <td class="px-4 py-3.5 text-right font-mono text-xs font-semibold text-[#171B28] whitespace-nowrap">
                            {{ $effectiveStatus === 'scheduled' ? '—' : \App\Models\Advertisement::formatMetricNumber($ad->impressions_count ?? 0) }}
                        </td>

                        {{-- Kolom Klik --}}
                        <td class="px-4 py-3.5 text-right font-mono text-xs font-semibold text-[#171B28] whitespace-nowrap">
                            {{ $effectiveStatus === 'scheduled' ? '—' : \App\Models\Advertisement::formatMetricNumber($ad->clicks_count ?? 0) }}
                        </td>

                        {{-- Kolom Aksi --}}
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                @can('ads.update')
                                    <button type="button"
                                        wire:click="openEditModal({{ $ad->id }})"
                                        title="Edit Iklan"
                                        class="btn-icon text-[#6C7387] hover:text-brand-600 hover:bg-brand-50">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                        </svg>
                                    </button>
                                @endcan

                                @can('ads.delete')
                                    <button type="button"
                                        wire:click="confirmDelete({{ $ad->id }})"
                                        title="Hapus Iklan"
                                        class="btn-icon text-danger hover:bg-danger-bg">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M3 6h18"></path>
                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                        </svg>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-[#848CA3]">
                            Belum ada iklan yang ditambahkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $this->advertisements->links() }}
    </div>

    {{-- ==================== MODAL TAMBAH / EDIT IKLAN ==================== --}}
    <div class="modal-overlay {{ $showAdModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-xl shadow-xl max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#E4E8EF] sticky top-0 bg-white z-10">
                <h3 class="font-semibold text-[#171B28] text-base">
                    {{ $editingAdId ? 'Edit Iklan' : 'Tambah Iklan Baru' }}
                </h3>
                <button type="button" wire:click="closeModals" class="text-[#848CA3] hover:text-gray-700 text-lg leading-none">&times;</button>
            </div>

            <form wire:submit="save" class="px-6 py-5 space-y-4">
                {{-- Banner Picker --}}
                <div>
                    <label class="form-label">Banner Iklan</label>
                    <div class="border border-[#CDD3DF] rounded-xl p-3.5 flex items-center justify-between gap-3 bg-[#F8F9FB]">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-16 h-12 rounded-lg bg-white border border-[#CDD3DF] flex items-center justify-center shrink-0 overflow-hidden text-[#848CA3]">
                                @if ($directBannerFile)
                                    <img src="{{ $directBannerFile->temporaryUrl() }}" class="w-full h-full object-cover">
                                @elseif ($bannerPreviewUrl)
                                    <img src="{{ $bannerPreviewUrl }}" class="w-full h-full object-cover">
                                @else
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <path d="M21 15l-5-5L5 21"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-[13px] font-semibold text-[#171B28] truncate">
                                    @if ($directBannerFile)
                                        {{ $directBannerFile->getClientOriginalName() }}
                                    @elseif ($bannerPreviewUrl)
                                        Banner terpilih
                                    @else
                                        Belum ada banner dipilih
                                    @endif
                                </div>
                                <div class="text-[11px] text-[#848CA3]">
                                    @canany(['media.upload', 'media.view-own', 'media.view-any'])
                                        Pilih dari Pustaka Media, atau unggah langsung di bawah ini.
                                    @else
                                        Unggah berkas banner langsung (JPG/PNG/WEBP, maks 5MB).
                                    @endcanany
                                </div>
                            </div>
                        </div>

                        @canany(['media.upload', 'media.view-own', 'media.view-any'])
                            <button type="button"
                                wire:click="$dispatch('open-media-picker', { target: 'ad-banner' })"
                                class="btn-secondary btn-sm shrink-0">
                                Pustaka Media
                            </button>
                        @endcanany
                    </div>

                    <div class="mt-2 flex items-center gap-2 text-xs text-[#6C7387]">
                        <span>Atau unggah file langsung:</span>
                        <input type="file" wire:model="directBannerFile" accept="image/*" class="text-xs text-[#6C7387]">
                    </div>
                    @error('directBannerFile') <p class="form-error-text">{{ $message }}</p> @enderror
                </div>

                {{-- Row: Nama Pengiklan & Kontak --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nama Pengiklan <span class="text-danger">*</span></label>
                        <input type="text"
                            wire:model="advertiserName"
                            placeholder="mis. Bank Kebumen Sejahtera"
                            class="form-input @error('advertiserName') is-error @enderror">
                        @error('advertiserName') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Kontak Pengiklan</label>
                        <input type="text"
                            wire:model="advertiserContact"
                            placeholder="Email / No. HP"
                            class="form-input @error('advertiserContact') is-error @enderror">
                        @error('advertiserContact') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Target URL --}}
                <div>
                    <label class="form-label">Target URL (Tautan Tujuan) <span class="text-danger">*</span></label>
                    <input type="url"
                        wire:model="targetUrl"
                        placeholder="https://..."
                        class="form-input @error('targetUrl') is-error @enderror">
                    @error('targetUrl') <p class="form-error-text">{{ $message }}</p> @enderror
                </div>

                {{-- Row: Penempatan & Status --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Posisi Penempatan <span class="text-danger">*</span></label>
                        <select wire:model="placement" class="form-select @error('placement') is-error @enderror">
                            <option value="header">Header Banner (970x250)</option>
                            <option value="sidebar">Sidebar Banner (300x250)</option>
                            <option value="inline_artikel">Inline Artikel (728x90)</option>
                            <option value="footer">Footer Banner</option>
                        </select>
                        @error('placement') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Status Iklan <span class="text-danger">*</span></label>
                        <select wire:model="status" class="form-select @error('status') is-error @enderror">
                            <option value="active">Aktif</option>
                            <option value="scheduled">Terjadwal</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                        @error('status') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Row: Tanggal Mulai & Berakhir --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date"
                            wire:model="startDate"
                            class="form-input @error('startDate') is-error @enderror">
                        @error('startDate') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                        <input type="date"
                            wire:model="endDate"
                            class="form-input @error('endDate') is-error @enderror">
                        @error('endDate') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Tombol Batal & Simpan --}}
                <div class="flex justify-end gap-2.5 pt-3 border-t border-[#E4E8EF]">
                    <button type="button" wire:click="closeModals" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">
                            {{ $editingAdId ? 'Simpan Perubahan' : 'Simpan Iklan' }}
                        </span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL KONFIRMASI HAPUS ==================== --}}
    <div class="modal-overlay {{ $showDeleteModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-sm p-6 space-y-4 shadow-xl text-center animate-in fade-in zoom-in-95 duration-150">
            <div class="w-12 h-12 rounded-full bg-danger-bg text-danger mx-auto flex items-center justify-center">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <div>
                <h3 class="font-semibold text-[#171B28]">Hapus Iklan</h3>
                <p class="text-sm text-[#6C7387] mt-1.5 leading-relaxed">
                    Apakah Anda yakin ingin menghapus data iklan
                    <strong class="text-[#171B28]">"{{ $deletingAdName }}"</strong>?
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <div class="flex justify-end gap-2.5 pt-2">
                <button type="button" wire:click="closeModals" class="btn-secondary flex-1">Batal</button>
                <button type="button" wire:click="deleteConfirmed" class="btn-danger flex-1" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deleteConfirmed">Ya, Hapus</span>
                    <span wire:loading wire:target="deleteConfirmed">Menghapus...</span>
                </button>
            </div>
        </div>
    </div>
</div>
