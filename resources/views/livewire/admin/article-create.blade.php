<div class="space-y-6">
    {{-- Header dengan tombol kembali --}}
    <div class="flex items-start gap-3">
        <a href="{{ route('admin.artikel.index') }}" class="btn-icon bg-[#F1F3F7] shrink-0 mt-0.5" aria-label="Kembali ke Artikel">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5"></path>
                <path d="M12 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div class="min-w-0">
            <div class="text-[12px] text-[#848CA3] mb-1">
                <a href="{{ route('admin.artikel.index') }}" class="hover:text-brand-600 hover:underline">Artikel</a>
                <span class="mx-1">/</span>
                <span class="text-[#171B28] font-medium">Tambah Artikel Baru</span>
            </div>
            <h2 class="text-[20px] font-bold leading-tight">Tambah Artikel Baru</h2>
            <p class="text-[13px] text-[#6C7387] mt-1">Lengkapi formulir di bawah untuk mempublikasikan artikel baru ke PortalKebumen.com.</p>
        </div>
    </div>

    {{-- Form dalam 1 card --}}
    <form wire:submit.prevent="save" class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div class="md:col-span-2">
                <label class="form-label">Judul Artikel</label>
                <input type="text"
                    wire:model.live="title"
                    placeholder="Masukkan judul artikel..."
                    class="form-input @error('title') is-error @enderror">
                @error('title') <p class="form-error-text">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="form-label">Slug (URL Artikel)</label>
                <div class="flex items-center gap-2">
                    <span class="text-[13px] text-[#848CA3] shrink-0">/artikel/</span>
                    <input type="text"
                        wire:model.live="slug"
                        placeholder="slug-artikel-otomatis-dari-judul"
                        class="form-input @error('slug') is-error @enderror">
                </div>
                @error('slug') <p class="form-error-text">{{ $message }}</p> @enderror
                <p class="text-[11px] text-[#848CA3] mt-1">Diisi otomatis dari judul. Bisa diedit manual — hanya huruf kecil, angka, dan tanda hubung.</p>
            </div>

            <div>
                <label class="form-label">Kategori</label>
                <select wire:model="category_id" class="form-select @error('category_id') is-error @enderror">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="form-error-text">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Status</label>
                <select class="form-select" disabled>
                    <option selected>Draft</option>
                </select>
                <p class="text-[11px] text-[#848CA3] mt-1">Artikel baru selalu dimulai sebagai Draft</p>
            </div>
        </div>

        <div class="mb-5">
            <label class="form-label">Ringkasan (Excerpt)</label>
            <textarea wire:model="excerpt"
                placeholder="Ringkasan singkat untuk tampilan kartu berita..."
                rows="2"
                class="form-textarea @error('excerpt') is-error @enderror"></textarea>
            @error('excerpt') <p class="form-error-text">{{ $message }}</p> @enderror
        </div>

        <div class="mb-5">
            <label class="form-label">Gambar Sampul</label>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 border border-[#E4E8EF] rounded-lg p-3">
                <div class="w-16 h-14 rounded-lg shrink-0 overflow-hidden bg-[#F1F3F7] flex items-center justify-center text-[#848CA3]">
                    @if ($featured_image_url)
                        <img src="{{ $featured_image_url }}" alt="Pratinjau" class="w-full h-full object-cover">
                    @else
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <rect x="3" y="4" width="18" height="14" rx="2"></rect>
                            <circle cx="8.5" cy="10" r="1.6"></circle>
                            <path d="M3 16l5-4 4 3 3-3 6 5"></path>
                        </svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[12.5px] font-medium truncate">
                        {{ $featured_image_url ? 'Gambar terpilih' : 'Belum ada gambar dipilih' }}
                    </div>
                    <div class="text-[11px] text-[#848CA3] mt-0.5">Rekomendasi 1200×675px</div>
                </div>
                <button type="button"
                    wire:click="$dispatch('open-media-picker', { target: 'article-featured' })"
                    class="btn-secondary btn-sm shrink-0">
                    Pilih Gambar
                </button>
            </div>
        </div>

        <div class="mb-5">
            <label class="form-label">Isi Artikel</label>
            <x-editor name="content" bind="content" value="{{ $content }}" />
            @error('content') <p class="form-error-text">{{ $message }}</p> @enderror
        </div>

        @canany(['articles.mark-breaking', 'articles.mark-advertorial'])
            <div class="flex flex-wrap gap-x-8 gap-y-3 mb-6">
                @can('articles.mark-breaking')
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model="is_breaking" class="form-checkbox">
                        <span class="text-[13px]">Tandai sebagai Breaking News</span>
                    </label>
                @endcan
                @can('articles.mark-advertorial')
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model="is_advertorial" class="form-checkbox">
                        <span class="text-[13px]">Advertorial</span>
                    </label>
                @endcan
            </div>
        @endcanany

        {{-- Scheduling Section --}}
        @can('articles.schedule')
            <div class="mb-5">
                <label class="form-label">Jadwal Publikasi (Opsional)</label>
                <input type="datetime-local"
                    wire:model="scheduled_at"
                    class="form-input @error('scheduled_at') is-error @enderror"
                    min="{{ now()->format('Y-m-d\TH:i') }}">
                <p class="text-[11px] text-[#848CA3] mt-1">Biarkan kosong untuk menyimpan sebagai Draft. Artikel akan otomatis dipublikasikan setelah disetujui.</p>
                @error('scheduled_at') <p class="form-error-text">{{ $message }}</p> @enderror
            </div>
        @endcan

        {{-- SEO Section (Collapsible) --}}
        <details class="border border-[#E4E8EF] rounded-lg mb-5">
            <summary class="cursor-pointer px-4 py-3 text-[13px] font-semibold text-[#171B28] hover:bg-[#F7F8FA] transition-colors rounded-t-lg">
                Pengaturan SEO
            </summary>
            <div class="p-4 space-y-4 border-t border-[#E4E8EF]">
                <div>
                    <label class="form-label">Meta Title</label>
                    <input type="text"
                        wire:model="meta_title"
                        placeholder="Judul untuk mesin pencari (maks. 60 karakter)"
                        maxlength="60"
                        class="form-input @error('meta_title') is-error @enderror">
                    <p class="text-[11px] text-[#848CA3] mt-1">Biarkan kosong untuk menggunakan judul artikel</p>
                    @error('meta_title') <p class="form-error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Meta Description</label>
                    <textarea wire:model="meta_description"
                        placeholder="Deskripsi untuk mesin pencari (maks. 160 karakter)"
                        maxlength="160"
                        rows="2"
                        class="form-textarea @error('meta_description') is-error @enderror"></textarea>
                    <p class="text-[11px] text-[#848CA3] mt-1">Biarkan kosong untuk menggunakan excerpt</p>
                    @error('meta_description') <p class="form-error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">OG Image (Open Graph)</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 border border-[#E4E8EF] rounded-lg p-3">
                        <div class="w-16 h-14 rounded-lg shrink-0 overflow-hidden bg-[#F1F3F7] flex items-center justify-center text-[#848CA3]">
                            @if ($og_image_url)
                                <img src="{{ $og_image_url }}" alt="Pratinjau OG" class="w-full h-full object-cover">
                            @else
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <rect x="3" y="4" width="18" height="14" rx="2"></rect>
                                    <circle cx="8.5" cy="10" r="1.6"></circle>
                                    <path d="M3 16l5-4 4 3 3-3 6 5"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[12.5px] font-medium truncate">
                                {{ $og_image_url ? 'OG image terpilih' : 'Belum ada OG image dipilih' }}
                            </div>
                            <div class="text-[11px] text-[#848CA3] mt-0.5">Untuk sharing di sosial media (1200×630px)</div>
                        </div>
                        <button type="button"
                            wire:click="$dispatch('open-media-picker', { target: 'article-og' })"
                            class="btn-secondary btn-sm shrink-0">
                            Pilih OG Image
                        </button>
                    </div>
                    <p class="text-[11px] text-[#848CA3] mt-1">Biarkan kosong untuk menggunakan gambar sampul</p>
                </div>

                @can('seo.set-indexing')
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="noindex" class="form-checkbox">
                            <span class="text-[13px]">No Index (jangan tampilkan di hasil pencarian)</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="nofollow" class="form-checkbox">
                            <span class="text-[13px]">No Follow (jangan ikuti link)</span>
                        </label>
                    </div>
                @endcan
            </div>
        </details>


        <div class="flex justify-end gap-2.5 pt-4 border-t border-[#E4E8EF]">
            <a href="{{ route('admin.artikel.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Simpan Artikel</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>

    {{-- Media Picker Component (required by x-editor for image insertion) --}}
    <livewire:media-picker />
</div>
