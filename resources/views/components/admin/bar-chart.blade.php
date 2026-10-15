@props([
    'data' => [],          // 'Y-m-d' (awal hari/pekan/bulan) => angka
    'unit' => '',          // satuan di tooltip, mis. "tayangan"
    'granularity' => 'day', // day | week | month (lihat AnalyticsReport::granularity())
    'height' => 'h-44',
    'bar' => 'bg-brand-500',
])

@php
    // Satu seri, satu warna: tinggi batang = besaran. Skala dibulatkan ke angka "rapi" agar garis bantu mudah dibaca.
    $values = array_values($data);
    $max = max(1, ...($values ?: [0]));
    $magnitude = 10 ** max(0, strlen((string) (int) $max) - 1);
    $niceMax = (int) (ceil($max / $magnitude) * $magnitude);
    $count = count($data);
    $labelEvery = max(1, (int) ceil($count / 6));
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $per = ['day' => 'hari', 'week' => 'pekan', 'month' => 'bulan'][$granularity] ?? 'hari';
    $tooltipDate = fn ($date) => match ($granularity) {
        'week' => 'Pekan mulai ' . $date->translatedFormat('d M Y'),
        'month' => $date->translatedFormat('F Y'),
        default => $date->translatedFormat('D, d M Y'),
    };
    $axisDate = fn ($date) => $date->translatedFormat($granularity === 'month' ? 'M Y' : 'd M');
@endphp

<div {{ $attributes->class('w-full') }}>
    <div class="flex gap-2">
        {{-- Sumbu Y (hanya puncak & tengah, warna redup) --}}
        <div class="flex {{ $height }} w-10 shrink-0 flex-col justify-between text-right font-mono text-[10.5px] text-[#848CA3]">
            <span class="-translate-y-1/2">{{ $fmt($niceMax) }}</span>
            <span class="-translate-y-1/2">{{ $fmt($niceMax / 2) }}</span>
            <span class="translate-y-1/2">0</span>
        </div>

        <div class="relative min-w-0 flex-1">
            {{-- Garis bantu --}}
            <div class="pointer-events-none absolute inset-0 {{ $height }} flex flex-col justify-between">
                <div class="border-t border-dashed border-[#E4E8EF]"></div>
                <div class="border-t border-dashed border-[#E4E8EF]"></div>
                <div class="border-t border-[#CDD3DF]"></div>
            </div>

            {{-- Batang: kolom penuh jadi area hover, batangnya menempel di garis dasar --}}
            <div class="relative flex {{ $height }} items-end gap-[2px]" role="img"
                 aria-label="Grafik batang {{ $unit }} per {{ $per }}, {{ $count }} {{ $per }}, tertinggi {{ $fmt($max) }}">
                @foreach ($data as $day => $value)
                    @php $date = \Carbon\Carbon::parse($day); @endphp
                    <div class="group relative flex h-full min-w-0 flex-1 items-end">
                        <div class="w-full rounded-t-[4px] {{ $bar }} transition-opacity group-hover:opacity-80"
                             style="height: {{ $value > 0 ? max(2, round($value / $niceMax * 100, 2)) : 0 }}%"></div>
                        {{-- Tooltip di tepi kiri/kanan ditambatkan ke sisi itu agar tidak keluar layar (HP) --}}
                        <div @class(['pointer-events-none absolute bottom-full z-10 mb-1.5 whitespace-nowrap rounded-md bg-[#171B28] px-2 py-1 text-[11px] text-white opacity-0 shadow transition-opacity group-hover:opacity-100',
                                     'left-0' => $loop->index < $count / 3,
                                     'right-0' => $loop->index >= $count * 2 / 3,
                                     'left-1/2 -translate-x-1/2' => $loop->index >= $count / 3 && $loop->index < $count * 2 / 3])>
                            <div class="text-white/70">{{ $tooltipDate($date) }}</div>
                            <div class="font-semibold">{{ $fmt($value) }} {{ $unit }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Label tanggal (sebagian, agar tidak bertumpuk) --}}
            <div class="mt-1.5 flex gap-[2px] text-[10.5px] text-[#848CA3]">
                @foreach (array_keys($data) as $i => $day)
                    @php
                        // Label terakhir hanya bila cukup jauh dari label berkala sebelumnya; label ganjil disembunyikan di HP
                        $isLast = $i === $count - 1 && ($count - 1) % $labelEvery >= max(1, intdiv($labelEvery, 2) + 1);
                        $show = $i % $labelEvery === 0 || $isLast;
                        $mobileHidden = ! $isLast && intdiv($i, $labelEvery) % 2 === 1;
                    @endphp
                    <div @class(['min-w-0 flex-1 overflow-visible whitespace-nowrap',
                                 'text-left' => $i === 0, 'text-right' => $i === $count - 1 && $count > 1, 'text-center' => $i !== 0 && $i !== $count - 1])>
                        @if ($show)
                            <span @class(['max-sm:invisible' => $mobileHidden])>{{ $axisDate(\Carbon\Carbon::parse($day)) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
