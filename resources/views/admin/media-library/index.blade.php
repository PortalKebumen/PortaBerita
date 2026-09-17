<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Media Library &mdash; Admin PortalKebumen</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/media-library-design.css') }}">
</head>
<body class="bg-[#F1F3F7] text-[#171B28] font-sans">

{{--
    CATATAN: ini sengaja HANYA konten Media Library (tanpa sidebar & header admin),
    karena shared layout admin itu bukan bagian tugas PK-27.
    Nanti begitu shared layout admin sudah ada dari tim,
    konten di dalam <div class="p-4 sm:p-7 max-w-none mx-auto"> ini
    tinggal dipindah/di-@include ke dalam layout tersebut.
--}}

<div class="p-4 sm:p-7 max-w-none mx-auto">

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-[#E7F7EE] text-[#146C43] text-[13px] font-medium px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.media-library.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5 flex-wrap">
        <div class="relative flex-1 max-w-[320px]">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="search" value="{{ request('search') }}" class="form-input pl-9" placeholder="Cari nama berkas...">
        </div>
        <select name="type" class="form-select w-full sm:w-44" onchange="this.form.submit()">
            <option value="" {{ request('type') === null || request('type') === '' ? 'selected' : '' }}>Semua Tipe</option>
            <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Gambar</option>
            <option value="document" {{ request('type') === 'document' ? 'selected' : '' }}>Dokumen</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input w-full sm:w-44" title="Dari tanggal" onchange="this.form.submit()">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input w-full sm:w-44" title="Sampai tanggal" onchange="this.form.submit()">
        <button type="button" class="btn-primary sm:ml-auto shrink-0" data-modal-open="modal-upload">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4M12 4l-4 4M12 4l4 4"></path><path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path></svg>
            Unggah Media
        </button>
    </form>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
        @forelse ($mediaItems as $item)
            @php
                $media = $item->getFirstMedia('library');
                if (! $media) continue;
                $isImage = str_starts_with($media->mime_type, 'image/');
                $sizeKb = $media->size / 1024;
                $sizeLabel = $sizeKb >= 1024 ? round($sizeKb / 1024, 1) . ' MB' : round($sizeKb) . ' KB';
            @endphp
            <div class="card overflow-hidden group relative">
                @if ($isImage)
                    <div class="h-28 bg-[#EDEFF4]">
                        <img src="{{ $media->getUrl('small') }}" alt="{{ $media->getCustomProperty('alt_text', '') }}" class="w-full h-28 object-cover">
                    </div>
                @else
                    <div class="h-28 bg-gradient-to-br from-brand-700 to-brand-500 flex items-center justify-center text-white/40">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="14" rx="2"></rect><circle cx="8.5" cy="10" r="1.6"></circle><path d="M3 16l5-4 4 3 3-3 6 5"></path></svg>
                    </div>
                @endif
                <div class="absolute top-2 right-2 hidden group-hover:flex gap-1.5">
                    <button
                        type="button"
                        class="w-7 h-7 rounded-md bg-white/90 flex items-center justify-center shadow"
                        aria-label="Edit"
                        onclick="openMediaEditModal({{ $media->id }}, {{ Js::from($media->file_name) }}, {{ Js::from($sizeLabel) }}, {{ Js::from($media->getCustomProperty('alt_text', '')) }}, {{ Js::from($media->getCustomProperty('caption', '')) }})"
                    >
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#171B28" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"></path></svg>
                    </button>
                    <button
                        type="button"
                        class="w-7 h-7 rounded-md bg-white/90 text-danger flex items-center justify-center shadow"
                        aria-label="Hapus"
                        onclick="openDeleteModal({{ $media->id }})"
                    >
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6"></path></svg>
                    </button>
                </div>
                <div class="p-2.5">
                    <div class="text-[11.5px] font-semibold truncate" title="{{ $media->file_name }}">{{ $media->file_name }}</div>
                    <div class="text-[10.5px] text-[#848CA3] mt-0.5">{{ $sizeLabel }} &middot; {{ $media->created_at->translatedFormat('d M Y') }}</div>
                </div>
            </div>
        @empty
            <p class="col-span-full text-center text-[#848CA3] py-10">Belum ada media yang diunggah.</p>
        @endforelse
    </div>

    <div class="mt-2">
        {{ $mediaItems->links() }}
    </div>

</div>

{{-- Modal: Upload --}}
<div id="modal-upload" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
    <div class="bg-white rounded-card max-w-[500px] w-full p-6 shadow-xl">
        <form method="POST" action="{{ route('admin.media-library.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-[16px] font-bold">Unggah Media Baru</h3>
                <button type="button" class="btn-icon bg-[#F1F3F7]" data-modal-close aria-label="Tutup">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                </button>
            </div>
            <label class="flex flex-col items-center justify-center gap-2 border border-dashed border-[#CDD3DF] rounded-lg py-10 cursor-pointer hover:bg-[#F8F9FB] transition-colors mb-4">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="1.8"><path d="M12 16V4M12 4l-4 4M12 4l4 4"></path><path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path></svg>
                <span class="text-[12.5px] text-[#6C7387]"><span class="font-semibold text-brand-600">Klik untuk unggah</span> atau seret berkas ke sini</span>
                <span class="text-[11px] text-[#848CA3]">JPG, PNG, WEBP, PDF hingga 10MB</span>
                <input type="file" name="file" class="hidden" required>
            </label>
            @error('file')
                <p class="text-[12px] text-danger mb-3">{{ $message }}</p>
            @enderror
            <div class="mb-4">
                <label class="form-label">Teks Alternatif (Alt Text)</label>
                <input type="text" name="alt_text" class="form-input" placeholder="Deskripsi gambar untuk SEO &amp; aksesibilitas">
            </div>
            <div class="mb-5">
                <label class="form-label">Keterangan (Caption)</label>
                <input type="text" name="caption" class="form-input" placeholder="Keterangan yang tampil di bawah gambar">
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" class="btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn-primary">Unggah</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Edit metadata --}}
<div id="modal-media-edit" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
    <div class="bg-white rounded-card max-w-[460px] w-full p-6 shadow-xl">
        <form method="POST" id="form-edit-media" action="">
            @csrf
            @method('PUT')
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-[16px] font-bold">Edit Metadata Berkas</h3>
                <button type="button" class="btn-icon bg-[#F1F3F7]" data-modal-close aria-label="Tutup">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="flex items-center gap-3 mb-5 bg-[#F8F9FB] border border-[#E4E8EF] rounded-lg p-3">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-brand-700 to-brand-500 shrink-0"></div>
                <div class="min-w-0">
                    <div class="text-[13px] font-semibold truncate" id="media-edit-fname"></div>
                    <div class="text-[11.5px] text-[#848CA3]" id="media-edit-meta"></div>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Teks Alternatif (Alt Text)</label>
                <input type="text" name="alt_text" id="media-edit-alt" class="form-input">
            </div>
            <div class="mb-5">
                <label class="form-label">Keterangan (Caption)</label>
                <input type="text" name="caption" id="media-edit-caption" class="form-input">
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" class="btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Hapus --}}
<div id="modal-hapus-media" class="modal-overlay hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
    <div class="bg-white rounded-card max-w-[420px] w-full p-6 shadow-xl">
        <form method="POST" id="form-delete-media" action="">
            @csrf
            @method('DELETE')
            <div class="w-11 h-11 rounded-full bg-danger-bg text-danger flex items-center justify-center mb-4">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6"></path></svg>
            </div>
            <h3 class="text-[16px] font-bold mb-1.5">Hapus berkas ini?</h3>
            <p class="text-[13px] text-[#6C7387] mb-5">Berkas yang masih dipakai di artikel/iklan tertentu sebaiknya diganti dulu sebelum dihapus.</p>
            <div class="flex justify-end gap-2.5">
                <button type="button" class="btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modalOpeners = document.querySelectorAll('[data-modal-open]');
    modalOpeners.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = document.getElementById(btn.getAttribute('data-modal-open'));
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal(overlay);
        });
        overlay.querySelectorAll('[data-modal-close]').forEach(function (btn) {
            btn.addEventListener('click', function () { closeModal(overlay); });
        });
    });
    function closeModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.flex').forEach(closeModal);
        }
    });
})();

function openMediaEditModal(mediaId, fname, sizeLabel, altText, caption) {
    document.getElementById('media-edit-fname').textContent = fname;
    document.getElementById('media-edit-meta').textContent = sizeLabel;
    document.getElementById('media-edit-alt').value = altText;
    document.getElementById('media-edit-caption').value = caption;
    document.getElementById('form-edit-media').action = '{{ url('admin/media-library') }}/' + mediaId;

    var modal = document.getElementById('modal-media-edit');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openDeleteModal(mediaId) {
    document.getElementById('form-delete-media').action = '{{ url('admin/media-library') }}/' + mediaId;

    var modal = document.getElementById('modal-hapus-media');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
</script>

</body>
</html>