<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Media Picker &mdash; PK-29</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/media-library-design.css') }}">
    @livewireStyles
</head>
<body class="bg-[#F1F3F7] text-[#171B28] font-sans">

    <div class="p-4 sm:p-7 max-w-none mx-auto">

        <h3 class="text-[16px] font-bold mb-4">Contoh Pemakaian Komponen Media Picker</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="card overflow-hidden p-4">
                <label class="form-label">Gambar Sampul (contoh field)</label>
                <div id="preview-cover-artikel" class="h-28 bg-[#EDEFF4] rounded-lg mb-3 flex items-center justify-center text-[#848CA3] text-[12.5px] overflow-hidden">
                    Belum ada gambar
                </div>
                <button type="button" class="btn-secondary" id="btn-pilih-cover">
                    Pilih Gambar
                </button>
            </div>

            <div class="card overflow-hidden p-4">
                <label class="form-label">Gambar Banner (contoh field kedua)</label>
                <div id="preview-banner-iklan" class="h-28 bg-[#EDEFF4] rounded-lg mb-3 flex items-center justify-center text-[#848CA3] text-[12.5px] overflow-hidden">
                    Belum ada gambar
                </div>
                <button type="button" class="btn-secondary" id="btn-pilih-banner">
                    Pilih Gambar
                </button>
            </div>
        </div>

    </div>

    <livewire:media-picker />

    @livewireScripts
    <script>
        document.addEventListener('livewire:init', () => {
            document.getElementById('btn-pilih-cover').addEventListener('click', () => {
                let picker = Livewire.getByName('media-picker')[0];
                picker.openFor('cover-artikel');
            });

            document.getElementById('btn-pilih-banner').addEventListener('click', () => {
                let picker = Livewire.getByName('media-picker')[0];
                picker.openFor('banner-iklan');
            });

            Livewire.on('media-picker-selected', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const previewEl = document.getElementById('preview-' + data.target);
                if (previewEl) {
                    previewEl.innerHTML = '<img src="' + data.media.url + '" alt="' + (data.media.alt_text || '') + '" style="width:100%;height:100%;object-fit:cover;">';
                }
            });
        });
    </script>
</body>
</html>