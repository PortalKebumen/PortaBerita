@php($site = \App\Models\SiteSetting::values())
@if (!empty($site['gsc_verification']))
    <meta name="google-site-verification" content="{{ $site['gsc_verification'] }}">
@endif
@if (!empty($site['adsense_publisher_id']))
    <meta name="google-adsense-account" content="{{ $site['adsense_publisher_id'] }}">
    @if (!empty($site['adsense_auto_ads']))
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $site['adsense_publisher_id'] }}" crossorigin="anonymous"></script>
    @endif
@endif
@if (!empty($site['ga_id']))
    {{-- GA dimuat tertunda (interaksi pertama atau 4 detik setelah halaman selesai) agar tidak
         membebani tampilan awal; antrean dataLayer tetap mencatat kunjungan sejak awal. --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @js($site['ga_id']));
        (function () {
            var loaded = false, events = ['pointerdown', 'keydown', 'scroll', 'touchstart'];
            function loadGa() {
                if (loaded) return; loaded = true;
                events.forEach(function (e) { window.removeEventListener(e, loadGa); });
                var s = document.createElement('script');
                s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + @js($site['ga_id']);
                document.head.appendChild(s);
            }
            events.forEach(function (e) { window.addEventListener(e, loadGa, { once: true, passive: true }); });
            window.addEventListener('load', function () { setTimeout(loadGa, 4000); });
        })();
    </script>
@endif