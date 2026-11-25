{{-- Ajakan mengikuti di akhir artikel: WhatsApp Channel & Google News (tampil bila diisi di Pengaturan > Media Sosial) --}}
@php
    $settings = \App\Models\SiteSetting::values();
    $channel = $settings['whatsapp_channel'] ?? null;
    $googleNews = $settings['google_news_url'] ?? null;
    $siteName = $settings['site_name'] ?: 'PortalKebumen.com';
@endphp

@if ($channel || $googleNews)
    <p {{ $attributes->merge(['class' => 'text-[15px] text-[#3B4152]']) }}>
        Ikuti berita terkini {{ $siteName }} di
        @if ($channel)
            <a href="{{ $channel }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-600 underline hover:text-brand-700">WhatsApp Channel</a>
        @endif
        @if ($channel && $googleNews) dan @endif
        @if ($googleNews)
            <a href="{{ $googleNews }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-600 underline hover:text-brand-700">Google News</a>
        @endif
    </p>
@endif
