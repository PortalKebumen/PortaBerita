@php
    $helpTexts = [
        'navbar' => 'Menu utama di bawah logo. Item kategori otomatis menampilkan sub-kategorinya sebagai dropdown (kecuali Anda menambahkan sub-item manual).',
        'footer' => 'Item level atas menjadi judul kolom di footer, dan sub-itemnya menjadi tautan di kolom tersebut. Gunakan "Judul Kolom" untuk judul tanpa tautan.',
        'footer_bottom' => 'Tautan kecil di baris hak cipta paling bawah (misal: Pedoman Media Siber, Kebijakan Privasi).',
    ];
    $shortLocations = ['navbar' => 'Navbar', 'footer' => 'Footer', 'footer_bottom' => 'Footer Bawah'];
    $canUpdate = auth()->user()->can('menus.update');
@endphp

@php
    // Halaman/kategori yang sudah ada di lokasi aktif tidak bisa dipilih lagi (cegah dobel)
    $menuNodes = $this->items->flatMap(fn ($i) => collect([$i])->merge($i->children));
    $usedPageIds = $menuNodes->pluck('page_id')->filter()->map(fn ($id) => (int) $id)->all();
    $usedCategoryIds = $menuNodes->pluck('category_id')->filter()->map(fn ($id) => (int) $id)->all();
@endphp

<div class="space-y-6">
    {{-- Tab lokasi menu --}}
    <div class="flex gap-2 border-b border-[#CDD3DF] max-sm:overflow-x-auto max-sm:overflow-y-hidden max-sm:[scrollbar-width:none] max-sm:[&::-webkit-scrollbar]:hidden">
        @foreach ($locations as $key => $label)
            <button type="button"
                wire:click="setLocation('{{ $key }}')"
                class="tab-btn whitespace-nowrap {{ $location === $key ? 'is-active' : '' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="alert-info">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5">
            <circle cx="12" cy="12" r="9"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-5M12 8h.01"/>
        </svg>
        <div>{{ $helpTexts[$location] ?? '' }}</div>
    </div>

    <div class="grid grid-cols-1 {{ $canUpdate ? 'lg:grid-cols-[320px_minmax(0,1fr)]' : '' }} gap-6 items-start">
        {{-- ==================== KOLOM KIRI: TAMBAH ITEM ==================== --}}
        @if ($canUpdate)
            <div class="space-y-3">
                <h3 class="font-semibold text-[#1B2033]">Tambah Item ke {{ $shortLocations[$location] ?? $location }}</h3>

                {{-- Halaman --}}
                <div class="card overflow-hidden" x-data="{ open: true }">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-[13.5px] font-semibold text-[#1B2033]">
                        Halaman
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" :class="open ? 'rotate-180' : ''" class="transition-transform"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div x-show="open" wire:ignore.self class="border-t border-[#E4E8EF] px-4 py-3 space-y-3">
                        @if ($this->pages->isEmpty())
                            <p class="text-[12.5px] text-[#848CA3]">
                                Belum ada halaman.
                                @can('pages.create')
                                    <a href="{{ route('admin.halaman.index', ['mode' => 'create']) }}" class="text-brand-600 hover:underline">Buat halaman</a>
                                @endcan
                            </p>
                        @else
                            <div class="space-y-2">
                                @foreach ($this->pages as $page)
                                    @php $used = in_array($page->id, $usedPageIds, true); @endphp
                                    <label class="flex items-center gap-2.5 {{ $used ? 'cursor-not-allowed' : 'cursor-pointer' }}" wire:key="pick-page-{{ $page->id }}-{{ $used ? 'u' : 'f' }}"
                                        @if ($used) title="Sudah ada di menu ini" @endif>
                                        <input type="checkbox" wire:model="selectedPages" value="{{ $page->id }}" @disabled($used) class="form-checkbox h-4 w-4 rounded accent-brand-600 disabled:opacity-40">
                                        <span class="text-[13px] {{ $used ? 'text-[#A3AAB9]' : '' }}">
                                            {{ $page->title }}
                                            @unless ($page->isPublished())
                                                <span class="text-[#848CA3]">(draft)</span>
                                            @endunless
                                        </span>
                                        @if ($used)
                                            <span class="ml-auto shrink-0 rounded bg-[#EEF1F5] px-1.5 py-0.5 text-[10.5px] font-semibold text-[#848CA3]">Sudah di menu</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                            <div class="flex justify-end">
                                <button type="button" wire:click="addPages" class="btn-secondary btn-sm" wire:loading.attr="disabled" wire:target="addPages">Tambahkan ke menu</button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Kategori --}}
                <div class="card overflow-hidden" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-[13.5px] font-semibold text-[#1B2033]">
                        Kategori
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" :class="open ? 'rotate-180' : ''" class="transition-transform"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div x-show="open" wire:ignore.self class="border-t border-[#E4E8EF] px-4 py-3 space-y-3" style="display: none;">
                        @if ($this->categoryGroups->isEmpty())
                            <p class="text-[12.5px] text-[#848CA3]">Belum ada kategori.</p>
                        @else
                            <div class="space-y-2">
                                @foreach ($this->categoryGroups as $cat)
                                    @php $used = in_array($cat->id, $usedCategoryIds, true); @endphp
                                    <div wire:key="pick-cat-{{ $cat->id }}">
                                        <label class="flex items-center gap-2.5 {{ $used ? 'cursor-not-allowed' : 'cursor-pointer' }}" wire:key="pick-cat-in-{{ $cat->id }}-{{ $used ? 'u' : 'f' }}"
                                            @if ($used) title="Sudah ada di menu ini" @endif>
                                            <input type="checkbox" wire:model="selectedCategories" value="{{ $cat->id }}" @disabled($used) class="form-checkbox h-4 w-4 rounded accent-brand-600 disabled:opacity-40">
                                            <span class="text-[13px] font-medium {{ $used ? 'text-[#A3AAB9]' : '' }}">{{ $cat->name }}</span>
                                            @if ($used)
                                                <span class="ml-auto shrink-0 rounded bg-[#EEF1F5] px-1.5 py-0.5 text-[10.5px] font-semibold text-[#848CA3]">Sudah di menu</span>
                                            @endif
                                        </label>
                                        @foreach ($cat->children as $child)
                                            @php $used = in_array($child->id, $usedCategoryIds, true); @endphp
                                            <label class="flex items-center gap-2.5 ml-6 mt-1.5 {{ $used ? 'cursor-not-allowed' : 'cursor-pointer' }}" wire:key="pick-cat-{{ $child->id }}-{{ $used ? 'u' : 'f' }}"
                                                @if ($used) title="Sudah ada di menu ini" @endif>
                                                <input type="checkbox" wire:model="selectedCategories" value="{{ $child->id }}" @disabled($used) class="form-checkbox h-4 w-4 rounded accent-brand-600 disabled:opacity-40">
                                                <span class="text-[13px] {{ $used ? 'text-[#A3AAB9]' : 'text-[#3B4152]' }}">{{ $child->name }}</span>
                                                @if ($used)
                                                    <span class="ml-auto shrink-0 rounded bg-[#EEF1F5] px-1.5 py-0.5 text-[10.5px] font-semibold text-[#848CA3]">Sudah di menu</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-end">
                                <button type="button" wire:click="addCategories" class="btn-secondary btn-sm" wire:loading.attr="disabled" wire:target="addCategories">Tambahkan ke menu</button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tautan Kustom --}}
                <div class="card overflow-hidden" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-[13.5px] font-semibold text-[#1B2033]">
                        Tautan Kustom
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" :class="open ? 'rotate-180' : ''" class="transition-transform"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <form x-show="open" wire:ignore.self wire:submit.prevent="addCustomLink" class="border-t border-[#E4E8EF] px-4 py-3 space-y-3" style="display: none;">
                        <div>
                            <label class="form-label">URL</label>
                            <input type="text" wire:model="customUrl" placeholder="https://... atau /kontak" class="form-input @error('customUrl') is-error @enderror">
                            @error('customUrl') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Teks Tautan</label>
                            <input type="text" wire:model="customLabel" placeholder="Misal: Kontak" class="form-input @error('customLabel') is-error @enderror">
                            @error('customLabel') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="customNewTab" class="form-checkbox h-4 w-4 rounded accent-brand-600">
                            <span class="text-[13px]">Buka di tab baru</span>
                        </label>
                        <div class="flex justify-end">
                            <button type="submit" class="btn-secondary btn-sm" wire:loading.attr="disabled" wire:target="addCustomLink">Tambahkan ke menu</button>
                        </div>
                    </form>
                </div>

                {{-- Judul Kolom --}}
                <div class="card overflow-hidden" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-[13.5px] font-semibold text-[#1B2033]">
                        Judul Kolom
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" :class="open ? 'rotate-180' : ''" class="transition-transform"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <form x-show="open" wire:ignore.self wire:submit.prevent="addHeading" class="border-t border-[#E4E8EF] px-4 py-3 space-y-3" style="display: none;">
                        <p class="text-[12px] text-[#848CA3]">Judul tanpa tautan, cocok sebagai judul kolom di Footer.</p>
                        <div>
                            <label class="form-label">Label</label>
                            <input type="text" wire:model="headingLabel" placeholder="Misal: Informasi" class="form-input @error('headingLabel') is-error @enderror">
                            @error('headingLabel') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="btn-secondary btn-sm" wire:loading.attr="disabled" wire:target="addHeading">Tambahkan ke menu</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ==================== KOLOM KANAN: STRUKTUR MENU ==================== --}}
        <div class="card p-5 space-y-4 min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-[#1B2033]">Struktur Menu — {{ $locations[$location] ?? $location }}</h3>
                    @if ($canUpdate)
                        <p class="text-[12px] text-[#848CA3] mt-0.5">Seret ikon <span class="font-semibold">⋮⋮</span> untuk mengurutkan, atau jatuhkan ke bawah item lain untuk menjadikannya sub-item (maks. 1 tingkat). Tombol panah juga bisa dipakai. Perubahan langsung tersimpan.</p>
                    @endif
                </div>
                <span class="badge-neutral">{{ $this->items->count() + $this->items->sum(fn ($i) => $i->children->count()) }} item</span>
            </div>

            @if ($this->items->isEmpty())
                <div class="rounded-lg border border-dashed border-[#CDD3DF] px-5 py-10 text-center text-[13px] text-[#848CA3]">
                    Menu ini masih kosong.
                    @if ($canUpdate)
                        Tambahkan item dari panel di sebelah kiri.
                    @endif
                </div>
            @else
                {{-- Level atas: bisa diurutkan & menerima sub-item yang dikeluarkan dari induknya --}}
                <div class="space-y-2"
                    @if ($canUpdate) wire:sort="reorder" wire:sort:group-id="root" wire:sort:group="menu-{{ $location }}" @endif>
                    @foreach ($this->items as $item)
                        @php $parentHidden = ! $item->isVisible(); @endphp
                        <div wire:key="node-{{ $item->id }}" wire:sort:item="{{ $item->id }}">
                            @include('livewire.admin.partials.menu-item-row', [
                                'item' => $item,
                                'isChild' => false,
                                'isFirst' => $loop->first,
                                'isLast' => $loop->last,
                                'canIndent' => ! $loop->first && $item->children->isEmpty(),
                                'parentHidden' => false,
                            ])

                            {{-- Daftar sub-item: area jatuh untuk menjadikan item sebagai sub-item --}}
                            <div class="ml-6 sm:ml-10 space-y-2 rounded-lg transition-all {{ $item->children->isNotEmpty() ? 'mt-2' : 'in-[.sorting]:mt-2 in-[.sorting]:min-h-11 in-[.sorting]:border in-[.sorting]:border-dashed in-[.sorting]:border-[#CDD3DF] in-[.sorting]:bg-[#F7F8FA]' }}"
                                @if ($canUpdate) wire:sort="reorder" wire:sort:group-id="{{ $item->id }}" wire:sort:group="menu-{{ $location }}" @endif>
                                @foreach ($item->children as $child)
                                    <div wire:key="node-{{ $child->id }}" wire:sort:item="{{ $child->id }}">
                                        @include('livewire.admin.partials.menu-item-row', [
                                            'item' => $child,
                                            'isChild' => true,
                                            'isFirst' => $loop->first,
                                            'isLast' => $loop->last,
                                            'canIndent' => false,
                                            'parentHidden' => $parentHidden,
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ==================== MODAL EDIT ITEM ==================== --}}
    <div class="modal-overlay {{ $showEditModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md shadow-xl animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#E4E8EF]">
                <h3 class="font-semibold text-[#1B2033]">Edit Item Menu</h3>
                <button type="button" wire:click="closeModals" class="text-[#848CA3] hover:text-gray-700 text-lg leading-none">&times;</button>
            </div>

            <form wire:submit="saveItem" class="px-6 py-5 space-y-4">
                <div class="flex items-center gap-2 text-[12.5px] text-[#6C7387]">
                    <span>Jenis:</span>
                    <span class="font-semibold text-[#1B2033]">{{ $types[$editType] ?? $editType }}</span>
                    @if ($editTargetName)
                        <span>·</span>
                        <span class="truncate">{{ $editTargetName }}</span>
                    @endif
                </div>

                <div>
                    <label class="form-label">
                        {{ $editType === 'heading' ? 'Judul Kolom' : 'Label' }}
                        @if (in_array($editType, ['url', 'heading'], true)) <span class="text-danger">*</span> @endif
                    </label>
                    <input type="text" wire:model="editLabel"
                        placeholder="{{ in_array($editType, ['page', 'category'], true) ? $editTargetName : '' }}"
                        class="form-input @error('editLabel') is-error @enderror">
                    @error('editLabel')
                        <p class="form-error-text">{{ $message }}</p>
                    @else
                        @if (in_array($editType, ['page', 'category'], true))
                            <span class="text-xs text-[#848CA3] mt-1 block">Kosongkan untuk memakai {{ $editType === 'page' ? 'judul halaman' : 'nama kategori' }}.</span>
                        @endif
                    @enderror
                </div>

                @if ($editType === 'url')
                    <div>
                        <label class="form-label">URL <span class="text-danger">*</span></label>
                        <input type="text" wire:model="editUrl" placeholder="https://... atau /kontak" class="form-input font-mono @error('editUrl') is-error @enderror">
                        @error('editUrl') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($editType !== 'heading')
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model="editNewTab" class="form-checkbox h-4 w-4 rounded accent-brand-600">
                        <span class="text-[13px]">Buka di tab baru</span>
                    </label>
                @endif

                <div class="flex justify-end gap-2.5 pt-2 border-t border-[#E4E8EF]">
                    <button type="button" wire:click="closeModals" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
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
                <h3 class="font-semibold text-[#1B2033]">Hapus Item Menu</h3>
                <p class="text-sm text-[#6C7387] mt-1.5 leading-relaxed">
                    Hapus <strong class="text-[#1B2033]">"{{ $deletingLabel }}"</strong> dari menu?
                    @if ($deletingChildrenCount > 0)
                        <span class="block mt-1 text-danger font-medium">{{ $deletingChildrenCount }} sub-item di bawahnya juga akan ikut terhapus.</span>
                    @endif
                    <span class="block mt-1">Halaman/kategori aslinya tidak ikut terhapus.</span>
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
