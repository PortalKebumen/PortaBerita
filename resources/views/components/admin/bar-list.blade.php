@props([
    // Tiap baris: ['label' => ..., 'value' => angka, 'display' => teks nilai (opsional), 'sub' => teks kecil (opsional), 'href' => (opsional)]
    'rows' => [],
    'bar' => 'bg-brand-100',
    'empty' => 'Belum ada data pada periode ini.',
])

@php $max = max(1, ...(collect($rows)->pluck('value')->all() ?: [0])); @endphp

@if (empty($rows))
    <p class="py-8 text-center text-[13px] text-[#848CA3]">{{ $empty }}</p>
@else
    {{-- Batang di belakang teks: panjang = besaran relatif terhadap baris teratas --}}
    <ul {{ $attributes->class('space-y-1.5') }}>
        @foreach ($rows as $row)
            <li class="relative overflow-hidden rounded-md">
                <div class="absolute inset-y-0 left-0 rounded-md {{ $bar }}" style="width: {{ round(($row['value'] / $max) * 100, 2) }}%"></div>
                <div class="relative flex items-center justify-between gap-3 px-2.5 py-1.5">
                    <div class="min-w-0">
                        @if (! empty($row['href']))
                            <a href="{{ $row['href'] }}" class="block truncate text-[13px] font-semibold text-[#171B28] hover:text-brand-600">{{ $row['label'] }}</a>
                        @else
                            <span class="block truncate text-[13px] font-semibold text-[#171B28]">{{ $row['label'] }}</span>
                        @endif
                        @if (! empty($row['sub']))
                            <span class="block truncate text-[11px] text-[#6C7387]">{{ $row['sub'] }}</span>
                        @endif
                    </div>
                    <span class="shrink-0 font-mono text-[12.5px] font-semibold text-[#171B28]">{{ $row['display'] ?? number_format($row['value'], 0, ',', '.') }}</span>
                </div>
            </li>
        @endforeach
    </ul>
@endif
