@php
    $shortLocations = ['navbar' => 'Navbar', 'footer' => 'Footer', 'footer_bottom' => 'Footer Bawah'];
@endphp

<div class="space-y-6">
    @if ($mode === 'list')
        {{-- ==================== DAFTAR HALAMAN ==================== --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative flex-1 max-w-[320px]">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Cari judul atau slug..."
                        class="form-input pl-9">
                </div>

                <select wire:model.live="statusFilter" class="form-select w-44">
                    <option value="all">Semua Status</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @can('pages.create')
                <a href="{{ route('admin.halaman.index', ['mode' => 'create']) }}" class="btn-primary shrink-0">
                    + Tambah Halaman
                </a>
            @endcan
        </div>

        <div class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[#6C7387] border-b border-[#E4E8EF]">
                        <th class="px-5 py-3.5 font-medium">JUDUL</th>
                        <th class="px-4 py-3.5 font-medium">SLUG</th>
                        <th class="px-4 py-3.5 font-medium">STATUS</th>
                        <th class="px-4 py-3.5 font-medium">DIPAKAI DI MENU</th>
                        <th class="px-4 py-3.5 font-medium">TERAKHIR DIUBAH</th>
                        <th class="px-5 py-3.5 font-medium text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4E8EF]">
                    @forelse ($this->pages as $page)
                        <tr class="hover:bg-gray-50/50 transition-colors" wire:key="page-{{ $page->id }}">
                            <td class="px-5 py-3.5">
                                @can('pages.update')
                                    <a href="{{ route('admin.halaman.index', ['mode' => 'edit', 'id' => $page->id]) }}" class="font-medium text-[#1B2033] hover:text-brand-600">{{ $page->title }}</a>
                                @else
                                    <span class="font-medium text-[#1B2033]">{{ $page->title }}</span>
                                @endcan
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-[#848CA3]">
                                /halaman/{{ $page->slug }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($page->isPublished())
                                    <span class="badge-success"><span class="badge-dot"></span>{{ $statuses['published'] }}</span>
                                @else
                                    <span class="badge-warning"><span class="badge-dot"></span>{{ $statuses['draft'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @php $usedIn = $page->menuItems->pluck('location')->unique(); @endphp
                                @if ($usedIn->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($usedIn as $loc)
                                            <span class="badge-info">{{ $shortLocations[$loc] ?? $loc }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-[#848CA3]">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-[#6C7387]">
                                <div>{{ $page->updated_at?->translatedFormat('d M Y, H:i') }}</div>
                                @if ($page->editor)
                                    <div class="text-[#848CA3] mt-0.5">oleh {{ $page->editor->name }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($page->isPublished())
                                        <a href="{{ $page->url() }}" target="_blank" rel="noopener"
                                            class="btn-icon text-[#6C7387] hover:text-brand-600 hover:bg-brand-50"
                                            title="Lihat Halaman">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                    @endif

                                    @can('pages.update')
                                        <a href="{{ route('admin.halaman.index', ['mode' => 'edit', 'id' => $page->id]) }}"
                                            class="btn-icon text-[#6C7387] hover:text-brand-600 hover:bg-brand-50"
                                            title="Edit Halaman">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M12 20h9"></path>
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('pages.delete')
                                        <button type="button"
                                            wire:click="confirmDelete({{ $page->id }})"
                                            class="btn-icon text-danger hover:bg-danger-bg"
                                            title="Hapus Halaman">
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
                            <td colspan="6" class="px-5 py-10 text-center text-[#848CA3]">
                                @if (trim($search) !== '' || $statusFilter !== 'all')
                                    Tidak ada halaman yang cocok dengan filter.
                                @else
                                    Belum ada halaman. Buat halaman seperti "Tentang Kami" atau "Pedoman Media Siber".
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $this->pages->links() }}
        </div>
    @else
        {{-- ==================== FORMULIR TAMBAH / EDIT ==================== --}}
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.halaman.index') }}" class="btn-icon bg-[#F1F3F7] shrink-0 mt-0.5" aria-label="Kembali ke Halaman">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div class="min-w-0">
                <div class="text-[12px] text-[#848CA3] mb-1">
                    <a href="{{ route('admin.halaman.index') }}" class="hover:text-brand-600 hover:underline">Halaman</a>
                    <span class="mx-1">/</span>
                    <span class="text-[#171B28] font-medium">{{ $editingId ? 'Edit Halaman' : 'Tambah Halaman' }}</span>
                </div>
                <h2 class="text-[20px] font-bold leading-tight">{{ $editingId ? 'Edit Halaman' : 'Tambah Halaman Baru' }}</h2>
                <p class="text-[13px] text-[#6C7387] mt-1">Halaman statis seperti Tentang Kami, Redaksi, atau Pedoman Media Siber.</p>
            </div>
        </div>

        <form wire:submit.prevent="save" class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_300px] gap-6 items-start">
            {{-- Kolom utama --}}
            <div class="space-y-6 min-w-0">
                <div class="card p-6 space-y-5">
                    <div>
                        <label class="form-label">Judul Halaman <span class="text-danger">*</span></label>
                        <input type="text"
                            wire:model.live.debounce.400ms="title"
                            placeholder="Misal: Tentang Kami"
                            class="form-input @error('title') is-error @enderror">
                        @error('title') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Slug (URL Halaman) <span class="text-danger">*</span></label>
                        <div class="flex items-center gap-2">
                            <span class="text-[13px] text-[#848CA3] shrink-0">/halaman/</span>
                            <input type="text"
                                wire:model.live.debounce.400ms="slug"
                                placeholder="tentang-kami"
                                class="form-input font-mono @error('slug') is-error @enderror">
                        </div>
                        @error('slug')
                            <p class="form-error-text">{{ $message }}</p>
                        @else
                            <p class="text-[11px] text-[#848CA3] mt-1">Diisi otomatis dari judul. Bisa diedit manual — hanya huruf kecil, angka, dan tanda hubung.</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Isi Halaman</label>
                        <x-editor name="content" bind="content" target="page-content" placeholder="Tulis isi halaman di sini..." value="{{ $content }}" />
                        @error('content') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- SEO --}}
                <div class="card p-6 space-y-5">
                    <div>
                        <h3 class="font-semibold text-[#1B2033]">SEO</h3>
                        <p class="text-[12px] text-[#848CA3] mt-0.5">Opsional. Mengatur tampilan halaman di hasil mesin pencari.</p>
                    </div>

                    <div x-data>
                        <div class="flex items-center justify-between">
                            <label class="form-label">Meta Title</label>
                            <span class="text-[11px] text-[#848CA3] mb-1.5"
                                :class="($wire.meta_title || '').length > 60 ? 'text-danger' : ''"
                                x-text="($wire.meta_title || '').length + '/60'"></span>
                        </div>
                        <input type="text"
                            wire:model="meta_title"
                            maxlength="60"
                            placeholder="Judul untuk mesin pencari (maks. 60 karakter)"
                            class="form-input @error('meta_title') is-error @enderror">
                        @error('meta_title')
                            <p class="form-error-text">{{ $message }}</p>
                        @else
                            <p class="text-[11px] text-[#848CA3] mt-1">Biarkan kosong untuk menggunakan judul halaman.</p>
                        @enderror
                    </div>

                    <div x-data>
                        <div class="flex items-center justify-between">
                            <label class="form-label">Meta Description</label>
                            <span class="text-[11px] text-[#848CA3] mb-1.5"
                                :class="($wire.meta_description || '').length > 160 ? 'text-danger' : ''"
                                x-text="($wire.meta_description || '').length + '/160'"></span>
                        </div>
                        <textarea wire:model="meta_description"
                            maxlength="160"
                            rows="3"
                            placeholder="Deskripsi singkat untuk mesin pencari (maks. 160 karakter)"
                            class="form-input form-textarea @error('meta_description') is-error @enderror"></textarea>
                        @error('meta_description') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Kolom samping --}}
            <div class="space-y-6 lg:sticky lg:top-20">
                <div class="card p-5 space-y-4">
                    <h3 class="font-semibold text-[#1B2033]">Terbitkan</h3>

                    <div>
                        <label class="form-label">Status</label>
                        <select wire:model="status" class="form-select @error('status') is-error @enderror">
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="form-error-text">{{ $message }}</p> @enderror
                        <p class="text-[11px] text-[#848CA3] mt-1">Halaman draft tidak bisa diakses publik dan tidak tampil di menu.</p>
                    </div>

                    @if ($this->editingPage)
                        <div class="text-[12px] text-[#6C7387] space-y-1 border-t border-[#E4E8EF] pt-3">
                            <div>Terakhir diubah: {{ $this->editingPage->updated_at?->translatedFormat('d M Y, H:i') }}</div>
                            @if ($this->editingPage->editor)
                                <div>Oleh: {{ $this->editingPage->editor->name }}</div>
                            @endif
                            @if ($this->editingPage->isPublished())
                                <a href="{{ $this->editingPage->url() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-brand-600 hover:underline font-medium">
                                    Lihat halaman
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-3 border-t border-[#E4E8EF]">
                        @if ($editingId)
                            @can('pages.delete')
                                <button type="button" wire:click="confirmDelete({{ $editingId }})" class="text-[13px] font-semibold text-danger hover:underline mr-auto">
                                    Hapus
                                </button>
                            @endcan
                        @endif
                        <a href="{{ route('admin.halaman.index') }}" class="btn-secondary {{ $editingId ? '' : 'ml-auto' }}">Batal</a>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Perbarui' : 'Simpan' }}</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </div>

                @can('menus.update')
                    <div class="card p-5 space-y-3">
                        <div>
                            <h3 class="font-semibold text-[#1B2033]">Tambahkan ke Menu</h3>
                            <p class="text-[12px] text-[#848CA3] mt-0.5">Centang lokasi menu tempat halaman ini ditampilkan. Hilangkan centang untuk melepasnya.</p>
                        </div>
                        <div class="space-y-2.5">
                            @foreach ($locations as $key => $label)
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model="menuLocations" value="{{ $key }}" class="form-checkbox h-4 w-4 rounded accent-brand-600">
                                    <span class="text-[13px]">{{ $shortLocations[$key] ?? $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('menuLocations.*') <p class="form-error-text">{{ $message }}</p> @enderror
                        @can('menus.view')
                            <p class="text-[11px] text-[#848CA3]">Atur urutan & susunan di <a href="{{ route('admin.menu.index') }}" class="text-brand-600 hover:underline">Menu</a>.</p>
                        @endcan
                    </div>
                @endcan
            </div>
        </form>

        {{-- Wajib untuk tombol sisip gambar di <x-editor> --}}
        <livewire:media-picker />
    @endif

    {{-- ==================== MODAL KONFIRMASI HAPUS ==================== --}}
    <div class="modal-overlay {{ $showDeleteModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-sm p-6 space-y-4 shadow-xl text-center">
            <div class="w-12 h-12 rounded-full bg-danger-bg text-danger mx-auto flex items-center justify-center">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <div>
                <h3 class="font-semibold text-[#1B2033]">Hapus Halaman</h3>
                <p class="text-sm text-[#6C7387] mt-1.5 leading-relaxed">
                    Apakah Anda yakin ingin menghapus halaman
                    <strong class="text-[#1B2033]">"{{ $deletingName }}"</strong>?
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            @if ($deletingLocations)
                <div class="alert-warning text-left">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
                    </svg>
                    <div>Halaman ini dipakai di menu: <strong>{{ implode(', ', $deletingLocations) }}</strong>. Item menu tersebut (beserta sub-itemnya) ikut terhapus.</div>
                </div>
            @endif

            <div class="flex justify-end gap-2.5 pt-2">
                <button type="button" wire:click="closeModals" class="btn-secondary flex-1">Batal</button>
                <button type="button" wire:click="deleteConfirmed" class="btn-danger flex-1">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>
