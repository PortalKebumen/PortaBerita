<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Editor WYSIWYG &mdash; PK-28</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/media-library-design.css') }}">
    @livewireStyles
</head>
<body class="bg-[#F1F3F7] text-[#171B28] font-sans">

    <div class="p-4 sm:p-7 max-w-none mx-auto">

        <h3 class="text-[16px] font-bold mb-4">Contoh Pemakaian Komponen Editor (x-editor)</h3>

        <div class="card overflow-hidden p-4">
            <label class="form-label">Konten Artikel</label>
            <x-editor name="konten" target="konten-artikel" placeholder="Tulis isi artikel di sini..." />
        </div>

    </div>

    <livewire:media-picker />

    @livewireScripts
</body>
</html>