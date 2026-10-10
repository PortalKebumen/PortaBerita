@php($site = \App\Models\SiteSetting::values())
@if (!empty($site['gsc_verification']))
    <meta name="google-site-verification" content="{{ $site['gsc_verification'] }}">
@endif
@if (!empty($site['ga_id']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $site['ga_id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @js($site['ga_id']));
    </script>
@endif