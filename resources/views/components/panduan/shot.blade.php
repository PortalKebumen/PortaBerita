{{--
    Tangkapan layar di halaman Panduan. Berkas ada di public/images/panduan/{src}.webp.
    Klik gambar membuka ukuran penuh di tab baru.
--}}
@props(['src', 'caption' => null, 'narrow' => false])

@php
    $path = "images/panduan/{$src}.webp";
    [$w, $h] = @getimagesize(public_path($path)) ?: [null, null];
@endphp

<figure class="my-5 {{ $narrow ? 'max-w-[300px]' : '' }}">
    <a href="{{ asset($path) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-[#E4E8EF] bg-[#F7F8FA] shadow-sm hover:shadow-md transition-shadow" title="Buka ukuran penuh">
        <img src="{{ asset($path) }}" alt="{{ $caption ?? 'Tangkapan layar' }}" loading="lazy" decoding="async"
             @if ($w) width="{{ $w }}" height="{{ $h }}" @endif class="block h-auto w-full">
    </a>
    @if ($caption)
        <figcaption class="mt-2 text-[12.5px] text-[#6C7387]">{{ $caption }}</figcaption>
    @endif
</figure>
