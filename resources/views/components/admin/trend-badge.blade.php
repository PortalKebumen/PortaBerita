@props(['change' => null, 'suffix' => 'vs periode sebelumnya'])
{{-- Perubahan vs periode sebelumnya: panah + angka + teks, bukan warna saja --}}
@if ($change === null)
    <span {{ $attributes->class('text-[11.5px] text-[#848CA3]') }}>Belum ada data pembanding</span>
@else
    <span {{ $attributes->class(['inline-block text-[11.5px] font-semibold', 'text-success' => $change >= 0, 'text-danger' => $change < 0]) }}>
        <span class="whitespace-nowrap">{{ $change >= 0 ? '▲' : '▼' }} {{ number_format(abs($change), 1, ',', '.') }}%</span>
        <span class="font-normal text-[#848CA3]">{{ $suffix }}</span>
    </span>
@endif
