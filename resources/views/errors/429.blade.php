{{-- Ringan & mandiri (tanpa layout/kueri database) karena tampil justru saat ada lonjakan permintaan --}}
@php
    $retry = (int) (($exception ?? null)?->getHeaders()['Retry-After'] ?? 60);
    $wait = $retry >= 120 ? ceil($retry / 60) . ' menit' : $retry . ' detik';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Terlalu Banyak Permintaan · {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               background: #F3F5F9; color: #171B28; font: 15px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .card { width: 100%; max-width: 440px; background: #fff; border: 1px solid #E4E8EF; border-radius: 16px; padding: 32px 28px; text-align: center; }
        img { height: 36px; width: auto; margin-bottom: 20px; }
        .code { display: inline-block; font-weight: 700; font-size: 12px; letter-spacing: .08em; color: #1E398E; background: #EEF1FA; border-radius: 999px; padding: 4px 12px; }
        h1 { font-size: 20px; margin: 14px 0 8px; }
        p { margin: 0 0 8px; color: #5B6378; }
        a { display: inline-block; margin-top: 18px; background: #1E398E; color: #fff; text-decoration: none; font-weight: 600; padding: 10px 18px; border-radius: 10px; }
        a:hover { background: #142B57; }
    </style>
</head>
<body>
    <main class="card">
        <img src="{{ asset('images/logo-full.png') }}" alt="{{ config('app.name') }}">
        <div><span class="code">429</span></div>
        <h1>Terlalu banyak permintaan</h1>
        <p>Kami menerima terlalu banyak permintaan dari jaringan Anda dalam waktu singkat.</p>
        <p>Silakan tunggu sekitar <strong>{{ $wait }}</strong>, lalu coba lagi.</p>
        <a href="{{ url('/') }}">Kembali ke Beranda</a>
    </main>
</body>
</html>
