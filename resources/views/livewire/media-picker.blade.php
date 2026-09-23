<div>
    @if ($isOpen)
        <div class="modal-overlay flex fixed inset-0 z-[1400] items-center justify-center p-4 bg-black/40" wire:keydown.escape="close">
            <div class="bg-white rounded-card max-w-[720px] w-full max-h-[85vh] overflow-y-auto shadow-xl">
                <div class="flex justify-between items-center px-6 py-4 border-b border-[#CDD3DF] sticky top-0 bg-white z-10">
                    <h3 class="text-[16px] font-bold">Pilih Gambar</h3>
                    <button type="button" class="btn-icon bg-[#F1F3F7]" wire:click="close" aria-label="Tutup">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex border-b border-[#CDD3DF] px-6">
                    @if ($canUpload)
                        <button type="button" class="tab-btn {{ $activeTab === 'upload' ? 'is-active' : '' }}" wire:click="$set('activeTab', 'upload')">Unggah Berkas</button>
                    @endif
                    <button type="button" class="tab-btn {{ $activeTab === 'library' ? 'is-active' : '' }}" wire:click="$set('activeTab', 'library')">Pustaka Media</button>
                </div>

                <div class="p-6">
                    {{-- Tab: Unggah Berkas --}}
                    @if ($canUpload)
                        <div @if ($activeTab !== 'upload') style="display:none" @endif x-data="{ preview: null, sizeError: null }">
                            <label
                                class="flex flex-col items-center justify-center gap-2 border border-dashed border-[#CDD3DF] rounded-lg py-10 px-4 cursor-pointer hover:bg-[#F8F9FB] transition-colors mb-4 overflow-hidden">

                                <template x-if="preview">
                                    <img :src="preview" class="h-20 w-auto object-contain rounded-md">
                                </template>

                                <template x-if="!preview">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="1.8"><path d="M12 16V4M12 4l-4 4M12 4l4 4"></path><path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path></svg>
                                </template>

                                <span class="text-[13px] text-[#6C7387]"><span class="font-semibold text-brand-600">Klik untuk unggah</span> atau seret berkas dari perangkat ke sini</span>
                                <span class="text-[11px] text-[#848CA3]">JPG, PNG, WEBP hingga 5MB</span>
                                <input type="file" accept="image/*" class="hidden"
                                    x-on:change="
                                        sizeError = null;
                                        const file = $event.target.files[0];
                                        if (!file) return;

                                        const maxBytes = 5 * 1024 * 1024;
                                        if (file.size > maxBytes) {
                                            sizeError = 'Ukuran berkas ' + (file.size / 1024 / 1024).toFixed(2) + ' MB, melebihi batas maksimal 5 MB.';
                                            $event.target.value = '';
                                            preview = null;
                                            return;
                                        }

                                        if (preview) { URL.revokeObjectURL(preview); }
                                        preview = URL.createObjectURL(file);
                                        $wire.upload('newFile', file);
                                    ">
                            </label>

                            <p x-show="sizeError" x-text="sizeError" x-cloak class="text-[12px] text-danger mb-3"></p>

                            @if ($newFile)
                                <p class="text-[12px] text-[#6C7387] mb-3">Berkas siap: {{ $newFile->getClientOriginalName() }}</p>
                            @endif

                            @error('newFile')
                                <p class="text-[12px] text-danger mb-3">{{ $message }}</p>
                            @enderror

                            <div class="mb-4">
                                <label class="form-label">Teks Alternatif (Alt Text)</label>
                                <input type="text" wire:model="newAltText" class="form-input" placeholder="Deskripsi gambar untuk SEO &amp; aksesibilitas">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Keterangan (Caption)</label>
                                <input type="text" wire:model="newCaption" class="form-input" placeholder="Keterangan yang tampil di bawah gambar">
                            </div>
                        </div>
                    @endif

                    {{-- Tab: Pustaka Media --}}
                    <div @if ($activeTab !== 'library') style="display:none" @endif>
                        <div class="relative mb-3 max-w-[280px]">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3 top-1/2 -translate-y-1/2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" wire:model.live.debounce.400ms="search" class="form-input pl-8" placeholder="Cari nama berkas...">
                        </div>

                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 max-h-[360px] overflow-y-auto pr-1">
                            @forelse ($mediaItems as $item)
                                @php $media = $item->getFirstMedia('library'); @endphp
                                @continue(! $media)
                                <button
                                    type="button"
                                    class="media-picker-item relative rounded-lg overflow-hidden border-2 {{ $selectedMediaId === $media->id ? 'border-brand-600' : 'border-transparent' }} text-left"
                                    wire:click="selectMedia({{ $media->id }})"
                                >
                                    <div class="h-20 bg-[#EDEFF4]">
                                        <img src="{{ $media->getUrl('small') }}" alt="{{ $media->getCustomProperty('alt_text', '') }}" class="w-full h-20 object-cover">
                                    </div>
                                    @if ($selectedMediaId === $media->id)
                                        <div class="media-picker-check absolute inset-0 flex items-center justify-center bg-brand-600/50">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M20 6L9 17l-5-5"></path></svg>
                                        </div>
                                    @endif
                                    <div class="px-1.5 py-1 bg-white">
                                        <div class="text-[10px] font-medium truncate">{{ $media->file_name }}</div>
                                        <div class="text-[9.5px] text-[#848CA3]">{{ round($media->size / 1024) }} KB</div>
                                    </div>
                                </button>
                            @empty
                                <p class="col-span-full text-center text-[#848CA3] py-8 text-[13px]">Tidak ada gambar ditemukan.</p>
                            @endforelse
                        </div>

                        <div class="mt-3">
                            {{ $mediaItems->links() }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-between items-center gap-3 px-6 py-4 border-t border-[#E4E8EF]">
                    <div class="text-[12.5px] text-[#6C7387]">
                        @if ($activeTab === 'library' && $selectedMediaId)
                            Gambar dipilih
                        @elseif ($activeTab === 'upload' && $newFile)
                            Siap diunggah
                        @else
                            Belum ada gambar dipilih
                        @endif
                    </div>
                    <div class="flex gap-2.5">
                        <button type="button" class="btn-secondary" wire:click="close">Batal</button>
                        @if ($activeTab === 'upload' && $canUpload)
                            <button type="button" class="btn-primary" wire:click="uploadAndSelect" @if (! $newFile) disabled @endif wire:loading.attr="disabled" wire:target="uploadAndSelect">
                                <span wire:loading.remove wire:target="uploadAndSelect">Unggah &amp; Gunakan</span>
                                <span wire:loading wire:target="uploadAndSelect">Mengunggah...</span>
                            </button>
                        @else
                            <button type="button" class="btn-primary" wire:click="confirmSelection" @if (! $selectedMediaId) disabled @endif>Gunakan Gambar Ini</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>