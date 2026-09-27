<div class="space-y-6">
    {{-- Tabs Navigasi: Kategori & Tag (Reaktif Livewire tanpa reload & tanpa query string URL) --}}
    <div class="flex gap-2 border-b border-[#CDD3DF]">
        <button type="button"
            wire:click="setTab('kategori')"
            class="tab-btn {{ $tab === 'kategori' ? 'is-active' : '' }}">
            Kategori
        </button>
        <button type="button"
            wire:click="setTab('tag')"
            class="tab-btn {{ $tab === 'tag' ? 'is-active' : '' }}">
            Tag
        </button>
    </div>

    {{-- ==================== TAB 1: KATEGORI ==================== --}}
    @if ($tab === 'kategori')
        {{-- Toolbar: Search Bar + Filter Type + Tombol Tambah Kategori (Gaya Pengguna & Role) --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative flex-1 max-w-[320px]">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Cari kategori..."
                        class="form-input pl-9">
                </div>

                <select wire:model.live="categoryTypeFilter" class="form-select w-44">
                    <option value="all">Semua Jenis</option>
                    <option value="main">Kategori Utama</option>
                    <option value="sub">Sub-kategori</option>
                </select>
            </div>

            @can('categories.create')
                <button type="button" wire:click="openAddCategory" class="btn-primary shrink-0">
                    + Tambah Kategori
                </button>
            @endcan
        </div>

        {{-- Tabel Kategori (Gaya Card & Table Pengguna & Role) --}}
        <div class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[#6C7387] border-b border-[#E4E8EF]">
                        <th class="px-5 py-3.5 font-medium">NAMA</th>
                        <th class="px-4 py-3.5 font-medium">SLUG</th>
                        <th class="px-4 py-3.5 font-medium">SUB-KATEGORI DARI</th>
                        <th class="px-4 py-3.5 font-medium text-center">JML. ARTIKEL</th>
                        <th class="px-5 py-3.5 font-medium text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4E8EF]">
                    @forelse ($this->categories as $cat)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <span class="font-medium text-[#1B2033]">{{ $cat->name }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-[#848CA3]">
                                /{{ $cat->slug }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($cat->parent)
                                    <span class="text-gray-700 font-medium text-xs">{{ $cat->parent->name }}</span>
                                @else
                                    <span class="badge-neutral">Utama</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-medium text-[#6C7387]">
                                {{ $cat->articles_count ?? 0 }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @can('categories.update')
                                        <button type="button"
                                            wire:click="openEditCategory({{ $cat->id }})"
                                            class="btn-icon text-[#6C7387] hover:text-brand-600 hover:bg-brand-50"
                                            title="Edit Kategori">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M12 20h9"></path>
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                            </svg>
                                        </button>
                                    @endcan

                                    @can('categories.delete')
                                        <button type="button"
                                            wire:click="confirmDeleteCategory({{ $cat->id }})"
                                            class="btn-icon text-danger hover:bg-danger-bg"
                                            title="Hapus Kategori">
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
                            <td colspan="5" class="px-5 py-10 text-center text-[#848CA3]">
                                Belum ada kategori yang ditambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $this->categories->links() }}
        </div>
    @endif

    {{-- ==================== TAB 2: TAG ==================== --}}
    @if ($tab === 'tag')
        {{-- Toolbar: Search Bar + Tombol Tambah Tag --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="relative flex-1 max-w-[320px]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari tag..."
                    class="form-input pl-9">
            </div>

            @can('categories.create')
                <button type="button" wire:click="openAddTag" class="btn-primary shrink-0">
                    + Tambah Tag
                </button>
            @endcan
        </div>

        {{-- Tabel Tag --}}
        <div class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[#6C7387] border-b border-[#E4E8EF]">
                        <th class="px-5 py-3.5 font-medium">NAMA TAG</th>
                        <th class="px-4 py-3.5 font-medium">SLUG</th>
                        <th class="px-4 py-3.5 font-medium text-center">JML. ARTIKEL</th>
                        <th class="px-5 py-3.5 font-medium text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4E8EF]">
                    @forelse ($this->tags as $t)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <span class="font-medium text-[#1B2033]">#{{ $t->name }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-[#848CA3]">
                                /{{ $t->slug }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-medium text-[#6C7387]">
                                {{ $t->articles_count ?? 0 }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @can('categories.update')
                                        <button type="button"
                                            wire:click="openEditTag({{ $t->id }})"
                                            class="btn-icon text-[#6C7387] hover:text-brand-600 hover:bg-brand-50"
                                            title="Edit Tag">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M12 20h9"></path>
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                            </svg>
                                        </button>
                                    @endcan

                                    @can('categories.delete')
                                        <button type="button"
                                            wire:click="confirmDeleteTag({{ $t->id }})"
                                            class="btn-icon text-danger hover:bg-danger-bg"
                                            title="Hapus Tag">
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
                            <td colspan="4" class="px-5 py-10 text-center text-[#848CA3]">
                                Belum ada tag yang ditambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $this->tags->links() }}
        </div>
    @endif

    {{-- ==================== MODAL KATEGORI ==================== --}}
    <div class="modal-overlay {{ $showCategoryModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md shadow-xl animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#E4E8EF]">
                <h3 class="font-semibold text-[#1B2033]">{{ $editingCategoryId ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
                <button type="button" wire:click="closeModals" class="text-[#848CA3] hover:text-gray-700 text-lg leading-none">&times;</button>
            </div>

            <form wire:submit="saveCategory" class="px-6 py-5 space-y-4">
                <div>
                    <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text"
                        wire:model="categoryName"
                        placeholder="Misal: Wisata, Berita Desa"
                        class="form-input @error('categoryName') is-error @enderror">
                    @error('categoryName') <p class="form-error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Kategori Induk (Parent)</label>
                    <select wire:model="categoryParentId" class="form-select @error('categoryParentId') is-error @enderror">
                        <option value="">-- Tanpa Induk (Kategori Utama) --</option>
                        @foreach ($this->parentCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryParentId')
                        <p class="form-error-text">{{ $message }}</p>
                    @else
                        <span class="text-xs text-[#848CA3] mt-1 block">Pilih kategori utama jika ini sub-kategori (maksimal 1 tingkat hierarki).</span>
                    @enderror
                </div>

                <div class="flex justify-end gap-2.5 pt-2 border-t border-[#E4E8EF]">
                    <button type="button" wire:click="closeModals" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary">
                        {{ $editingCategoryId ? 'Simpan Perubahan' : 'Simpan Kategori' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL TAG ==================== --}}
    <div class="modal-overlay {{ $showTagModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md shadow-xl animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#E4E8EF]">
                <h3 class="font-semibold text-[#1B2033]">{{ $editingTagId ? 'Edit Tag' : 'Tambah Tag' }}</h3>
                <button type="button" wire:click="closeModals" class="text-[#848CA3] hover:text-gray-700 text-lg leading-none">&times;</button>
            </div>

            <form wire:submit="saveTag" class="px-6 py-5 space-y-4">
                <div>
                    <label class="form-label">Nama Tag <span class="text-danger">*</span></label>
                    <input type="text"
                        wire:model="tagName"
                        placeholder="Misal: Kuliner Kebumen"
                        class="form-input @error('tagName') is-error @enderror">
                    @error('tagName') <p class="form-error-text">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2.5 pt-2 border-t border-[#E4E8EF]">
                    <button type="button" wire:click="closeModals" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary">
                        {{ $editingTagId ? 'Simpan Perubahan' : 'Simpan Tag' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL KONFIRMASI HAPUS ==================== --}}
    <div class="modal-overlay {{ $showDeleteModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-sm p-6 space-y-4 shadow-xl text-center">
            <div class="w-12 h-12 rounded-full bg-danger-bg text-danger mx-auto flex items-center justify-center">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <div>
                <h3 class="font-semibold text-[#1B2033]">
                    Hapus {{ $deleteType === 'category' ? 'Kategori' : 'Tag' }}
                </h3>
                <p class="text-sm text-[#6C7387] mt-1.5 leading-relaxed">
                    Apakah Anda yakin ingin menghapus {{ $deleteType === 'category' ? 'kategori' : 'tag' }}
                    <strong class="text-[#1B2033]">"{{ $deletingName }}"</strong>?
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <div class="flex justify-end gap-2.5 pt-2">
                <button type="button" wire:click="closeModals" class="btn-secondary flex-1">Batal</button>
                <button type="button" wire:click="deleteConfirmed" class="btn-danger flex-1">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>
