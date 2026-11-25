{{-- Kit media otomatis di halaman Pasang Iklan: angka pembaca 30 hari, lokasi & ukuran iklan, kontak --}}
@php
    $stats = \Illuminate\Support\Facades\Cache::remember('media-kit.stats.v1', 3600, function () {
        $since = now()->subDays(30)->toDateString();
        $locations = \Illuminate\Support\Facades\DB::table('article_daily_locations')->where('date', '>=', $since);

        // Hanya tayangan dari Indonesia: lalu lintas luar negeri sebagian besar berasal dari pusat data/perayap
        $indonesia = (int) (clone $locations)->where('country_code', 'ID')->sum('views');
        $centralJava = (int) (clone $locations)->where('country_code', 'ID')->where('region', 'Central Java')->sum('views');

        return [
            'views' => $indonesia,
            'central_java' => $indonesia > 0 ? round($centralJava / $indonesia * 100) : 0,
            'articles' => \App\Models\Article::where('status', 'published')->where('published_at', '>=', now()->subDays(30))->count(),
        ];
    });

    $where = [
        'header' => 'Bagian atas semua halaman, terlihat tanpa menggulir',
        'sidebar' => 'Kolom samping beranda, kategori, dan artikel (desktop); di bawah artikel pada HP',
        'inline_artikel' => 'Di sela isi berita, terbaca bersama artikel',
        'footer' => 'Bagian bawah semua halaman',
    ];

    $site = \App\Models\SiteSetting::values();
    $whatsapp = $site['whatsapp'] ?? null;
    $waNumber = $whatsapp ? preg_replace('/\D/', '', preg_replace('/^0/', '62', trim($whatsapp))) : null;
    $num = fn ($v) => number_format($v, 0, ',', '.');
@endphp

<section {{ $attributes }}>
    <h2 class="text-xl font-bold text-[#171B28]">Data pembaca</h2>
    <p class="mt-1 text-sm text-[#5B6378]">Diperbarui otomatis dari statistik situs, 30 hari terakhir.</p>

    <table class="mt-3 w-full border-collapse text-[15px]">
        <tbody>
            <tr class="border-b border-[#E4E8EF]">
                <td class="py-2 pr-4 text-[#5B6378]">Tayangan artikel dari Indonesia</td>
                <td class="py-2 text-right font-semibold text-[#171B28]">{{ $num($stats['views']) }}</td>
            </tr>
            <tr class="border-b border-[#E4E8EF]">
                <td class="py-2 pr-4 text-[#5B6378]">Pembaca dari Jawa Tengah</td>
                <td class="py-2 text-right font-semibold text-[#171B28]">{{ $stats['central_java'] }}%</td>
            </tr>
            <tr class="border-b border-[#E4E8EF]">
                <td class="py-2 pr-4 text-[#5B6378]">Artikel baru</td>
                <td class="py-2 text-right font-semibold text-[#171B28]">{{ $num($stats['articles']) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 class="mt-8 text-xl font-bold text-[#171B28]">Lokasi dan ukuran iklan</h2>
    <p class="mt-1 text-sm text-[#5B6378]">Banner diperkecil otomatis agar muat tanpa terpotong. Ukuran yang disarankan mengikuti standar IAB.</p>

    <div class="mt-3 overflow-x-auto">
        <table class="w-full min-w-[520px] border-collapse text-[14px]">
            <thead>
                <tr class="border-b-2 border-[#CDD3DF] text-left">
                    <th class="py-2 pr-3 font-semibold">Lokasi</th>
                    <th class="py-2 pr-3 font-semibold">Penempatan</th>
                    <th class="py-2 pr-3 font-semibold whitespace-nowrap">Ruang maks.</th>
                    <th class="py-2 font-semibold">Ukuran disarankan</th>
                </tr>
            </thead>
            <tbody>
                @foreach (\App\Models\Advertisement::PLACEMENTS as $key => $spec)
                    <tr class="border-b border-[#E4E8EF] align-top">
                        <td class="py-2 pr-3 font-semibold text-[#171B28] whitespace-nowrap">{{ $spec['label'] }}</td>
                        <td class="py-2 pr-3 text-[#3B4152]">{{ $where[$key] ?? '' }}</td>
                        <td class="py-2 pr-3 font-mono whitespace-nowrap">{{ $spec['max_width'] }}×{{ $spec['max_height'] }}</td>
                        <td class="py-2 font-mono text-[#3B4152]">{{ implode(', ', $spec['popular']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-[15px] text-[#3B4152]">Setiap pengiklan mendapat tautan laporan tayangan dan klik yang bisa dibuka kapan saja selama masa tayang.</p>

    <h2 class="mt-8 text-xl font-bold text-[#171B28]">Hubungi kami</h2>
    <ul class="mt-2 space-y-1 text-[15px] text-[#3B4152]">
        @if ($waNumber)
            <li>WhatsApp: <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 underline">{{ $whatsapp }}</a></li>
        @endif
        @if (! empty($site['editorial_email']))
            <li>Email: <a href="mailto:{{ $site['editorial_email'] }}" class="font-semibold text-brand-600 underline">{{ $site['editorial_email'] }}</a></li>
        @endif
    </ul>
</section>
