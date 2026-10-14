@props([
    'placement' => 'sidebar',
    'label' => 'Advertisement',
    'height' => 'h-32',
    'placeholder' => true,
    'interval' => 4,
])

@php
    // Urutan diacak per muat halaman agar setiap pengiklan mendapat giliran tampil pertama.
    $ads = \App\Models\Advertisement::activeRunning($placement)->with('media')->get()->shuffle()->values();

    // Rasio mengikuti ukuran banner standar tiap lokasi (header 970x250, sidebar 300x250, inline/footer 728x90).
    [$ratio, $maxWidth] = match ($placement) {
        'header' => ['aspect-[970/250]', 'max-w-[970px]'],
        'sidebar' => ['aspect-[300/250]', 'max-w-full'],
        default => ['aspect-[728/90]', 'max-w-[728px]'],
    };
@endphp

@if ($ads->isNotEmpty())
    <div {{ $attributes->merge(['class' => "relative mx-auto w-full $maxWidth"]) }}
         data-ad-slot data-ad-interval="{{ max(2, (int) $interval) * 1000 }}"
         role="complementary" aria-label="Iklan">
        <div class="grid overflow-hidden rounded-card bg-[#E4E8EF]">
            @foreach ($ads as $i => $ad)
                <div class="group [grid-area:1/1] transition-opacity duration-700 motion-reduce:transition-none {{ $i === 0 ? 'opacity-100' : 'opacity-0' }}"
                     data-ad-slide data-ad-impression="{{ route('ads.impression', $ad) }}"
                     @if ($i > 0) inert aria-hidden="true" @endif>
                    <a href="{{ route('ads.click', $ad) }}" target="_blank" rel="sponsored noopener noreferrer" class="block {{ $ratio }} w-full">
                        @if ($ad->banner_url)
                            <img src="{{ $ad->banner_url }}" alt="Iklan: {{ $ad->advertiser_name }}" loading="lazy"
                                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.01]">
                        @else
                            <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-r from-brand-700 to-brand-500 p-4 text-center text-white">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-white/70">Sponsor / Iklan</span>
                                <span class="mt-1 text-sm font-bold">{{ $ad->advertiser_name }}</span>
                                <span class="mt-2 inline-block rounded bg-white/20 px-2.5 py-1 text-[11px] font-medium">Kunjungi Situs &rarr;</span>
                            </div>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>

        <span class="pointer-events-none absolute right-1.5 top-1.5 rounded bg-black/45 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-white/90">Iklan</span>
    </div>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var token = '{{ csrf_token() }}';

                // Impresi dicatat sekali per iklan per muat halaman, hanya saat iklan benar-benar tampil di layar.
                var send = function (slide) {
                    var url = slide && slide.getAttribute('data-ad-impression');
                    if (!url || slide.dataset.adSent) return;
                    slide.dataset.adSent = '1';
                    fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        keepalive: true
                    }).catch(function () {});
                };

                document.querySelectorAll('[data-ad-slot]').forEach(function (slot) {
                    var slides = Array.prototype.slice.call(slot.querySelectorAll('[data-ad-slide]'));
                    var interval = parseInt(slot.dataset.adInterval, 10) || 4000;
                    var current = 0, visible = false, paused = false, timer = null;

                    var show = function (index) {
                        current = (index + slides.length) % slides.length;
                        slides.forEach(function (slide, i) {
                            var active = i === current;
                            slide.classList.toggle('opacity-100', active);
                            slide.classList.toggle('opacity-0', !active);
                            slide.inert = !active;
                            if (active) slide.removeAttribute('aria-hidden'); else slide.setAttribute('aria-hidden', 'true');
                        });
                        if (visible) send(slides[current]);
                    };

                    // Rotasi hanya berjalan saat slot terlihat, tidak disorot, dan tab sedang aktif.
                    var schedule = function () {
                        clearInterval(timer);
                        timer = null;
                        if (slides.length > 1 && visible && !paused && !document.hidden) {
                            timer = setInterval(function () { show(current + 1); }, interval);
                        }
                    };

                    slot.addEventListener('mouseenter', function () { paused = true; schedule(); });
                    slot.addEventListener('mouseleave', function () { paused = false; schedule(); });
                    slot.addEventListener('focusin', function () { paused = true; schedule(); });
                    slot.addEventListener('focusout', function () { paused = false; schedule(); });
                    document.addEventListener('visibilitychange', schedule);

                    if (!('IntersectionObserver' in window)) {
                        visible = true;
                        send(slides[current]);
                        schedule();
                        return;
                    }

                    new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            visible = entry.isIntersecting;
                            if (visible) send(slides[current]);
                            schedule();
                        });
                    }, { threshold: 0.5 }).observe(slot);
                });
            });
        </script>
    @endonce
@elseif ($placeholder)
    <div {{ $attributes->merge(['class' => "flex $height w-full items-center justify-center rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] text-xs font-semibold uppercase tracking-wide text-[#848CA3]"]) }}>
        {{ $label }}
    </div>
@endif
