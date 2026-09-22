<x-layouts.admin title="Kategori & Tag">

    {{-- Tabs Navigasi: Kategori & Tag --}}
    <div class="mb-6 border-b border-[#E4E8EF]">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button type="button" id="tab-btn-kategori" onclick="switchTab('kategori')"
                class="tab-nav-btn py-3 px-1 border-b-2 font-bold text-[14px] transition-colors border-brand-600 text-brand-600">
                Kategori
            </button>
            <button type="button" id="tab-btn-tag" onclick="switchTab('tag')"
                class="tab-nav-btn py-3 px-1 border-b-2 font-medium text-[14px] transition-colors border-transparent text-[#848CA3] hover:text-gray-700 hover:border-gray-300">
                Tag
            </button>
        </nav>
    </div>

    {{-- ==================== TAB 1: KATEGORI ==================== --}}
    <div id="tab-panel-kategori" class="tab-panel">
        {{-- Toolbar: Search Input + Tombol Tambah Kategori --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
            <form action="{{ route('admin.kategori-tag.index') }}" method="GET" class="relative max-w-sm w-full">
                <input type="hidden" name="tab" value="kategori">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-[#848CA3]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke-width="2"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"></line>
                    </svg>
                </span>
                <input type="text" name="q_cat" value="{{ request('q_cat') }}" placeholder="Cari kategori..."
                    class="w-full pl-10 pr-4 py-2 text-[13px] bg-white border border-[#E4E8EF] rounded-xl placeholder-[#848CA3] focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm transition">
            </form>

            <button type="button" onclick="openAddCategoryModal()"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-brand-900 hover:bg-brand-800 text-white font-semibold text-[13px] rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19" stroke-width="2.5" stroke-linecap="round"></line>
                    <line x1="5" y1="12" x2="19" y2="12" stroke-width="2.5" stroke-linecap="round"></line>
                </svg>
                <span>Tambah Kategori</span>
            </button>
        </div>

        {{-- Kartu Tabel Kategori --}}
        <div class="bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] border-collapse">
                    <thead>
                        <tr class="border-b border-[#E4E8EF] bg-gray-50/40">
                            <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-6 py-3.5">NAMA</th>
                            <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-5 py-3.5">SLUG</th>
                            <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-5 py-3.5">SUB-KATEGORI DARI</th>
                            <th class="text-center text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-4 py-3.5">JML. ARTIKEL</th>
                            <th class="text-right text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-6 py-3.5">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4E8EF]">
                        @forelse ($categories as $cat)
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="px-6 py-4 text-[13.5px] font-semibold text-gray-900 whitespace-nowrap">
                                    {{ $cat->name }}
                                </td>
                                <td class="px-5 py-4 text-[12.5px] font-body text-[#848CA3] whitespace-nowrap">
                                    /{{ $cat->slug }}
                                </td>
                                <td class="px-5 py-4 text-[12.5px] whitespace-nowrap">
                                    @if ($cat->parent)
                                        <span class="text-gray-700 font-medium">{{ $cat->parent->name }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#EEF1FA] text-[#848CA3]">
                                            Utama
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-[13px] text-center font-medium text-gray-700 whitespace-nowrap">
                                    {{ $cat->articles_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        {{-- Tombol Edit --}}
                                        <button type="button"
                                            onclick="openEditCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->parent_id ?? '' }}', {{ $cat->children ? $cat->children->count() : 0 }})"
                                            title="Edit Kategori"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-brand-600 hover:bg-[#F1F3F7] transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        {{-- Tombol Hapus (Trigger Pop-up Modal) --}}
                                        <button type="button"
                                            onclick="confirmDeleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', {{ $cat->articles_count ?? 0 }})"
                                            title="Hapus Kategori"
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
                                <td colspan="5" class="px-6 py-12 text-center text-[13px] text-[#848CA3]">
                                    Belum ada kategori yang ditambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="px-6 py-3.5 border-t border-[#E4E8EF]">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ==================== TAB 2: TAG ==================== --}}
    <div id="tab-panel-tag" class="tab-panel hidden">
        {{-- Toolbar: Search Tag + Tombol Tambah Tag --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
            <form action="{{ route('admin.kategori-tag.index') }}" method="GET" class="relative max-w-sm w-full">
                <input type="hidden" name="tab" value="tag">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-[#848CA3]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke-width="2"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"></line>
                    </svg>
                </span>
                <input type="text" name="q_tag" value="{{ request('q_tag') }}" placeholder="Cari tag..."
                    class="w-full pl-10 pr-4 py-2 text-[13px] bg-white border border-[#E4E8EF] rounded-xl placeholder-[#848CA3] focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm transition">
            </form>

            <button type="button" onclick="openAddTagModal()"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-brand-900 hover:bg-brand-800 text-white font-semibold text-[13px] rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19" stroke-width="2.5" stroke-linecap="round"></line>
                    <line x1="5" y1="12" x2="19" y2="12" stroke-width="2.5" stroke-linecap="round"></line>
                </svg>
                <span>Tambah Tag</span>
            </button>
        </div>

        {{-- Kartu Tabel Tag --}}
        <div class="bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] border-collapse">
                    <thead>
                        <tr class="border-b border-[#E4E8EF] bg-gray-50/40">
                            <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-6 py-3.5">NAMA TAG</th>
                            <th class="text-left text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-5 py-3.5">SLUG</th>
                            <th class="text-center text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-4 py-3.5">JML. ARTIKEL</th>
                            <th class="text-right text-[11px] font-bold tracking-wider text-[#848CA3] uppercase px-6 py-3.5">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4E8EF]">
                        @forelse ($tags as $t)
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="px-6 py-4 text-[13.5px] font-semibold text-gray-900 whitespace-nowrap">
                                    #{{ $t->name }}
                                </td>
                                <td class="px-5 py-4 text-[12.5px] font-body text-[#848CA3] whitespace-nowrap">
                                    /{{ $t->slug }}
                                </td>
                                <td class="px-4 py-4 text-[13px] text-center font-medium text-gray-700 whitespace-nowrap">
                                    {{ $t->articles_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        {{-- Tombol Edit --}}
                                        <button type="button"
                                            onclick="openEditTagModal({{ $t->id }}, '{{ addslashes($t->name) }}')"
                                            title="Edit Tag"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-brand-600 hover:bg-[#F1F3F7] transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        {{-- Tombol Hapus (Trigger Pop-up Modal) --}}
                                        <button type="button"
                                            onclick="confirmDeleteTag({{ $t->id }}, '{{ addslashes($t->name) }}')"
                                            title="Hapus Tag"
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
                                <td colspan="4" class="px-6 py-12 text-center text-[13px] text-[#848CA3]">
                                    Belum ada tag yang ditambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tags->hasPages())
                <div class="px-6 py-3.5 border-t border-[#E4E8EF]">
                    {{ $tags->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- ============================ MODAL WINDOWS ============================== --}}
    {{-- ========================================================================= --}}

    {{-- 1. Modal Tambah Kategori --}}
    <div id="modal-add-category" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[480px] w-full p-6 shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4E8EF] mb-4">
                <div>
                    <h3 class="text-[16px] font-bold text-gray-900">Tambah Kategori</h3>
                    <p class="text-[12px] text-[#848CA3] mt-0.5">Buat kategori utama atau sub-kategori turunan.</p>
                </div>
                <button type="button" onclick="closeModal('modal-add-category')" class="text-[#848CA3] hover:text-gray-800 text-2xl font-semibold leading-none">&times;</button>
            </div>

            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Misal: Wisata, Berita Desa"
                        class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>

                <div class="mb-6">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Kategori Induk (Parent)</label>
                    <select name="parent_id" class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                        <option value="">-- Tanpa Induk (Kategori Utama) --</option>
                        @foreach ($parentCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-[#848CA3] mt-1 block">Pilih kategori utama jika ini merupakan sub-kategori (maks 1 tingkat).</span>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#E4E8EF]">
                    <button type="button" onclick="closeModal('modal-add-category')" class="px-4 py-2.5 text-[13px] font-semibold text-gray-600 bg-[#F1F3F7] hover:bg-[#E4E8EF] rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 text-[13px] font-semibold text-white bg-brand-900 hover:bg-brand-800 rounded-xl transition shadow-sm">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Modal Edit Kategori --}}
    <div id="modal-edit-category" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[480px] w-full p-6 shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4E8EF] mb-4">
                <div>
                    <h3 class="text-[16px] font-bold text-gray-900">Edit Kategori</h3>
                    <p class="text-[12px] text-[#848CA3] mt-0.5">Perbarui nama atau induk kategori.</p>
                </div>
                <button type="button" onclick="closeModal('modal-edit-category')" class="text-[#848CA3] hover:text-gray-800 text-2xl font-semibold leading-none">&times;</button>
            </div>

            <form id="form-edit-category" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit-cat-name" name="name" required
                        class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>

                <div class="mb-6">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Kategori Induk (Parent)</label>
                    <select id="edit-cat-parent-id" name="parent_id" class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:border-brand-600">
                        <option value="">-- Tanpa Induk (Kategori Utama) --</option>
                        @foreach ($parentCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <span id="edit-cat-parent-help" class="text-[11px] text-[#848CA3] mt-1 block">Maksimal 1 tingkat sub-kategori.</span>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#E4E8EF]">
                    <button type="button" onclick="closeModal('modal-edit-category')" class="px-4 py-2.5 text-[13px] font-semibold text-gray-600 bg-[#F1F3F7] hover:bg-[#E4E8EF] rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 text-[13px] font-semibold text-white bg-brand-900 hover:bg-brand-800 rounded-xl transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Modal Tambah Tag --}}
    <div id="modal-add-tag" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[440px] w-full p-6 shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4E8EF] mb-4">
                <div>
                    <h3 class="text-[16px] font-bold text-gray-900">Tambah Tag</h3>
                    <p class="text-[12px] text-[#848CA3] mt-0.5">Label kata kunci topik artikel berita.</p>
                </div>
                <button type="button" onclick="closeModal('modal-add-tag')" class="text-[#848CA3] hover:text-gray-800 text-2xl font-semibold leading-none">&times;</button>
            </div>

            <form action="{{ route('admin.tags.store') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Nama Tag <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Misal: Kuliner Kebumen"
                        class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#E4E8EF]">
                    <button type="button" onclick="closeModal('modal-add-tag')" class="px-4 py-2.5 text-[13px] font-semibold text-gray-600 bg-[#F1F3F7] hover:bg-[#E4E8EF] rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 text-[13px] font-semibold text-white bg-brand-900 hover:bg-brand-800 rounded-xl transition shadow-sm">
                        Simpan Tag
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 4. Modal Edit Tag --}}
    <div id="modal-edit-tag" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[440px] w-full p-6 shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 border-b border-[#E4E8EF] mb-4">
                <div>
                    <h3 class="text-[16px] font-bold text-gray-900">Edit Tag</h3>
                    <p class="text-[12px] text-[#848CA3] mt-0.5">Perbarui nama tag kata kunci.</p>
                </div>
                <button type="button" onclick="closeModal('modal-edit-tag')" class="text-[#848CA3] hover:text-gray-800 text-2xl font-semibold leading-none">&times;</button>
            </div>

            <form id="form-edit-tag" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Nama Tag <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit-tag-name" name="name" required
                        class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#E4E8EF]">
                    <button type="button" onclick="closeModal('modal-edit-tag')" class="px-4 py-2.5 text-[13px] font-semibold text-gray-600 bg-[#F1F3F7] hover:bg-[#E4E8EF] rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 text-[13px] font-semibold text-white bg-brand-900 hover:bg-brand-800 rounded-xl transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 5. Modal Konfirmasi Hapus --}}
    <div id="modal-delete-confirm" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[420px] w-full p-6 shadow-2xl relative text-center">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 mx-auto flex items-center justify-center mb-3.5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 id="delete-modal-title" class="text-[16px] font-bold text-gray-900 mb-1">Konfirmasi Hapus</h3>
            <p id="delete-modal-message" class="text-[13px] text-[#848CA3] mb-6">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>

            <form id="form-delete-confirm" method="POST" class="flex items-center justify-center gap-3">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeModal('modal-delete-confirm')" class="flex-1 px-4 py-2.5 text-[13px] font-semibold text-gray-600 bg-[#F1F3F7] hover:bg-[#E4E8EF] rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2.5 text-[13px] font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>

    {{-- 6. Modal Pop-up Alert Feedback (Sukses & Error) --}}
    <div id="modal-alert-popup" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-2xl max-w-[400px] w-full p-6 shadow-2xl relative text-center">
            {{-- Icon Wadah --}}
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

    {{-- Script Interaktivitas Modal & Tab --}}
    <script>
        function switchTab(tabName) {
            const btnKategori = document.getElementById('tab-btn-kategori');
            const btnTag = document.getElementById('tab-btn-tag');
            const panelKategori = document.getElementById('tab-panel-kategori');
            const panelTag = document.getElementById('tab-panel-tag');

            if (tabName === 'kategori') {
                btnKategori.classList.add('border-brand-600', 'text-brand-600', 'font-bold');
                btnKategori.classList.remove('border-transparent', 'text-[#848CA3]', 'font-medium');

                btnTag.classList.remove('border-brand-600', 'text-brand-600', 'font-bold');
                btnTag.classList.add('border-transparent', 'text-[#848CA3]', 'font-medium');

                panelKategori.classList.remove('hidden');
                panelTag.classList.add('hidden');
            } else {
                btnTag.classList.add('border-brand-600', 'text-brand-600', 'font-bold');
                btnTag.classList.remove('border-transparent', 'text-[#848CA3]', 'font-medium');

                btnKategori.classList.remove('border-brand-600', 'text-brand-600', 'font-bold');
                btnKategori.classList.add('border-transparent', 'text-[#848CA3]', 'font-medium');

                panelTag.classList.remove('hidden');
                panelKategori.classList.add('hidden');
            }
        }

        // Modal Helpers
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

        // Kategori Modal
        function openAddCategoryModal() {
            openModal('modal-add-category');
        }

        function openEditCategoryModal(id, name, parentId, childrenCount) {
            const form = document.getElementById('form-edit-category');
            form.action = `/admin/categories/${id}`;

            document.getElementById('edit-cat-name').value = name;

            const parentSelect = document.getElementById('edit-cat-parent-id');
            const parentHelp = document.getElementById('edit-cat-parent-help');

            // Reset opsi dropdown
            Array.from(parentSelect.options).forEach(opt => {
                opt.disabled = false;
                if (opt.value == id) {
                    opt.disabled = true; // Kategori tidak boleh jadi induk dirinya sendiri
                }
            });

            if (childrenCount > 0) {
                // Kategori yang sudah memiliki anak tidak boleh diubah jadi sub-kategori
                parentSelect.value = '';
                parentSelect.disabled = true;
                parentHelp.textContent = 'Kategori ini memiliki sub-kategori sehingga tidak dapat dijadikan sub-kategori.';
                parentHelp.className = 'text-[11px] text-rose-600 mt-1 block font-medium';
            } else {
                parentSelect.disabled = false;
                parentSelect.value = parentId || '';
                parentHelp.textContent = 'Maksimal 1 tingkat sub-kategori (induk harus berupa kategori level utama).';
                parentHelp.className = 'text-[11px] text-[#848CA3] mt-1 block';
            }

            openModal('modal-edit-category');
        }

        function confirmDeleteCategory(id, name, articlesCount) {
            if (articlesCount > 0) {
                showModalAlert('Gagal Menghapus', `Kategori "<strong>${name}</strong>" tidak dapat dihapus karena masih digunakan oleh <strong>${articlesCount}</strong> artikel.`, 'error');
                return;
            }

            const form = document.getElementById('form-delete-confirm');
            form.action = `/admin/categories/${id}`;

            document.getElementById('delete-modal-title').textContent = 'Hapus Kategori';
            document.getElementById('delete-modal-message').innerHTML = `Apakah Anda yakin ingin menghapus kategori "<strong>${name}</strong>"?`;

            openModal('modal-delete-confirm');
        }

        // Tag Modal
        function openAddTagModal() {
            openModal('modal-add-tag');
        }

        function openEditTagModal(id, name) {
            const form = document.getElementById('form-edit-tag');
            form.action = `/admin/tags/${id}`;

            document.getElementById('edit-tag-name').value = name;
            openModal('modal-edit-tag');
        }

        function confirmDeleteTag(id, name) {
            const form = document.getElementById('form-delete-confirm');
            form.action = `/admin/tags/${id}`;

            document.getElementById('delete-modal-title').textContent = 'Hapus Tag';
            document.getElementById('delete-modal-message').innerHTML = `Apakah Anda yakin ingin menghapus tag "<strong>#${name}</strong>"?`;

            openModal('modal-delete-confirm');
        }

        // Pop-up Modal Alert
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

        // Cek flash session saat load halaman untuk memicu Modal Alert
        document.addEventListener('DOMContentLoaded', function () {
            // Deteksi tab dari URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'tag' || urlParams.has('q_tag') || urlParams.has('tag_page')) {
                switchTab('tag');
            }

            // Flash Message Pop-up Modal
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