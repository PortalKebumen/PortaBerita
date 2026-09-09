@props(['title' => null])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' - Admin PortalKebumen' : 'Admin PortalKebumen' }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .form-error-text { color: #dc2626; }
    </style>
</head>
<body class="bg-[#F1F3F7] text-[#171B28] font-sans min-h-screen relative overflow-hidden">

    {{-- Dekorasi background lembut, tetap pakai warna brand/accent --}}
    <div class="pointer-events-none absolute -top-24 -left-24 w-[420px] h-[420px] rounded-full bg-brand-500/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -right-16 w-[420px] h-[420px] rounded-full bg-accent-500/10 blur-3xl"></div>

    <div class="relative min-h-screen flex flex-col items-center justify-center p-4">
        <img src="{{ asset('images/logo-full.png') }}" alt="PortalKebumen.com" class="w-64 sm:w-72 h-auto mb-8">

        <div class="w-full max-w-[400px] bg-white rounded-card shadow-lg border border-[#E4E8EF] p-7 sm:p-8">
            {{ $slot }}
        </div>

        <p class="relative text-[11.5px] text-[#848CA3] mt-8">
            &copy; {{ date('Y') }} PortalKebumen.com | Internal Admin
        </p>
    </div>
    <script>
        document.addEventListener('invalid', (event) => {
            const field = event.target;
            if (typeof field.setCustomValidity !== 'function') return;
            field.setCustomValidity('');
            const label = field.labels?.[0]?.textContent.trim() || 'Kolom ini';
            if (field.validity.valueMissing) {
                field.setCustomValidity(label + ' wajib diisi.');
            } else if (field.validity.typeMismatch && field.type === 'email') {
                field.setCustomValidity('Masukkan alamat email yang valid.');
            } else if (!field.validity.valid) {
                field.setCustomValidity('Periksa kembali isian ' + label.toLowerCase() + '.');
            }
        }, true);
        document.addEventListener('input', (event) => {
            if (typeof event.target.setCustomValidity === 'function') {
                event.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
