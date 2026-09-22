<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap">
        <div class="relative flex-1 max-w-[320px]">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" wire:model.live.debounce.400ms="search" class="form-input pl-9" placeholder="Cari nama berkas...">
        </div>
        <select wire:model.live="type" class="form-select w-full sm:w-44">
            <option value="">Semua Tipe</option>
            <option value="image">Gambar</option>
            <option value="document">Dokumen</option>
        </select>
        <input type="date" wire:model.live="dateFrom" class="form-input w-full sm:w-44" title="Dari tanggal">
        <input type="date" wire:model.live="dateTo" class="form-input w-full sm:w-44" title="Sampai tanggal">

        @can('media.upload')
            <button type="button" wire:click="openUploadModal" class="btn-primary sm:ml-auto shrink-0">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4M12 4l-4 4M12 4l4 4"></path><path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path></svg>
                Unggah Media
            </button>
        @endcan
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4" wire:loading.class="opacity-50" wire:target="search,type,dateFrom,dateTo">
        @forelse ($this->mediaItems as $item)
            @php
                $media = $item->getFirstMedia('library');
                if (! $media) continue;
                $isImage = str_starts_with($media->mime_type, 'image/');
                $sizeKb = $media->size / 1024;
                $sizeLabel = $sizeKb >= 1024 ? round($sizeKb / 1024, 1) . ' MB' : round($sizeKb) . ' KB';
                $canUpdate = $this->canManage($item, 'media.update-own', 'media.update-any');
                $canDelete = $this->canManage($item, 'media.delete-own', 'media.delete-any');
            @endphp
            <div class="card overflow-hidden group relative" wire:key="media-{{ $item->id }}">
                @if ($isImage)
                    <div class="h-28 bg-[#EDEFF4]">
                        <img src="{{ $media->getUrl('small') }}" alt="{{ $media->getCustomProperty('alt_text', '') }}" class="w-full h-28 object-cover">
                    </div>
                @else
                    <div class="h-28 bg-gradient-to-br from-brand-700 to-brand-500 flex items-center justify-center text-white/40">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="14" rx="2"></rect><circle cx="8.5" cy="10" r="1.6"></circle><path d="M3 16l5-4 4 3 3-3 6 5"></path></svg>
                    </div>
                @endif

                @if ($canUpdate || $canDelete)
                    <div class="absolute top-2 right-2 hidden group-hover:flex gap-1.5">
                        @if ($canUpdate)
                            <button type="button" wire:click="openEditModal({{ $media->id }})" class="w-7 h-7 rounded-md bg-white/90 flex items-center justify-center shadow" aria-label="Edit">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#171B28" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"></path></svg>
                            </button>
                        @endif
                        @if ($canDelete)
                            <button type="button" wire:click="openDeleteModal({{ $media->id }})" class="w-7 h-7 rounded-md bg-white/90 text-danger flex items-center justify-center shadow" aria-label="Hapus">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6"></path></svg>
                            </button>
                        @endif
                    </div>
                @endif

                <div class="p-2.5">
                    <div class="text-[11.5px] font-semibold truncate" title="{{ $media->file_name }}">{{ $media->file_name }}</div>
                    <div class="text-[10.5px] text-[#848CA3] mt-0.5">{{ $sizeLabel }} &middot; {{ $media->created_at->translatedFormat('d M Y') }}</div>
                </div>
            </div>
        @empty
            <p class="col-span-full text-center text-[#848CA3] py-10">Belum ada media yang diunggah.</p>
        @endforelse
    </div>

    <div>
        {{ $this->mediaItems->links() }}
    </div>

    {{-- Modal: Upload --}}
    <div class="modal-overlay {{ $showUploadModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[500px] w-full p-6 shadow-xl">
            <form wire:submit="upload">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-[16px] font-bold">Unggah Media Baru</h3>
                    <button type="button" wire:click="closeUploadModal" class="btn-icon bg-[#F1F3F7]" aria-label="Tutup">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                </div>

                <label class="flex flex-col items-center justify-center gap-2 border border-dashed border-[#CDD3DF] rounded-lg py-10 cursor-pointer hover:bg-[#F8F9FB] transition-colors mb-4" wire:loading.class="opacity-50 pointer-events-none" wire:target="newFile">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="1.8"><path d="M12 16V4M12 4l-4 4M12 4l4 4"></path><path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path></svg>
                    <span class="text-[12.5px] text-[#6C7387]"><span class="font-semibold text-brand-600">Klik untuk unggah</span> atau seret berkas ke sini</span>
                    <span class="text-[11px] text-[#848CA3]">JPG, PNG, WEBP, PDF hingga 10MB</span>
                    <input type="file" wire:model="newFile" wire:key="media-upload-new-file" class="hidden">
                </label>

                <div wire:loading wire:target="newFile" class="text-[12px] text-brand-600 mb-3 flex items-center gap-2">
                    <svg class="animate-spin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="9" stroke-opacity="0.25"></circle><path d="M21 12a9 9 0 00-9-9"></path></svg>
                    Mengunggah berkas ke server, mohon tunggu sebentar...
                </div>

                @if ($newFile)
                    <p class="text-[12px] text-[#6C7387] mb-3" wire:loading.remove wire:target="newFile">Berkas siap: {{ $newFile->getClientOriginalName() }}</p>
                @endif
                @error('newFile') <p class="text-[12px] text-danger mb-3">{{ $message }}</p> @enderror

                <div class="mb-4">
                    <label class="form-label">Teks Alternatif (Alt Text)</label>
                    <input type="text" wire:model="newAltText" class="form-input" placeholder="Deskripsi gambar untuk SEO & aksesibilitas">
                </div>
                <div class="mb-5">
                    <label class="form-label">Keterangan (Caption)</label>
                    <input type="text" wire:model="newCaption" class="form-input" placeholder="Keterangan yang tampil di bawah gambar">
                </div>

                <div class="flex justify-end gap-2.5">
                    <button type="button" wire:click="closeUploadModal" class="btn-secondary">Batal</button>
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="newFile,upload"
                            class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="newFile,upload">Unggah</span>
                        <span wire:loading wire:target="newFile">Menunggu berkas...</span>
                        <span wire:loading wire:target="upload">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Edit metadata --}}
    <div class="modal-overlay {{ $showEditModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[460px] w-full p-6 shadow-xl">
            <form wire:submit="updateMedia">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-[16px] font-bold">Edit Metadata Berkas</h3>
                    <button type="button" wire:click="closeEditModal" class="btn-icon bg-[#F1F3F7]" aria-label="Tutup">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="flex items-center gap-3 mb-5 bg-[#F8F9FB] border border-[#E4E8EF] rounded-lg p-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-brand-700 to-brand-500 shrink-0"></div>
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
                    <input type="text" wire:model="editCaption" class="form-input">
                </div>
                <div class="flex justify-end gap-2.5">
                    <button type="button" wire:click="closeEditModal" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Hapus --}}
    <div class="modal-overlay {{ $showDeleteModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[420px] w-full p-6 shadow-xl">
            <h3 class="text-[16px] font-bold mb-1.5">Hapus berkas ini?</h3>
            <p class="text-[13px] text-[#6C7387] mb-3">Kamu akan menghapus <span class="font-semibold">{{ $deletingFileName }}</span>.</p>
            <div class="alert-danger mb-5 text-[12.5px]">
                Berkas yang masih dipakai di artikel/iklan tertentu sebaiknya diganti dulu sebelum dihapus. Tindakan ini tidak bisa dibatalkan.
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" wire:click="closeDeleteModal" class="btn-secondary">Batal</button>
                <button type="button" wire:click="deleteMedia" class="btn-danger">Ya, Hapus</button>
            </div>
        </div>
    </div>

</div>