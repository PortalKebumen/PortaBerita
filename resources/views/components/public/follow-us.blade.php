@php
    // Hanya akun yang diisi di Pengaturan > Sosial Media
    $socials = \App\Models\SiteSetting::socialLinks();
    $siteName = \App\Models\SiteSetting::get('site_name', 'PortalKebumen.com');
@endphp

@if (count($socials))
    <section {{ $attributes->merge(['class' => 'rounded-card border border-[#E4E8EF] bg-white p-4']) }}>
        <h2 class="mb-1 border-b-2 border-brand-600 pb-2 text-base font-bold text-[#171B28]">Ikuti Kami</h2>
        <p class="mt-2 text-xs text-[#5B6378]">Dapatkan kabar terbaru dari {{ $siteName }} di media sosial.</p>
        <div class="mt-3 grid grid-cols-1 gap-2">
            @foreach ($socials as $social)
                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                   class="group flex items-center gap-3 rounded-lg border border-[#E4E8EF] px-3 py-2 transition hover:border-transparent hover:shadow-sm">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white" style="background-color: {{ $social['color'] }}">
                        <x-public.social-icon :name="$social['key']" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-[#171B28] group-hover:text-brand-600">{{ $social['label'] }}</span>
                        <span class="block truncate text-[11px] text-[#848CA3]">{{ $social['chat'] ? 'Chat redaksi' : preg_replace('#^https?://(www\.)?#', '', rtrim($social['url'], '/')) }}</span>
                    </span>
                    <span class="shrink-0 text-[11px] font-semibold text-brand-600">{{ $social['chat'] ? 'Chat' : 'Ikuti' }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
