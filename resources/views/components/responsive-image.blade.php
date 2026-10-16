@props([
    'media' => null,              // Spatie Media (koleksi featured/og artikel) atau null
    'sizes' => '100vw',           // lebar tampil gambar di tiap layar, agar browser memilih file yang pas
    'alt' => '',
    'priority' => false,          // true untuk gambar utama di layar pertama (LCP): tidak lazy, diambil lebih dulu
    'fallback' => null,           // URL bila tidak ada media
])

@php
    // srcset dari turunan WebP 16:9 yang sudah dibuat (Article::IMAGE_SIZES); file asli hanya cadangan
    $srcset = [];
    $src = $fallback ?? asset('images/placeholder-article.jpg');

    if ($media) {
        foreach (\App\Models\Article::IMAGE_SIZES as $conversion => $width) {
            if ($media->hasGeneratedConversion($conversion)) {
                $srcset[] = $media->getUrl($conversion) . " {$width}w";
            }
        }
        $src = $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : $media->getUrl();
    }
@endphp

<img src="{{ $src }}"
     @if ($srcset) srcset="{{ implode(', ', $srcset) }}" sizes="{{ $sizes }}" @endif
     alt="{{ $alt }}" width="1280" height="720"
     @if ($priority) fetchpriority="high" @else loading="lazy" @endif
     decoding="async"
     {{ $attributes }}>
