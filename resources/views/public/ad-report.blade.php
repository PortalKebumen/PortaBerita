@php
    $siteName = \App\Models\SiteSetting::get('site_name', 'PortalKebumen.com');
    $num = fn ($v) => number_format($v, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Laporan Iklan {{ $ad->advertiser_name }} – {{ $siteName }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } body { background: #fff !important; } }</style>
</head>
<body class="bg-[#F7F8FA] text-[#171B28]">
    <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <header class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-[#171B28] pb-4">
            <div>
                <p class="text-sm text-[#5B6378]">{{ $siteName }} &middot; Laporan Iklan</p>
                <h1 class="mt-1 text-2xl font-bold">{{ $ad->advertiser_name }}</h1>
            </div>
            <button type="button" onclick="window.print()" class="no-print rounded border border-[#CDD3DF] bg-white px-3 py-1.5 text-sm font-semibold hover:border-[#171B28]">Cetak / Simpan PDF</button>
        </header>

        <table class="mt-4 text-[15px]">
            <tbody>
                <tr><td class="py-0.5 pr-6 text-[#5B6378]">Lokasi</td><td class="font-semibold">{{ $placement }}</td></tr>
                <tr><td class="py-0.5 pr-6 text-[#5B6378]">Masa tayang</td><td class="font-semibold">{{ $ad->start_date->translatedFormat('d F Y') }} – {{ $ad->end_date->translatedFormat('d F Y') }}</td></tr>
                <tr><td class="py-0.5 pr-6 text-[#5B6378]">Data per</td><td class="font-semibold">{{ now()->translatedFormat('d F Y, H:i') }} WIB</td></tr>
            </tbody>
        </table>

        <table class="mt-6 w-full border-collapse text-[15px]">
            <thead>
                <tr class="border-y-2 border-[#171B28] text-left">
                    <th class="py-2 font-semibold">Impresi</th>
                    <th class="py-2 font-semibold">Klik</th>
                    <th class="py-2 font-semibold">CTR</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-[#CDD3DF] text-xl font-bold">
                    <td class="py-3">{{ $num($impressions) }}</td>
                    <td class="py-3">{{ $num($clicks) }}</td>
                    <td class="py-3">{{ number_format($ctr, 2, ',', '.') }}%</td>
                </tr>
            </tbody>
        </table>

        <h2 class="mt-8 text-lg font-bold">Rincian harian</h2>
        @if ($daily->isEmpty())
            <p class="mt-2 text-sm text-[#5B6378]">Iklan belum mulai tayang.</p>
        @else
            <table class="mt-2 w-full border-collapse text-[14px]">
                <thead>
                    <tr class="border-b-2 border-[#CDD3DF] text-left">
                        <th class="py-1.5 font-semibold">Tanggal</th>
                        <th class="py-1.5 text-right font-semibold">Impresi</th>
                        <th class="py-1.5 text-right font-semibold">Klik</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daily as $row)
                        <tr class="border-b border-[#E4E8EF]">
                            <td class="py-1.5">{{ $row['date']->translatedFormat('D, d M Y') }}</td>
                            <td class="py-1.5 text-right font-mono">{{ $num($row['impressions']) }}</td>
                            <td class="py-1.5 text-right font-mono">{{ $num($row['clicks']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="mt-6 text-[13px] leading-relaxed text-[#5B6378]">
            Impresi dihitung saat banner benar-benar terlihat di layar pengunjung. Klik dihitung sekali per pengunjung per hari,
            dan hanya bila pengunjung tersebut telah melihat iklannya. Kunjungan dari mesin pencari, bot, dan jaringan pusat data
            tidak dihitung.
        </p>
    </main>
</body>
</html>
