@props([
    'placement' => 'sidebar',
    'label' => 'Advertisement',
    'height' => 'h-32',
    'placeholder' => true,
    'interval' => 3,
    'start' => 0,
])

@php
    // Urutan diacak per muat halaman agar setiap pengiklan mendapat giliran tampil pertama.
    // Disimpan per request supaya slot kedua di lokasi yang sama memakai urutan yang sama.
    $cacheKey = "ad-slot.{$placement}";
    $ads = request()->attributes->get($cacheKey);
    if ($ads === null) {
        $ads = \App\Models\Advertisement::activeRunning($placement)->with('media')->get()->shuffle()->values();
        request()->attributes->set($cacheKey, $ads);
    }

    // start > 0: slot tambahan (mis. iklan sticky sidebar) mulai dari iklan berikutnya agar tidak kembar
    // dengan slot pertama; tidak tampil bila iklannya tidak cukup.
    $start = (int) $start;
    if ($start > 0) {
        $ads = $ads->count() > $start ? $ads->slice($start)->concat($ads->take($start))->values() : collect();
    }

    // Ruang maksimal per lokasi (lihat Advertisement::PLACEMENTS). Lebar dibatasi kolom tempat slot berada,
    // tinggi dibatasi di sini. Banner ukuran apa pun tampil utuh: diperkecil bila lebih besar, tidak diperbesar.
    // Kelas ditulis lengkap agar terbaca Tailwind; $fallbackRatio hanya untuk iklan tanpa gambar.
    [$maxHeight, $fallbackRatio, $sizes] = match ($placement) {
        'sidebar' => ['max-h-[600px]', 'aspect-[300/250]', '(min-width: 1024px) 384px, 100vw'],
        'header', 'footer' => ['max-h-[250px]', 'aspect-[970/250]', '(min-width: 1280px) 1216px, 100vw'],
        default => ['max-h-[250px]', 'aspect-[728/90]', '(min-width: 900px) 800px, 100vw'],
    };

    // Unit AdSense manual hanya untuk lokasi Inline Artikel & Sidebar, dan hanya bila tidak ada iklan langsung
    $adsenseSlot = null;
    $adsenseClient = null;
    if ($ads->isEmpty() && (int) $start === 0) {
        $siteValues = \App\Models\SiteSetting::values();
        $adsenseClient = $siteValues['adsense_publisher_id'] ?? null;
        $adsenseSlot = $adsenseClient ? match ($placement) {
            'inline_artikel' => $siteValues['adsense_slot_inline'] ?? null,
            'sidebar' => $siteValues['adsense_slot_sidebar'] ?? null,
            default => null,
        } : null;
    }
@endphp

@if ($ads->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'mx-auto w-full']) }}
         data-ad-slot data-ad-interval="{{ max(2, (int) $interval) * 1000 }}"
         role="complementary" aria-label="Iklan" @if ($ads->count() > 1) aria-roledescription="carousel" @endif>
        {{-- Slide ditumpuk di sel grid yang sama; tinggi ruang mengikuti banner tertinggi, banner lain di tengah --}}
        <div class="grid">
            @foreach ($ads as $i => $ad)
                <div class="group flex items-center justify-center [grid-area:1/1] transition-opacity duration-700 motion-reduce:transition-none {{ $i === 0 ? 'opacity-100' : 'opacity-0' }}"
                     data-ad-slide data-ad-impression="{{ route('ads.impression', $ad) }}"
                     @if ($i > 0) inert aria-hidden="true" @endif>
                    <a href="{{ route('ads.click', $ad) }}" target="_blank" rel="sponsored noopener noreferrer"
                       class="relative block max-w-full overflow-hidden rounded-card {{ $ad->banner_url ? '' : 'w-full' }}">
                        @if ($ad->banner_url)
                            @php
                                $banner = $ad->bannerImage();
                                // Banner header pertama terlihat tanpa scroll: dimuat segera agar LCP cepat
                                $eager = $i === 0 && $placement === 'header' && (int) $start === 0;
                            @endphp
                            <img src="{{ $banner['src'] }}" alt="Iklan: {{ $ad->advertiser_name }}"
                                 @if ($banner['srcset']) srcset="{{ $banner['srcset'] }}" sizes="{{ $sizes }}" @endif
                                 @if ($banner['width']) width="{{ $banner['width'] }}" height="{{ $banner['height'] }}" @endif
                                 @if ($eager) fetchpriority="high" @else loading="lazy" @endif decoding="async"
                                 class="block h-auto w-auto max-w-full {{ $maxHeight }} transition-transform duration-300 group-hover:scale-[1.01]">
                        @else
                            <div class="flex {{ $fallbackRatio }} w-full flex-col items-center justify-center bg-gradient-to-r from-brand-700 to-brand-500 p-4 text-center text-white">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-white/70">Sponsor / Iklan</span>
                                <span class="mt-1 text-sm font-bold">{{ $ad->advertiser_name }}</span>
                                <span class="mt-2 inline-block rounded bg-white/20 px-2.5 py-1 text-[11px] font-medium">Kunjungi Situs &rarr;</span>
                            </div>
                        @endif
                        <span class="pointer-events-none absolute right-1.5 top-1.5 rounded bg-black/45 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-white/90">Iklan</span>
                    </a>
                </div>
            @endforeach
        </div>

        @if ($ads->count() > 1)
            <div class="mt-1 flex items-center justify-center" data-ad-dots>
                @foreach ($ads as $i => $ad)
                    <button type="button" data-ad-dot="{{ $i }}" aria-label="Tampilkan iklan {{ $i + 1 }} dari {{ $ads->count() }}"
                            @if ($i === 0) aria-current="true" @endif
                            class="group/dot flex h-6 min-w-6 items-center justify-center px-1">
                        <span class="block h-1.5 rounded-full transition-all duration-300 {{ $i === 0 ? 'w-4 bg-brand-600' : 'w-1.5 bg-[#CDD3DF] group-hover/dot:bg-[#9AA3B8]' }}"></span>
                    </button>
                @endforeach
            </div>
        @endif
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
                    var dots = Array.prototype.slice.call(slot.querySelectorAll('[data-ad-dot]'));
                    var interval = parseInt(slot.dataset.adInterval, 10) || 3000;
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
                        dots.forEach(function (dot, i) {
                            var bar = dot.firstElementChild;
                            var active = i === current;
                            bar.classList.toggle('w-4', active);
                            bar.classList.toggle('bg-brand-600', active);
                            bar.classList.toggle('w-1.5', !active);
                            bar.classList.toggle('bg-[#CDD3DF]', !active);
                            if (active) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current');
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

                    // Geser manual: titik penanda dan swipe di layar sentuh; timer otomatis dimulai ulang
                    var go = function (index) { show(index); schedule(); };
                    dots.forEach(function (dot) {
                        dot.addEventListener('click', function () { go(parseInt(dot.dataset.adDot, 10)); });
                    });
                    var touchX = null, touchY = null;
                    slot.addEventListener('touchstart', function (e) {
                        touchX = e.touches[0].clientX; touchY = e.touches[0].clientY;
                    }, { passive: true });
                    slot.addEventListener('touchend', function (e) {
                        if (touchX === null || slides.length < 2) return;
                        var dx = e.changedTouches[0].clientX - touchX, dy = e.changedTouches[0].clientY - touchY;
                        touchX = null;
                        // Geser horizontal yang jelas saja, agar tidak bentrok dengan scroll halaman
                        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.5) {
                            slot.dataset.adSwiped = '1';
                            go(current + (dx < 0 ? 1 : -1));
                        }
                    });
                    // Swipe tidak boleh ikut dihitung sebagai klik iklan
                    slot.addEventListener('click', function (e) {
                        if (slot.dataset.adSwiped) {
                            delete slot.dataset.adSwiped;
                            if (e.target.closest('a')) e.preventDefault();
                        }
                    }, true);

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
@elseif ($adsenseSlot)
    {{-- Lokasi kosong dari iklan langsung: diisi unit iklan AdSense (Pengaturan > SEO) --}}
    <div {{ $attributes->merge(['class' => 'mx-auto w-full']) }} role="complementary" aria-label="Iklan">
        <ins class="adsbygoogle" style="display:block" data-ad-client="{{ $adsenseClient }}" data-ad-slot="{{ $adsenseSlot }}"
             data-ad-format="auto" data-full-width-responsive="true"></ins>
        <script>(window.adsbygoogle = window.adsbygoogle || []).push({});</script>
    </div>
@elseif ($placeholder)
    <div {{ $attributes->merge(['class' => "flex $height w-full items-center justify-center rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] text-xs font-semibold uppercase tracking-wide text-[#6C7387]"]) }}>
        {{ $label }}
    </div>
@endif
