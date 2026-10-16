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
        // Lebar foto asli disimpan sekali di custom property; turunan yang lebih lebar dari aslinya
        // (hasil pembesaran) tidak ditawarkan karena lebih berat tanpa tambahan detail
        $originalWidth = $media->getCustomProperty('width');
        if ($originalWidth === null && is_file($path = $media->getPath())) {
            $originalWidth = (int) (@getimagesize($path)[0] ?? 0);
            $media->setCustomProperty('width', $originalWidth)->saveQuietly();
        }

        foreach (\App\Models\Article::IMAGE_SIZES as $conversion => $width) {
            $fits = ! $originalWidth || $width <= $originalWidth || $srcset === [];
            if ($fits && $media->hasGeneratedConversion($conversion)) {
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
