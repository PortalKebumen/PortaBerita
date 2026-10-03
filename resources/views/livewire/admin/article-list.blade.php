<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center gap-3">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6L9 17l-5-5"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 flex items-center gap-3">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 8v4M12 16h.01"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Toolbar: Search & Filters --}}
    <div class="flex flex-col lg:flex-row lg:items-center gap-3">
        <div class="relative flex-1 max-w-[360px]">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari judul artikel..."
                class="form-input pl-9">
        </div>

        <select wire:model.live="category_id" class="form-select w-full lg:w-48">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="status" class="form-select w-full lg:w-44">
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="published">Published</option>
            <option value="archived">Archived</option>
        </select>

        <div class="flex gap-2.5 lg:ml-auto">
            {{-- Bulk Actions Dropdown --}}
            <div class="relative inline-block group">
                <button type="button" class="btn-secondary flex items-center gap-2">
                    Aksi Massal
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9l6 6 6-6"></path>
                    </svg>
                </button>
                <div class="absolute right-0 top-full mt-1 hidden group-hover:block bg-white rounded-lg shadow-lg border border-[#E4E8EF] min-w-[180px] z-10">
                    <button type="button" class="w-full text-left px-4 py-2.5 text-sm text-[#171B28] hover:bg-[#F1F3F7] flex items-center gap-2 border-b border-[#E4E8EF]">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                        Setujui Terpilih
                    </button>
                    <button type="button" class="w-full text-left px-4 py-2.5 text-sm text-[#171B28] hover:bg-[#F1F3F7] flex items-center gap-2">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                            <path d="M7 10l5 5 5-5M12 15V3"></path>
                        </svg>
                        Hapus Terpilih
                    </button>
                </div>
            </div>

            @can('articles.create')
                <a href="{{ route('admin.artikel.create') }}" class="btn-primary shrink-0 flex items-center gap-2">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                    Tambah Artikel
                </a>
            @endcan
        </div>
    </div>

    {{-- Articles Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] border-collapse">
                <thead>
                    <tr>
                        <th class="px-4 py-2.5 border-b border-[#CDD3DF] w-10">
                            <input type="checkbox" class="form-checkbox">
                        </th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#CDD3DF]">JUDUL</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#CDD3DF]">PENULIS</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#CDD3DF]">KATEGORI</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#CDD3DF] whitespace-nowrap">STATUS</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#CDD3DF] whitespace-nowrap">VIEWS</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#CDD3DF] whitespace-nowrap">TANGGAL</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#CDD3DF] whitespace-nowrap">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->articles as $article)
                        <tr class="border-b border-[#E4E8EF] hover:bg-gray-50/50">
                            <td class="px-4 py-3">
                                <input type="checkbox" class="form-checkbox">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if ($article->is_breaking)
                                        <span class="text-[9.5px] font-bold text-white bg-accent-500 px-1.5 py-0.5 rounded uppercase shrink-0">Breaking</span>
                                    @endif
                                    @if ($article->is_advertorial)
                                        <span class="text-[9.5px] font-bold text-warning bg-warning-bg px-1.5 py-0.5 rounded uppercase shrink-0">Advertorial</span>
                                    @endif
                                    @if ($article->revisions_count > 0 && in_array($article->status->value, ['submitted', 'rejected']))
                                        <span class="text-[9.5px] font-bold text-orange-700 bg-orange-100 px-1.5 py-0.5 rounded uppercase shrink-0" title="{{ $article->revisions_count }} catatan revisi">
                                            Revisi ({{ $article->revisions_count }})
                                        </span>
                                    @endif
                                    <span class="font-semibold text-sm leading-snug">{{ $article->title }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm truncate">
                                <span class="block truncate">{{ $article->author?->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm truncate">
                                <span class="block truncate">{{ $article->category?->name ?? '-' }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="badge-{{ match($article->status->value) { 'draft' => 'neutral', 'submitted' => 'info', 'approved' => 'success', 'rejected' => 'danger', 'published' => 'primary', 'archived' => 'neutral', default => 'neutral' } }}">
                                    <span class="badge-dot"></span>{{ ucfirst($article->status->value) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 font-mono text-sm whitespace-nowrap">
                                {{ $article->views_count ?? 0 }}
                            </td>
                            <td class="px-3 py-3 font-mono text-sm whitespace-nowrap">
                                {{ $article->created_at->format('d/m') }}
                            </td>
                            <td class="px-2 py-3">
                                <div class="flex gap-1.5">
                                    {{-- Quick Actions for Workflow --}}
                                    @can('approve', $article)
                                        @if ($article->status->value === 'submitted')
                                            <button type="button"
                                                wire:click="openApproveModal({{ $article->id }}, '{{ addslashes($article->title) }}')"
                                                class="w-7 h-7 rounded-md bg-green-100 text-green-700 flex items-center justify-center hover:bg-green-600 hover:text-white transition-colors"
                                                title="Setujui Artikel">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path d="M20 6L9 17l-5-5"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    @endcan

                                    @can('publish', $article)
                                        @if ($article->status->value === 'approved')
                                            <button type="button"
                                                wire:click="openPublishModal({{ $article->id }}, '{{ addslashes($article->title) }}')"
                                                class="w-7 h-7 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-colors"
                                                title="Terbitkan Artikel">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <path d="M12 6v6l4 2"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    @endcan

                                    @can('update', $article)
                                        <a href="{{ route('admin.artikel.edit', $article) }}" 
                                            class="w-7 h-7 rounded-md bg-[#F1F3F7] flex items-center justify-center hover:bg-brand-50 text-[#171B28]"
                                            title="Edit Artikel">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 20h9"></path>
                                                <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"></path>
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('delete', $article)
                                        <button type="button"
                                            class="w-7 h-7 rounded-md bg-danger-bg text-danger flex items-center justify-center hover:bg-danger hover:text-white transition-colors"
                                            wire:click="openDeleteModal({{ $article->id }}, '{{ addslashes($article->title) }}')"
                                            title="Hapus Artikel">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 6h18"></path>
                                                <path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6"></path>
                                            </svg>
                                        </button>
                                    @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-[#848CA3]">
                                Belum ada artikel yang ditambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-t border-[#CDD3DF]">
            <span class="text-[12.5px] text-[#6C7387]">Menampilkan hasil artikel</span>
            <div>
                {{ $this->articles->links() }}
            </div>
        </div>
    </div>

    {{-- Modal: Hapus Artikel --}}
    @if ($showDeleteModal)
        <div class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="bg-white rounded-lg max-w-[420px] w-full p-6 shadow-xl">
                <div class="w-11 h-11 rounded-full bg-danger-bg text-danger flex items-center justify-center mb-4">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18"></path>
                        <path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6"></path>
                    </svg>
                </div>
                <h3 class="text-[16px] font-bold mb-1.5">Hapus artikel ini?</h3>
                <p class="text-[13px] text-[#6C7387] mb-5">Hapus artikel "{{ $deleteTitle }}"? Tindakan ini tidak dapat dibatalkan.</p>
                <div class="flex justify-end gap-2.5">
                    <button type="button" class="btn-secondary" wire:click="closeDeleteModal">Batal</button>
                    <button type="button" class="btn-danger" wire:click="confirmDelete" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmDelete">Ya, Hapus</span>
                        <span wire:loading wire:target="confirmDelete">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Terbitkan Artikel --}}
    @if ($showPublishModal)
        <div class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="bg-white rounded-2xl max-w-[480px] w-full p-6 shadow-xl">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v6l4 2"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-[17px] font-bold text-gray-900 mb-1">🚀 Terbitkan Artikel Sekarang?</h3>
                        <p class="text-[13px] text-[#6C7387]">
                            Apakah Anda yakin ingin menerbitkan artikel <strong class="text-gray-800">"{{ $publishTitle }}"</strong>?
                        </p>
                    </div>
                </div>

                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3.5 mb-5 text-[12.5px] text-emerald-800 space-y-1">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Artikel akan langsung tayang secara publik di portal</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Waktu publikasi akan otomatis dicatat saat ini</span>
                    </div>
                </div>

                <div class="flex justify-end gap-2.5">
                    <button type="button" class="btn-secondary" wire:click="closePublishModal">Batal</button>
                    <button type="button" class="btn-primary bg-emerald-600 hover:bg-emerald-700" wire:click="confirmPublish" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmPublish">Ya, Terbitkan Sekarang</span>
                        <span wire:loading wire:target="confirmPublish">Menerbitkan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Setujui Artikel --}}
    @if ($showApproveModal)
        <div class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="bg-white rounded-2xl max-w-[480px] w-full p-6 shadow-xl">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-[17px] font-bold text-gray-900 mb-1">Setujui Artikel?</h3>
                        <p class="text-[13px] text-[#6C7387]">
                            Setujui artikel <strong class="text-gray-800">"{{ $approveTitle }}"</strong> untuk publikasi?
                        </p>
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-3.5 mb-5 text-[12.5px] text-green-800">
                    Artikel yang disetujui (Approved) siap untuk diterbitkan langsung atau dijadwalkan oleh redaktur.
                </div>

                <div class="flex justify-end gap-2.5">
                    <button type="button" class="btn-secondary" wire:click="closeApproveModal">Batal</button>
                    <button type="button" class="btn-primary bg-green-600 hover:bg-green-700" wire:click="confirmApprove" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmApprove">Ya, Setujui</span>
                        <span wire:loading wire:target="confirmApprove">Menyetujui...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
