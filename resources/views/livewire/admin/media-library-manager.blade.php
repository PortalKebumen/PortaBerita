<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap">
        <div class="relative flex-1 max-w-[320px]">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2"
                class="absolute left-3.5 top-1/2 -translate-y-1/2">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" wire:model.live.debounce.400ms="search" class="form-input pl-9"
                placeholder="Cari nama berkas...">
        </div>
        <select wire:model.live="type" class="form-select w-full sm:w-44">
            <option value="">Semua Tipe</option>
            <option value="image">Gambar</option>
            <option value="document">Dokumen</option>
        </select>
        <div class="relative w-full sm:w-56" wire:ignore x-data="{
                init() {
                    flatpickr(this.$refs.rangeInput, {
                        mode: 'range',
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd M Y',
                        defaultDate: [@js($dateFrom ?: null), @js($dateTo ?: null)],
                        onChange: (selectedDates, dateStr, instance) => {
                            if (selectedDates.length === 2) {
                                $wire.set('dateFrom', instance.formatDate(selectedDates[0], 'Y-m-d'));
                                $wire.set('dateTo', instance.formatDate(selectedDates[1], 'Y-m-d'));
                            } else if (selectedDates.length === 0) {
                                $wire.set('dateFrom', '');
                                $wire.set('dateTo', '');
                            }
                        },
                    });
                }
            }">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <input type="text" x-ref="rangeInput" class="form-input pl-9" placeholder="Pilih rentang tanggal" readonly>
        </div>

        @can('media.upload')
            <button type="button" wire:click="openUploadModal" class="btn-primary sm:ml-auto shrink-0">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M12 16V4M12 4l-4 4M12 4l4 4"></path>
                    <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path>
                </svg>
                Unggah Media
            </button>
        @endcan
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4" wire:loading.class="opacity-50"
        wire:target="search,type,dateFrom,dateTo">
        @forelse ($this->mediaItems as $item)
            @php
                $media = $item->getFirstMedia('library');
                if (!$media) {
                    continue;
                }
                $isImage = str_starts_with($media->mime_type, 'image/');
                $thumbUrl =
                    $isImage && $media->hasGeneratedConversion('small') ? $media->getUrl('small') : $media->getUrl();
                $sizeKb = $media->size / 1024;
                $sizeLabel = $sizeKb >= 1024 ? round($sizeKb / 1024, 1) . ' MB' : round($sizeKb) . ' KB';
                $canUpdate = $this->canManage($item, 'media.update-own', 'media.update-any');
                $canDelete = $this->canManage($item, 'media.delete-own', 'media.delete-any');
            @endphp
            <div class="card overflow-hidden group relative" wire:key="media-{{ $item->id }}">
                @if ($isImage)
                    <div class="h-28 bg-[#EDEFF4]">
                        <img src="{{ $thumbUrl }}" alt="{{ $media->getCustomProperty('alt_text', '') }}"
                            class="w-full h-28 object-cover">
                    </div>
                @else
                    <div
                        class="h-28 bg-gradient-to-br from-brand-700 to-brand-500 flex items-center justify-center text-white/40">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.6">
                            <rect x="3" y="4" width="18" height="14" rx="2"></rect>
                            <circle cx="8.5" cy="10" r="1.6"></circle>
                            <path d="M3 16l5-4 4 3 3-3 6 5"></path>
                        </svg>
                    </div>
                @endif

                @if ($canUpdate || $canDelete)
                    <div class="absolute top-2 right-2 hidden group-hover:flex gap-1.5">
                        @if ($canUpdate)
                            <button type="button" wire:click="openEditModal({{ $media->id }})"
                                class="w-7 h-7 rounded-md bg-white/90 flex items-center justify-center shadow"
                                aria-label="Edit">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#171B28"
                                    stroke-width="2">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"></path>
                                </svg>
                            </button>
                        @endif
                        @if ($canDelete)
                            <button type="button" wire:click="openDeleteModal({{ $media->id }})"
                                class="w-7 h-7 rounded-md bg-white/90 text-danger flex items-center justify-center shadow"
                                aria-label="Hapus">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18"></path>
                                    <path
                                        d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6">
                                    </path>
                                </svg>
                            </button>
                        @endif
                    </div>
                @endif

                <div class="p-2.5">
                    <div class="text-[11.5px] font-semibold truncate" title="{{ $media->file_name }}">
                        {{ $media->file_name }}</div>
                    <div class="text-[10.5px] text-[#848CA3] mt-0.5">{{ $sizeLabel }} &middot;
                        {{ $media->created_at->translatedFormat('d M Y') }}</div>
                </div>
            </div>
        @empty
            <p class="col-span-full text-center text-[#848CA3] py-10">Belum ada media yang diunggah.</p>
        @endforelse
    </div>

    <div>
        {{ $this->mediaItems->links() }}
    </div>

    {{-- Modal: Upload (banyak berkas sekaligus) --}}
    <script>
        // Unggah banyak berkas: dikirim satu per satu (antre) agar tiap request tetap di bawah batas
        // ukuran server, dengan progres per berkas. Berkas yang selesai tampil dari data server.
        window.mediaMultiUpload = window.mediaMultiUpload || function (maxBytes, maxLabel, maxFiles) {
            return {
                pending: [], errors: [], counter: 0, dragging: false, queue: Promise.resolve(),
                pick(fileList) {
                    this.errors = [];
                    const files = Array.from(fileList || []);
                    let room = maxFiles - Object.keys(this.$wire.newFiles || {}).length - this.pending.length;
                    files.forEach((file) => {
                        if (room <= 0) {
                            this.errors.push('"' + file.name + '": melebihi batas ' + maxFiles + ' berkas sekali unggah.');
                            return;
                        }
                        if (file.size > maxBytes) {
                            this.errors.push('"' + file.name + '": ' + (file.size / 1024 / 1024).toFixed(2) + ' MB, melebihi batas maksimal ' + maxLabel + '. Kecilkan atau kompres terlebih dahulu.');
                            return;
                        }
                        room--;
                        const key = Date.now() % 100000000 * 100 + (this.counter++ % 100);
                        this.pending.push({ key, name: file.name, progress: 0, preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null });
                        this.queue = this.queue.then(() => new Promise((resolve) => {
                            this.$wire.upload('newFiles.' + key, file,
                                () => { this.drop(key); resolve(); },
                                () => { this.drop(key); resolve(); },
                                (e) => { const item = this.pending.find((p) => p.key === key); if (item) item.progress = e.detail.progress; });
                        }));
                    });
                    if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                },
                drop(key) {
                    const i = this.pending.findIndex((p) => p.key === key);
                    if (i < 0) return;
                    if (this.pending[i].preview) URL.revokeObjectURL(this.pending[i].preview);
                    this.pending.splice(i, 1);
                },
                clear() {
                    this.pending.forEach((p) => p.preview && URL.revokeObjectURL(p.preview));
                    this.pending = [];
                    this.errors = [];
                },
            };
        };
    </script>
    <div
        class="modal-overlay {{ $showUploadModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center p-4 bg-black/40"
        x-data="mediaMultiUpload({{ \App\Support\UploadLimit::maxBytes() }}, @js(\App\Support\UploadLimit::label()), {{ \App\Livewire\Admin\MediaLibraryManager::MAX_FILES }})"
        x-on:flash-message.window="clear()">
        <div class="bg-white rounded-card max-w-[640px] w-full max-h-[calc(100vh-2rem)] flex flex-col shadow-xl">
            <form wire:submit="saveUpload" class="flex flex-col min-h-0">
                <div class="flex justify-between items-center px-6 pt-6 pb-4">
                    <h3 class="text-[16px] font-bold">Unggah Media Baru</h3>
                    <button type="button" wire:click="closeUploadModal" @click="clear()" class="btn-icon bg-[#F1F3F7]" aria-label="Tutup">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="px-6 overflow-y-auto min-h-0">
                    <label
                        class="flex flex-col items-center justify-center gap-2 border border-dashed rounded-lg py-6 px-4 cursor-pointer transition-colors mb-4"
                        :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-[#CDD3DF] hover:bg-[#F8F9FB]'"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="dragging = false; pick($event.dataTransfer.files)">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="1.8">
                            <path d="M12 16V4M12 4l-4 4M12 4l4 4"></path>
                            <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path>
                        </svg>
                        <span class="text-[12.5px] text-[#6C7387] text-center"><span class="font-semibold text-brand-600">Klik untuk pilih berkas</span> atau seret ke sini &mdash; bisa banyak sekaligus</span>
                        <span class="text-[11px] text-[#848CA3]">JPG, PNG, WEBP, PDF &middot; maks. {{ \App\Support\UploadLimit::label() }} per berkas &middot; {{ \App\Livewire\Admin\MediaLibraryManager::MAX_FILES }} berkas sekali unggah</span>
                        <input type="file" multiple x-ref="fileInput" class="hidden" x-on:change="pick($event.target.files)">
                    </label>

                    <template x-for="msg in errors" :key="msg">
                        <p class="text-[12px] text-danger mb-1.5" x-text="msg"></p>
                    </template>
                    @foreach (collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'newFiles'))->flatten() as $message)
                        <p class="text-[12px] text-danger mb-1.5">{{ $message }}</p>
                    @endforeach

                    {{-- Sedang diunggah --}}
                    <template x-for="item in pending" :key="item.key">
                        <div class="flex items-center gap-3 border border-[#E4E8EF] rounded-lg p-2.5 mb-2.5">
                            <div class="w-12 h-12 rounded-md bg-[#F1F3F7] overflow-hidden shrink-0 flex items-center justify-center">
                                <template x-if="item.preview"><img :src="item.preview" alt="" class="w-full h-full object-cover"></template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-[12.5px] font-medium truncate" x-text="item.name"></div>
                                <div class="h-1.5 rounded-full bg-[#E4E8EF] mt-1.5 overflow-hidden">
                                    <div class="h-full bg-brand-600 transition-all" :style="'width:' + item.progress + '%'"></div>
                                </div>
                                <div class="text-[11px] text-[#848CA3] mt-1" x-text="item.progress < 100 ? 'Mengunggah… ' + item.progress + '%' : 'Memproses…'"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Siap disimpan --}}
                    @foreach ($newFiles as $key => $file)
                        <div wire:key="newfile-{{ $key }}" class="flex gap-3 border border-[#E4E8EF] rounded-lg p-2.5 mb-2.5">
                            <div class="w-16 h-16 rounded-md bg-[#F1F3F7] overflow-hidden shrink-0 flex items-center justify-center text-[10px] font-bold text-[#848CA3]">
                                @if (method_exists($file, 'isPreviewable') && $file->isPreviewable())
                                    <img src="{{ $file->temporaryUrl() }}" alt="" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper($file->getClientOriginalExtension()) }}
                                @endif
                            </div>
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[12.5px] font-medium truncate flex-1" title="{{ $file->getClientOriginalName() }}">{{ $file->getClientOriginalName() }}</span>
                                    <span class="badge-success shrink-0">Siap</span>
                                    <button type="button" wire:click="removeNewFile({{ $key }})" class="text-[#848CA3] hover:text-danger shrink-0" title="Batalkan berkas ini" aria-label="Batalkan {{ $file->getClientOriginalName() }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <input type="text" wire:model="newMeta.{{ $key }}.alt" class="form-input !py-1.5 text-[12.5px]" placeholder="Teks alternatif (alt): deskripsi gambar untuk SEO & aksesibilitas">
                                <input type="text" wire:model="newMeta.{{ $key }}.caption" maxlength="255" class="form-input !py-1.5 text-[12.5px]" placeholder="Keterangan (caption), contoh: Warga memadati Pantai Menganti. (Foto: Dok. PortalKebumen)">
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-2.5 px-6 py-4 border-t border-[#E4E8EF] mt-2">
                    <span class="text-[12px] text-[#6C7387]">
                        {{ count($newFiles) }} berkas siap<span x-show="pending.length" x-cloak x-text="' · ' + pending.length + ' sedang diunggah'"></span>
                    </span>
                    <div class="flex gap-2.5">
                        <button type="button" wire:click="closeUploadModal" @click="clear()" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="pending.length > 0" wire:loading.attr="disabled" wire:target="saveUpload"
                            class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="saveUpload" x-text="pending.length ? 'Menunggu berkas…' : 'Unggah {{ count($newFiles) > 1 ? count($newFiles).' Berkas' : '' }}'"></span>
                            <span wire:loading wire:target="saveUpload">Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Edit metadata --}}
    <div
        class="modal-overlay {{ $showEditModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[460px] w-full p-6 shadow-xl">
            <form wire:submit="updateMedia">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-[16px] font-bold">Edit Metadata Berkas</h3>
                    <button type="button" wire:click="closeEditModal" class="btn-icon bg-[#F1F3F7]"
                        aria-label="Tutup">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center gap-3 mb-5 bg-[#F8F9FB] border border-[#E4E8EF] rounded-lg p-3">
                    @if ($editIsImage && $editThumbUrl)
                        <img src="{{ $editThumbUrl }}" alt="{{ $editAltText }}" class="w-12 h-12 rounded-lg object-cover shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-brand-700 to-brand-500 shrink-0 flex items-center justify-center text-white">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                                <path d="M14 2v6h6"></path>
                            </svg>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-[13px] font-semibold truncate">{{ $editFileName }}</div>
                        <div class="text-[11.5px] text-[#848CA3]">{{ $editSizeLabel }}</div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Teks Alternatif (Alt Text)</label>
                    <input type="text" wire:model="editAltText" class="form-input">
                </div>
                <div class="mb-5">
                    <label class="form-label">Keterangan (Caption)</label>
                    <textarea wire:model="editCaption" rows="3" maxlength="255" class="form-textarea resize-y"
                        placeholder="Keterangan yang tampil di bawah gambar"></textarea>
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" wire:click="closeEditModal" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Hapus --}}
    <div
        class="modal-overlay {{ $showDeleteModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[420px] w-full p-6 shadow-xl">
            <h3 class="text-[16px] font-bold mb-1.5">Hapus berkas ini?</h3>
            <p class="text-[13px] text-[#6C7387] mb-3">Kamu akan menghapus <span
                    class="font-semibold">{{ $deletingFileName }}</span>.</p>
            <div class="alert-danger mb-5 text-[12.5px]">
                Berkas yang masih dipakai di artikel/iklan tertentu sebaiknya diganti dulu sebelum dihapus. Tindakan ini
                tidak bisa dibatalkan.
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" wire:click="closeDeleteModal" class="btn-secondary">Batal</button>
                <button type="button" wire:click="deleteMedia" class="btn-danger">Ya, Hapus</button>
            </div>
        </div>
    </div>

</div>
