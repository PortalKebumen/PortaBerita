@props([
    'placement' => 'sidebar',
    'label' => 'Advertisement',
    'height' => 'h-32',
])

@php
    $ad = \App\Models\Advertisement::activeRunning($placement)->inRandomOrder()->first();
@endphp

@if ($ad)
    <div {{ $attributes->merge(['class' => 'relative w-full overflow-hidden rounded-card group']) }}
         x-data
         x-init="
            fetch('{{ route('ads.impression', $ad) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).catch(() => {})
         ">
        <a href="{{ route('ads.click', $ad) }}" target="_blank" rel="noopener noreferrer" class="block w-full h-full">
            @if ($ad->banner_url)
                <img src="{{ $ad->banner_url }}" alt="{{ $ad->advertiser_name }}" class="w-full object-cover rounded-card {{ $height }} transition-transform duration-200 group-hover:scale-[1.01]">
            @else
                <div class="flex {{ $height }} w-full flex-col items-center justify-center rounded-card bg-gradient-to-r from-blue-700 to-indigo-800 p-4 text-center text-white shadow-sm transition group-hover:brightness-105">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-200">Sponsor / Iklan</span>
                    <h4 class="mt-1 text-sm font-bold">{{ $ad->advertiser_name }}</h4>
                    <span class="mt-2 inline-block rounded bg-white/20 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur-sm">Kunjungi Situs &rarr;</span>
                </div>
            @endif
        </a>
        <div class="absolute bottom-1 right-2 text-[9px] font-semibold text-gray-400/80 bg-black/40 px-1.5 py-0.5 rounded backdrop-blur-[2px] pointer-events-none">
            Iklan
        </div>
    </div>
@else
    <div {{ $attributes->merge(['class' => "flex $height w-full items-center justify-center rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] text-xs font-semibold uppercase tracking-wide text-[#848CA3]"]) }}>
        {{ $label }}
    </div>
@endif