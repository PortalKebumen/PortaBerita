@props([
    'url',
    'title',
])

@php
    $u = urlencode($url);
    $t = urlencode($title);

    $platforms = [
        ['key' => 'whatsapp', 'label' => 'WhatsApp', 'color' => '#25D366', 'href' => "https://wa.me/?text={$t}%20{$u}"],
        ['key' => 'facebook', 'label' => 'Facebook', 'color' => '#1877F2', 'href' => "https://www.facebook.com/sharer/sharer.php?u={$u}"],
        ['key' => 'x', 'label' => 'X', 'color' => '#000000', 'href' => "https://twitter.com/intent/tweet?text={$t}&url={$u}"],
        ['key' => 'telegram', 'label' => 'Telegram', 'color' => '#26A5E4', 'href' => "https://t.me/share/url?url={$u}&text={$t}"],
        ['key' => 'linkedin', 'label' => 'LinkedIn', 'color' => '#0A66C2', 'href' => "https://www.linkedin.com/sharing/share-offsite/?url={$u}"],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }} data-share data-share-url="{{ $url }}" data-share-title="{{ $title }}">
    <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-[#848CA3]">Bagikan</span>
    @foreach ($platforms as $p)
        <a href="{{ $p['href'] }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan ke {{ $p['label'] }}" title="{{ $p['label'] }}"
           class="flex h-8 w-8 items-center justify-center rounded-full text-white transition hover:-translate-y-0.5 hover:brightness-95"
           style="background-color: {{ $p['color'] }}">
            <x-public.social-icon :name="$p['key']" class="h-3.5 w-3.5" />
        </a>
    @endforeach
    <button type="button" data-share-copy aria-label="Salin tautan" title="Salin tautan"
            class="relative flex h-8 w-8 items-center justify-center rounded-full border border-[#CDD3DF] bg-white text-[#3B4152] transition hover:bg-[#F1F3F7]">
        <x-public.social-icon name="link" class="h-3.5 w-3.5" />
        <span class="sr-only">Salin tautan</span>
    </button>
    <button type="button" data-share-native hidden aria-label="Bagikan lewat aplikasi lain" title="Lainnya"
            class="flex h-8 w-8 items-center justify-center rounded-full border border-[#CDD3DF] bg-white text-[#3B4152] transition hover:bg-[#F1F3F7]">
        <x-public.social-icon name="share" class="h-3.5 w-3.5" />
    </button>
</div>

@once
    <div id="share-toast" role="status" aria-live="polite"
         class="pointer-events-none fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-full bg-[#171B28] px-4 py-2 text-sm font-medium text-white opacity-0 shadow-lg transition-opacity duration-200"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toast = document.getElementById('share-toast');
            var toastTimer = null;
            var showToast = function (text) {
                toast.textContent = text;
                toast.classList.remove('opacity-0');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () { toast.classList.add('opacity-0'); }, 2000);
            };

            var copyText = function (text) {
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text);
                }
                // Cadangan untuk http biasa / browser lama
                return new Promise(function (resolve, reject) {
                    var field = document.createElement('textarea');
                    field.value = text;
                    field.setAttribute('readonly', '');
                    field.style.position = 'fixed';
                    field.style.opacity = '0';
                    document.body.appendChild(field);
                    field.select();
                    var ok = document.execCommand('copy');
                    document.body.removeChild(field);
                    ok ? resolve() : reject();
                });
            };

            document.querySelectorAll('[data-share]').forEach(function (box) {
                var url = box.dataset.shareUrl;
                var title = box.dataset.shareTitle;

                box.querySelectorAll('[data-share-copy]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        copyText(url).then(function () {
                            showToast('Tautan artikel disalin');
                        }).catch(function () {
                            showToast('Gagal menyalin tautan');
                        });
                    });
                });

                // Menu bagikan bawaan perangkat (umumnya di HP), hanya tampil jika didukung
                var native = box.querySelector('[data-share-native]');
                if (native && navigator.share) {
                    native.hidden = false;
                    native.addEventListener('click', function () {
                        navigator.share({ title: title, url: url }).catch(function () {});
                    });
                }
            });
        });
    </script>
@endonce
