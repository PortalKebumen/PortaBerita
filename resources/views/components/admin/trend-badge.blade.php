@props(['change' => null, 'suffix' => 'vs periode sebelumnya'])
{{-- Perubahan vs periode sebelumnya: panah + angka + teks, bukan warna saja --}}
@if ($change === null)
    <span {{ $attributes->class('text-[11.5px] text-[#848CA3]') }}>Belum ada data pembanding</span>
@else
    <span {{ $attributes->class(['inline-flex items-center gap-1 text-[11.5px] font-semibold', 'text-success' => $change >= 0, 'text-danger' => $change < 0]) }}>
        {{ $change >= 0 ? '▲' : '▼' }} {{ number_format(abs($change), 1, ',', '.') }}%
        <span class="font-normal text-[#848CA3]">{{ $suffix }}</span>
    </span>
@endif
