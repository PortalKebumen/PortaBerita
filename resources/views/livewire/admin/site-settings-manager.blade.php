<div class="space-y-6"
    x-data="{ tab: 'umum' }"
    x-on:settings-focus-tab.window="tab = $event.detail.tab">

    <div class="alert-info">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="9"></circle><path d="M12 16v-5M12 8h.01"></path></svg>
        <div>Pengaturan adalah data <strong class="font-semibold">singleton</strong> (satu baris konfigurasi per situs) &mdash; halaman ini hanya menyediakan aksi <strong class="font-semibold">lihat &amp; perbarui</strong>, bukan tambah/hapus seperti data lain.</div>
    </div>

    {{-- Tabs --}}
    <div class="flex border-b border-[#CDD3DF] overflow-x-auto overflow-y-hidden pb-px">
        <button type="button" class="tab-btn whitespace-nowrap" :class="{ 'is-active': tab === 'umum' }" @click="tab = 'umum'">Umum</button>
        <button type="button" class="tab-btn whitespace-nowrap" :class="{ 'is-active': tab === 'seo' }" @click="tab = 'seo'">SEO</button>
        <button type="button" class="tab-btn whitespace-nowrap" :class="{ 'is-active': tab === 'sosial' }" @click="tab = 'sosial'">Media Sosial</button>
        @can('settings.update')
            <button type="button" class="tab-btn whitespace-nowrap text-danger" :class="{ 'is-active': tab === 'bahaya' }" @click="tab = 'bahaya'">Zona Bahaya</button>
        @endcan
    </div>

    <form wire:submit="save">
        <fieldset class="min-w-0" @disabled(! auth()->user()->can('settings.update'))>

            {{-- ===== TAB UMUM ===== --}}
            <div x-show="tab === 'umum'">
                <div class="card card-body">
                    <h2 class="text-[15px] font-bold mb-4">Informasi Situs</h2>

                    <div class="mb-5">
                        <label class="form-label">Logo Situs</label>
                        <div class="flex items-center gap-4">
                            @if ($logoPreviewUrl)
                                <img src="{{ $logoPreviewUrl }}" alt="Logo situs" class="w-16 h-16 rounded-lg object-contain border border-[#E4E8EF] bg-white p-1">
                            @else
                                <div class="w-16 h-16 rounded-lg flex items-center justify-center text-white font-bold text-[11px] bg-brand-900">
                                    {{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $siteName), 0, 2)) ?: 'PK' }}
                                </div>
                            @endif
                            <div class="flex items-center gap-2">
                                <button type="button" class="btn-secondary btn-sm"
                                    wire:click="$dispatch('open-media-picker', { target: 'site-logo' })">
                                    {{ $logoPreviewUrl ? 'Ganti Logo' : 'Pilih Logo' }}
                                </button>
                                @if ($logoPreviewUrl)
                                    <button type="button" class="btn-sm text-danger hover:underline" wire:click="clearLogo">Hapus</button>
                                @endif
                            </div>
                        </div>
                        @error('logoMediaId') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-2">
                        <div>
                            <label class="form-label">Nama Situs <span class="text-danger">*</span></label>
                            <input type="text" wire:model="siteName" class="form-input @error('siteName') is-error @enderror">
                            @error('siteName') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Tagline</label>
                            <input type="text" wire:model="tagline" class="form-input @error('tagline') is-error @enderror">
                            @error('tagline') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Email Redaksi</label>
                            <input type="email" wire:model="editorialEmail" class="form-input @error('editorialEmail') is-error @enderror">
                            @error('editorialEmail') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" wire:model="phone" class="form-input @error('phone') is-error @enderror">
                            @error('phone') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Alamat Redaksi</label>
                            <input type="text" wire:model="address" class="form-input @error('address') is-error @enderror">
                            @error('address') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Zona Waktu</label>
                            <select wire:model="timezone" class="form-select @error('timezone') is-error @enderror">
                                <option value="Asia/Jakarta">Asia/Jakarta (WIB, UTC+7)</option>
                                <option value="Asia/Makassar">Asia/Makassar (WITA, UTC+8)</option>
                                <option value="Asia/Jayapura">Asia/Jayapura (WIT, UTC+9)</option>
                            </select>
                            @error('timezone') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Bahasa Default</label>
                            <select wire:model="locale" class="form-select @error('locale') is-error @enderror">
                                <option value="id">Bahasa Indonesia</option>
                                <option value="en">English</option>
                            </select>
                            @error('locale') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== TAB SEO ===== --}}
            <div x-show="tab === 'seo'" style="display: none;" class="space-y-6">
                <div class="card card-body">
                    <h2 class="text-[15px] font-bold mb-4">Pengaturan SEO Default</h2>

                    <div class="mb-5">
                        <label class="form-label">Meta Title Default</label>
                        <input type="text" wire:model="metaTitle" class="form-input @error('metaTitle') is-error @enderror">
                        <div class="form-hint">Dipakai bila artikel tidak mengisi meta title khusus.</div>
                        @error('metaTitle') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Meta Description Default</label>
                        <textarea wire:model="metaDescription" rows="2" class="form-textarea @error('metaDescription') is-error @enderror"></textarea>
                        @error('metaDescription') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Gambar OG Default</label>
                        <div class="flex items-center gap-3 border border-[#E4E8EF] rounded-lg p-3">
                            <div class="w-20 h-12 rounded-lg shrink-0 overflow-hidden bg-[#F1F3F7] flex items-center justify-center text-[#848CA3]">
                                @if ($ogPreviewUrl)
                                    <img src="{{ $ogPreviewUrl }}" alt="Pratinjau OG" class="w-full h-full object-cover">
                                @else
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="14" rx="2"></rect><circle cx="8.5" cy="10" r="1.6"></circle><path d="M3 16l5-4 4 3 3-3 6 5"></path></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[12.5px] font-medium truncate">{{ $ogPreviewUrl ? 'Gambar OG terpilih' : 'Belum ada gambar dipilih' }}</div>
                                <div class="text-[11px] text-[#848CA3] mt-0.5">Rekomendasi 1200&times;630px. Dipakai bila artikel tidak punya gambar sampul/OG.</div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" class="btn-secondary btn-sm"
                                    wire:click="$dispatch('open-media-picker', { target: 'site-og' })">Pilih Gambar</button>
                                @if ($ogPreviewUrl)
                                    <button type="button" class="btn-sm text-danger hover:underline" wire:click="clearOg">Hapus</button>
                                @endif
                            </div>
                        </div>
                        @error('ogMediaId') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="form-label">Google Analytics ID</label>
                            <input type="text" wire:model="gaId" placeholder="G-XXXXXXXXXX" class="form-input font-mono @error('gaId') is-error @enderror">
                            @error('gaId') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Google Search Console</label>
                            <input type="text" wire:model="gscVerification" placeholder="Kode verifikasi meta tag" class="form-input font-mono @error('gscVerification') is-error @enderror">
                            @error('gscVerification') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Sitemap XML --}}
                <div class="card card-body">
                    <h2 class="text-[15px] font-bold mb-1">Sitemap XML</h2>
                    <p class="text-[12.5px] text-[#6C7387] mb-4">Dibuat otomatis dan diperbarui berkala oleh scheduler. Daftarkan alamat di bawah ke Google Search Console.</p>

                    <div class="flex flex-wrap items-center justify-between gap-3 border border-[#E4E8EF] rounded-lg p-3.5">
                        <div class="min-w-0">
                            <a href="{{ $this->sitemapInfo['url'] }}" target="_blank" rel="noopener" class="text-[13px] font-mono text-brand-600 hover:underline break-all">{{ $this->sitemapInfo['url'] }}</a>
                            <div class="text-[11.5px] text-[#848CA3] mt-1">
                                @if ($this->sitemapInfo['exists'])
                                    Terakhir diperbarui: {{ $this->sitemapInfo['updated'] }}
                                @else
                                    Belum pernah dibuat.
                                @endif
                            </div>
                        </div>
                        @can('settings.update')
                            <button type="button" class="btn-outline btn-sm shrink-0" wire:click="regenerateSitemap" wire:loading.attr="disabled" wire:target="regenerateSitemap">
                                <span wire:loading.remove wire:target="regenerateSitemap">Perbarui Sekarang</span>
                                <span wire:loading wire:target="regenerateSitemap">Memproses...</span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- ===== TAB MEDIA SOSIAL ===== --}}
            <div x-show="tab === 'sosial'" style="display: none;">
                <div class="card card-body">
                    <h2 class="text-[15px] font-bold mb-4">Tautan Media Sosial</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="form-label">Facebook</label>
                            <input type="url" wire:model="facebook" placeholder="https://facebook.com/..." class="form-input @error('facebook') is-error @enderror">
                            @error('facebook') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Instagram</label>
                            <input type="url" wire:model="instagram" placeholder="https://instagram.com/..." class="form-input @error('instagram') is-error @enderror">
                            @error('instagram') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">X (Twitter)</label>
                            <input type="url" wire:model="xTwitter" placeholder="https://x.com/..." class="form-input @error('xTwitter') is-error @enderror">
                            @error('xTwitter') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">YouTube</label>
                            <input type="url" wire:model="youtube" placeholder="https://youtube.com/@..." class="form-input @error('youtube') is-error @enderror">
                            @error('youtube') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">WhatsApp Redaksi</label>
                            <input type="text" wire:model="whatsapp" placeholder="+62 812-3456-7890" class="form-input @error('whatsapp') is-error @enderror">
                            @error('whatsapp') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- Bar simpan (sticky) --}}
        @can('settings.update')
            <div x-show="tab !== 'bahaya'"
                class="flex justify-end gap-2.5 mt-6 sticky bottom-0 bg-[#F7F8FA]/95 backdrop-blur-sm py-3 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 border-t border-[#E4E8EF] z-10">
                <button type="button" class="btn-secondary" wire:click="cancelChanges">Batalkan Perubahan</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        @endcan
    </form>

    {{-- ===== TAB ZONA BAHAYA (di luar form) ===== --}}
    @can('settings.update')
        <div x-show="tab === 'bahaya'" style="display: none;">
            <div class="card overflow-hidden">
                <div class="card-header"><h2 class="text-[15px] font-bold text-danger">Zona Bahaya</h2></div>
                <div class="divide-y divide-[#E4E8EF]">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <div class="text-[13px] font-semibold">Bersihkan Cache Situs</div>
                            <div class="text-[11.5px] text-[#848CA3] mt-0.5">Menghapus cache halaman &amp; query untuk memuat ulang data terbaru.</div>
                        </div>
                        <button type="button" class="btn-outline btn-sm shrink-0" wire:click="clearCache" wire:loading.attr="disabled" wire:target="clearCache">
                            <span wire:loading.remove wire:target="clearCache">Bersihkan Cache</span>
                            <span wire:loading wire:target="clearCache">Memproses...</span>
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <div class="text-[13px] font-semibold">Ekspor Seluruh Data</div>
                            <div class="text-[11.5px] text-[#848CA3] mt-0.5">Unduh salinan artikel, pengguna, dan pengaturan dalam format JSON.</div>
                        </div>
                        <button type="button" class="btn-outline btn-sm shrink-0" wire:click="exportData" wire:loading.attr="disabled" wire:target="exportData">
                            <span wire:loading.remove wire:target="exportData">Ekspor Data</span>
                            <span wire:loading wire:target="exportData">Menyiapkan...</span>
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <div class="text-[13px] font-semibold text-danger">Reset Pengaturan ke Default</div>
                            <div class="text-[11.5px] text-[#848CA3] mt-0.5">Mengembalikan seluruh pengaturan (bukan konten) ke nilai bawaan sistem.</div>
                        </div>
                        <button type="button" class="btn-danger btn-sm shrink-0" wire:click="openResetModal">Reset Pengaturan</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Modal: Reset Pengaturan --}}
    @if ($showResetModal)
        <div class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            wire:click.self="closeResetModal"
            wire:keydown.escape.window="closeResetModal">
            <div class="bg-white rounded-card max-w-[420px] w-full p-6 shadow-xl">
                <div class="w-11 h-11 rounded-full bg-danger-bg text-danger flex items-center justify-center mb-4">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"></path><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"></path></svg>
                </div>
                <h3 class="text-[16px] font-bold mb-1.5">Reset semua pengaturan?</h3>
                <p class="text-[13px] text-[#6C7387] mb-5">Seluruh pengaturan Umum, SEO, dan Media Sosial (termasuk logo dan gambar OG) akan dikembalikan ke nilai bawaan sistem. Konten artikel tidak terpengaruh.</p>
                <div class="flex justify-end gap-2.5">
                    <button type="button" class="btn-secondary" wire:click="closeResetModal">Batal</button>
                    <button type="button" class="btn-danger" wire:click="resetToDefaults" wire:loading.attr="disabled" wire:target="resetToDefaults">
                        <span wire:loading.remove wire:target="resetToDefaults">Ya, Reset</span>
                        <span wire:loading wire:target="resetToDefaults">Mereset...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>