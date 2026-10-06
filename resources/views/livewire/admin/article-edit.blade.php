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
                <span class="text-[#171B28] font-medium">Edit Artikel</span>
            </div>
            <h2 class="text-[20px] font-bold leading-tight">Edit Artikel</h2>
            <p class="text-[13px] text-[#6C7387] mt-1">Perbarui detail artikel di bawah, lalu simpan perubahannya.</p>
        </div>
    </div>


    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="alert-success">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><path d="M20 6L9 17l-5-5"></path></svg>
            <div><strong class="font-semibold">Berhasil:</strong> {{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="alert-danger">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="9"></circle><path d="M15 9l-6 6M9 9l6 6"></path></svg>
            <div><strong class="font-semibold">Gagal:</strong> {{ session('error') }}</div>
        </div>
    @endif

    {{-- Revision Alert for Rejected/Resubmitted Articles --}}
    @if ($status === 'rejected' && $this->revisions->isNotEmpty())
        <div class="alert-warning">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><path d="M12 9v4M12 17h.01"></path><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"></path></svg>
            <div>
                <h4 class="text-[14px] font-bold mb-1">Artikel Ditolak - Perlu Revisi</h4>
                <p class="text-[13px] mb-2">Artikel ini ditolak oleh redaktur. Silakan periksa catatan revisi di bawah, perbaiki artikel, lalu kirim ulang untuk review.</p>
                <div class="text-[12px] font-medium">{{ $this->revisions->count() }} catatan revisi tersedia</div>
            </div>
        </div>
    @elseif ($status === 'submitted' && $this->revisions->isNotEmpty())
        <div class="alert-info">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="9"></circle><path d="M12 16v-5M12 8h.01"></path></svg>
            <div>
                <h4 class="text-[14px] font-bold mb-1">Pengajuan Ulang Setelah Revisi</h4>
                <p class="text-[13px] mb-2">Artikel ini telah diperbaiki dan dikirim ulang untuk review. Redaktur dapat melihat riwayat revisi sebelumnya di bawah.</p>
                <div class="text-[12px] font-medium">{{ $this->revisions->count() }} riwayat revisi</div>
            </div>
        </div>
    @elseif ($status === 'submitted')
        <div class="alert-info">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="9"></circle><path d="M12 16v-5M12 8h.01"></path></svg>
            <div>
                <h4 class="text-[14px] font-bold mb-1">✅ Artikel Sedang Direview</h4>
                <p class="text-[13px]">Artikel telah dikirim ke redaktur untuk direview. Anda akan menerima notifikasi jika artikel disetujui atau memerlukan revisi.</p>
            </div>
        </div>
    @endif


    {{-- Form dalam 1 card --}}
    <form wire:submit.prevent="save" class="card p-6 max-w-[880px]">
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
                        placeholder="slug-artikel"
                        @disabled($article->published_at)
                        class="form-input @error('slug') is-error @enderror">
                </div>
                @error('slug') <p class="form-error-text">{{ $message }}</p> @enderror
                <p class="text-[11px] text-[#848CA3] mt-1">
                    @if ($article->published_at)
                        Slug dikunci karena artikel sudah pernah terbit, agar tautan lama tidak putus.
                    @else
                        Diisi otomatis dari judul. Bisa diedit manual — hanya huruf kecil, angka, dan tanda hubung.
                    @endif
                </p>
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
                <div class="flex items-center gap-2">
                    <span class="badge-{{ $article->status->badge() }} text-[13px]">
                        <span class="badge-dot"></span>{{ ucfirst($status) }}
                    </span>
                    @if ($status === 'submitted')
                        <span class="text-[11px] text-blue-700 bg-blue-100 px-2.5 py-1 rounded-full font-medium">⏳ Menunggu review redaktur</span>
                    @endif
                </div>
                <p class="text-[11px] text-[#848CA3] mt-1">Status dikelola melalui workflow lifecycle</p>
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

        {{-- Scheduling Section (for Draft/Rejected only) --}}
        @if (in_array($status, ['draft', 'rejected']))
            @can('articles.schedule')
                <div class="mb-5">
                    <label class="form-label">Jadwal Publikasi (Opsional)</label>
                    <input type="datetime-local"
                        wire:model="scheduled_at"
                        class="form-input @error('scheduled_at') is-error @enderror"
                        min="{{ now()->format('Y-m-d\TH:i') }}">
                    <p class="text-[11px] text-[#848CA3] mt-1">Artikel akan otomatis dipublikasikan pada waktu yang ditentukan setelah disetujui.</p>
                    @error('scheduled_at') <p class="form-error-text">{{ $message }}</p> @enderror
                </div>
            @endcan
        @elseif ($article->scheduled_at)
            <div class="mb-5">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="text-[13px] font-semibold text-blue-900 mb-1">Dijadwalkan untuk Publikasi</div>
                    <div class="text-[13px] text-blue-700">{{ $article->scheduled_at->format('d M Y, H:i') }} WIB</div>
                </div>
            </div>
        @endif

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


        {{-- Revision History - More Prominent --}}
        @if ($this->revisions->isNotEmpty())
            <div class="bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-200 rounded-xl p-5 mb-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-bold text-red-900">Catatan Revisi dari Redaktur</h3>
                        <p class="text-[12px] text-red-700">{{ $this->revisions->count() }} catatan revisi</p>
                    </div>
                </div>
                <div class="space-y-3">
                    @foreach ($this->revisions as $revision)
                        <div class="flex gap-3 pb-3 border-b border-red-200 last:border-b-0 last:pb-0 bg-white rounded-lg p-3">
                            <div class="w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 text-[12px] font-bold">
                                {{ strtoupper(substr($revision->editor->name ?? 'E', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="text-[13px] font-bold text-red-900">{{ $revision->editor->name ?? 'Editor' }}</span>
                                    <span class="text-[11px] text-red-600 bg-red-100 px-2 py-0.5 rounded">{{ $revision->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-[13px] text-gray-800 leading-relaxed">{{ $revision->content }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex justify-end gap-2.5 pt-4 border-t border-[#E4E8EF]">
            <a href="{{ route('admin.artikel.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Simpan Perubahan</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>

    {{-- Lifecycle Action Buttons - MOVED OUTSIDE form --}}

    {{-- Lifecycle Action Buttons - OUTSIDE form to prevent Livewire interference --}}
    @php
        $canSubmit = in_array($status, ['draft', 'rejected']) && auth()->user()->can('submit', $article);
        $canApprove = $status === 'submitted' && auth()->user()->can('approve', $article);
        $canReject = in_array($status, ['submitted', 'approved']) && auth()->user()->can('reject', $article);
        $canPublish = $status === 'approved' && auth()->user()->can('publish', $article);
        $canSchedule = $status === 'approved' && auth()->user()->can('schedule', $article);
        $canArchive = $status === 'published' && auth()->user()->can('archive', $article);
    @endphp

    @if ($canSubmit || $canApprove || $canReject || $canPublish || $canSchedule || $canArchive)
        <div class="card p-6 max-w-[880px]">
            <div class="bg-[#F7F8FA] border border-[#E4E8EF] rounded-lg p-4">
                <div class="text-[13px] font-semibold text-[#171B28] mb-3">Aksi Workflow</div>
                <div class="flex flex-wrap gap-2">
                    @if ($canSubmit)
                        <button type="button"
                            onclick="window.pkModal.open('modal-submit')"
                            class="btn-primary btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline">
                                <path d="M22 2L11 13"></path>
                                <path d="M22 2l-7 20-4-9-9-4 20-7z"></path>
                            </svg>
                            {{ $status === 'rejected' ? 'Kirim Review Ulang' : 'Kirim Review' }}
                        </button>
                    @endif

                    @if ($canApprove)
                        <form action="{{ route('admin.artikel.approve', $article) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn-sm bg-green-600 hover:bg-green-700 text-white rounded-lg px-4 py-2 text-[13px] font-medium transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline">
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                                Setujui
                            </button>
                        </form>
                    @endif

                    @if ($canReject)
                        <button type="button" onclick="window.pkModal.open('modal-reject')" class="btn-sm bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-[13px] font-medium transition-colors">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline">
                                <path d="M18 6L6 18M6 6l12 12"></path>
                            </svg>
                            Tolak
                        </button>
                    @endif

                    @if ($canPublish)
                        <button type="button"
                            onclick="window.pkModal.open('modal-publish')"
                            class="btn-primary btn-sm bg-emerald-600 hover:bg-emerald-700">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l4 2"></path>
                            </svg>
                            Terbitkan Sekarang
                        </button>
                    @endif

                    @if ($canSchedule)
                        <button type="button" onclick="window.pkModal.open('modal-schedule')" class="btn-secondary btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                            </svg>
                            Jadwalkan
                        </button>
                    @endif

                    @if ($canArchive)
                        <form action="{{ route('admin.artikel.archive', $article) }}" method="POST" class="inline" onsubmit="return confirm('Arsipkan artikel ini?')">
                            @csrf
                            <button type="submit" class="btn-sm bg-gray-600 hover:bg-gray-700 text-white rounded-lg px-4 py-2 text-[13px] font-medium transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline">
                                    <path d="M21 8v13H3V8"></path>
                                    <path d="M1 3h22v5H1z"></path>
                                    <path d="M10 12h4"></path>
                                </svg>
                                Arsipkan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Reject Article --}}
    <div id="modal-reject" class="modal-overlay hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[480px] w-full p-6 shadow-xl">
            <h3 class="text-[17px] font-bold mb-4">Tolak Artikel</h3>
            <form action="{{ route('admin.artikel.reject', $article) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea name="reason" rows="4" class="form-textarea" placeholder="Jelaskan alasan penolakan dan saran revisi..." required></textarea>
                </div>
                <div class="flex gap-2.5">
                    <button type="button" onclick="window.pkModal.close('modal-reject')" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2.5 text-[13px] font-medium transition-colors">Tolak Artikel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Submit/Resubmit Confirmation --}}
    <div id="modal-submit" class="modal-overlay hidden fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[520px] w-full p-6 shadow-xl">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13"></path>
                        <path d="M22 2l-7 20-4-9-9-4 20-7z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-[17px] font-bold text-gray-900 mb-1">
                        {{ $status === 'rejected' ? '📝 Kirim Review Ulang?' : '📤 Kirim Artikel untuk Review?' }}
                    </h3>
                    <p class="text-[14px] text-gray-600">
                        {{ $status === 'rejected' 
                            ? 'Artikel yang sudah diperbaiki akan dikirim kembali ke redaktur untuk direview.' 
                            : 'Artikel ini akan dikirim ke redaktur untuk direview.'
                        }}
                    </p>
                </div>
            </div>

            @if ($status === 'rejected')
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                    <div class="text-[13px] font-semibold text-orange-900 mb-2">Pastikan Anda sudah:</div>
                    <div class="space-y-1.5 text-[13px] text-orange-800">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-orange-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Membaca semua catatan revisi dari redaktur</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-orange-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Memperbaiki artikel sesuai saran redaktur</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-orange-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Menyimpan perubahan dengan klik "Simpan Perubahan"</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-[13px] text-blue-800">Pastikan artikel sudah lengkap dan siap untuk ditinjau oleh redaktur.</p>
                </div>
            @endif

            <div class="flex gap-3">
                <button type="button" onclick="window.pkModal.close('modal-submit')" class="flex-1 btn-secondary">
                    Batal
                </button>
                <form action="{{ route('admin.artikel.submit', $article) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full btn-primary">
                        {{ $status === 'rejected' ? 'Kirim Review Ulang' : 'Ya, Kirim Review' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Publish Confirmation (Redaktur) --}}
    <div id="modal-publish" class="modal-overlay hidden fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[500px] w-full p-6 shadow-xl">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-[17px] font-bold text-gray-900 mb-1">
                        🚀 Terbitkan Artikel Sekarang?
                    </h3>
                    <p class="text-[14px] text-gray-600">
                        Artikel ini telah disetujui (Approved) dan siap dipublikasikan ke publik.
                    </p>
                </div>
            </div>

            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-5 space-y-2">
                <div class="text-[13px] font-semibold text-emerald-900">Konfirmasi Publikasi:</div>
                <ul class="text-[12.5px] text-emerald-800 space-y-1.5">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Artikel akan langsung tayang secara publik di portal</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Waktu publikasi akan otomatis dicatat saat ini</span>
                    </li>
                    @if ($article->scheduled_at)
                        <li class="flex items-center gap-2 text-amber-800">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span>Jadwal rilis sebelumnya akan dibatalkan karena langsung diterbitkan</span>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="window.pkModal.close('modal-publish')" class="flex-1 btn-secondary">
                    Batal
                </button>
                <form action="{{ route('admin.artikel.publish', $article) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full btn-primary bg-emerald-600 hover:bg-emerald-700">
                        Ya, Terbitkan Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Schedule Article --}}
    <div id="modal-schedule" class="modal-overlay hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
        <div class="bg-white rounded-card max-w-[420px] w-full p-6 shadow-xl">
            <h3 class="text-[17px] font-bold mb-4">Jadwalkan Publikasi</h3>
            <form action="{{ route('admin.artikel.schedule', $article) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Tanggal & Waktu Publikasi</label>
                    <input type="datetime-local" name="scheduled_at" class="form-input" required min="{{ now()->format('Y-m-d\TH:i') }}">
                    <p class="text-[11px] text-[#848CA3] mt-1">Artikel akan otomatis dipublikasikan pada waktu yang ditentukan</p>
                </div>
                <div class="flex gap-2.5">
                    <button type="button" onclick="window.pkModal.close('modal-schedule')" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1">Jadwalkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Media Picker Component (required by x-editor for image insertion) --}}
    <livewire:media-picker />
</div>
