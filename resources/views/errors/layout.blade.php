{{--
    Layout halaman error. 4xx (401, 403, 404, 405, 419, ...) tampil di dalam layout situs: layout admin
    untuk pengguna yang sudah masuk di area /admin, layout publik (header & footer) untuk lainnya.
    429 & 5xx serta saat database tidak terjangkau memakai halaman mandiri di bawah (tanpa kueri database
    & tanpa aset Vite), karena justru muncul saat server kelebihan beban atau database bermasalah.
    Detail teknis error tidak pernah ditampilkan ke pengunjung (APP_DEBUG=false di server).
--}}
@php
    $code = trim($__env->yieldContent('code'));
    $inAdmin = request()->is('admin', 'admin/*');
    $loggedIn = rescue(fn () => auth()->check(), false, false);
    $home = $inAdmin && $loggedIn ? url('/admin') : url('/');
    $homeLabel = $inAdmin && $loggedIn ? 'Kembali ke Dashboard' : 'Kembali ke Beranda';

    $standalone = (int) $code === 429 || (int) $code >= 500
        || ! rescue(fn () => \Illuminate\Support\Facades\DB::connection()->getPdo() !== null, false, false);
    $variant = $standalone ? 'standalone' : ($inAdmin && $loggedIn ? 'admin' : 'public');
    $pageTitle = trim($__env->yieldContent('title'));
@endphp
@if ($variant === 'admin')
    <x-layouts.admin :title="$pageTitle">
        @include('errors.partials.panel')
    </x-layouts.admin>
@elseif ($variant === 'public')
    <x-layouts.public :title="$pageTitle" :noindex="true">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            @include('errors.partials.panel')
        </div>
    </x-layouts.public>
@else
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               background: #F3F5F9; color: #171B28; font: 15px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .card { width: 100%; max-width: 480px; background: #fff; border: 1px solid #E4E8EF; border-radius: 16px; padding: 36px 28px; text-align: center; }
        .logo { height: 36px; width: auto; margin-bottom: 22px; }
        .code { font-size: 64px; font-weight: 800; line-height: 1; letter-spacing: -.02em; color: #1E398E; }
        h1 { font-size: 20px; margin: 12px 0 8px; }
        p { margin: 0 0 8px; color: #5B6378; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 22px; }
        .btn { display: inline-block; text-decoration: none; font-weight: 600; padding: 10px 18px; border-radius: 10px; font-size: 14px; border: 0; cursor: pointer; font-family: inherit; }
        .btn-primary { background: #1E398E; color: #fff; }
        .btn-primary:hover { background: #142B57; }
        .btn-secondary { background: #E4E8EF; color: #171B28; }
        .btn-secondary:hover { background: #D7DCE6; }
        form.search { display: flex; gap: 8px; margin-top: 18px; }
        form.search input { flex: 1; min-width: 0; padding: 10px 12px; border: 1px solid #CDD3DF; border-radius: 10px; font: inherit; }
        form.search input:focus { outline: 2px solid #DCE2F5; border-color: #2C48A8; }
        .ref { margin-top: 18px; font-size: 12px; color: #848CA3; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <a href="{{ url('/') }}"><img class="logo" src="{{ asset('images/logo-header.png') }}" alt="{{ config('app.name') }}"></a>
        <div class="code">{{ $code }}</div>
        <h1>@yield('title')</h1>
        @yield('message')

        @hasSection('search')
            @unless ($inAdmin)
                <form class="search" action="{{ url('/cari') }}" method="GET" role="search">
                    <input type="search" name="q" placeholder="Cari berita..." aria-label="Cari berita">
                    <button class="btn btn-primary" type="submit">Cari</button>
                </form>
            @endunless
        @endif

        <div class="actions">
            @yield('extra-actions')
            <a class="btn btn-primary" href="{{ $home }}">{{ $homeLabel }}</a>
        </div>

        <div class="ref">Kode {{ $code }} · {{ now()->translatedFormat('d M Y H:i') }}</div>
    </main>
</body>
</html>
@endif
