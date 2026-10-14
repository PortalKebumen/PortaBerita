@php
    $siteName = \App\Models\SiteSetting::get('site_name', 'PortalKebumen.com');
    $role = $penulis->getRoleNames()->first();
    $jabatan = match ($role) {
        'Editor' => 'Redaktur',
        'Super Admin' => 'Redaksi',
        default => 'Jurnalis',
    };
    $initials = \Illuminate\Support\Str::of($penulis->name)->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $description = $penulis->bio
        ? \Illuminate\Support\Str::limit($penulis->bio, 155)
        : "Kumpulan artikel {$penulis->name}, {$jabatan} {$siteName}.";
    $pageTitle = $penulis->name . ($articles->currentPage() > 1 ? ' - Halaman ' . $articles->currentPage() : '');
    $profileUrl = $penulis->profileUrl();
@endphp

<x-layouts.public :title="$pageTitle" :description="$description" type="profile"
    :canonical="$articles->onFirstPage() ? $profileUrl : $articles->url($articles->currentPage())">

    @push('head')
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                'mainEntity' => array_filter([
                    '@type' => 'Person',
                    'name' => $penulis->name,
                    'jobTitle' => $jabatan,
                    'description' => $penulis->bio,
                    'url' => $profileUrl,
                    'worksFor' => ['@type' => 'NewsMediaOrganization', 'name' => $siteName, 'url' => url('/')],
                ]),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush

    {{-- Kepala profil --}}
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <section class="rounded-card bg-brand-900 px-5 py-7 sm:px-8 sm:py-9">
            <nav class="flex flex-wrap items-center gap-1.5 text-xs text-brand-300" aria-label="Breadcrumb">
                <a href="{{ route('public.beranda') }}" class="hover:text-white">Beranda</a>
                <span>/</span>
                <span>Penulis</span>
                <span>/</span>
                <span class="font-medium text-white">{{ $penulis->name }}</span>
            </nav>

            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-start">
                <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-accent-500 text-3xl font-extrabold text-white ring-4 ring-white/15" aria-hidden="true">
                    {{ $initials }}
                </div>
                <div class="min-w-0 flex-1">
                    <span class="inline-block rounded bg-white/10 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-brand-100">{{ $jabatan }}</span>
                    <h1 class="mt-2 text-2xl font-extrabold text-white sm:text-3xl">{{ $penulis->name }}</h1>
                    <p class="mt-1 text-sm text-brand-300">{{ $jabatan }} di {{ $siteName }}</p>
                    @if ($penulis->bio)
                        <p class="mt-4 max-w-3xl text-[15px] leading-relaxed text-white/85">{{ $penulis->bio }}</p>
                    @endif
                </div>
            </div>

            <dl class="mt-7 grid grid-cols-3 divide-x divide-white/10 rounded-card bg-white/5 text-center sm:max-w-xl">
                <div class="px-3 py-3">
                    <dt class="text-[11px] uppercase tracking-wide text-brand-300">Artikel</dt>
                    <dd class="mt-0.5 text-lg font-extrabold text-white">{{ number_format($stats->total ?? 0, 0, ',', '.') }}</dd>
                </div>
                <div class="px-3 py-3">
                    <dt class="text-[11px] uppercase tracking-wide text-brand-300">Dibaca</dt>
                    <dd class="mt-0.5 text-lg font-extrabold text-white">{{ \App\Models\Advertisement::formatMetricNumber((int) ($stats->views ?? 0)) }}</dd>
                </div>
                <div class="px-3 py-3">
                    <dt class="text-[11px] uppercase tracking-wide text-brand-300">Bergabung</dt>
                    <dd class="mt-0.5 text-lg font-extrabold text-white">{{ $penulis->created_at->translatedFormat('M Y') }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="mb-4 border-b-2 border-brand-600 pb-2 text-lg font-bold text-[#171B28]">
                    Artikel oleh {{ $penulis->name }}
                    @if ($articles->currentPage() > 1)
                        <span class="text-sm font-medium text-[#848CA3]">&middot; Halaman {{ $articles->currentPage() }}</span>
                    @endif
                </h2>

                @if ($articles->isEmpty())
                    <div class="rounded-card border border-dashed border-[#CDD3DF] bg-white px-6 py-16 text-center">
                        <p class="text-sm text-[#848CA3]">Penulis ini belum memiliki artikel yang dipublikasikan.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($articles as $item)
                            <x-public.article-card :article="$item" variant="row" />
                        @endforeach
                    </div>

                    @if ($articles->hasPages())
                        <div class="mt-8">{{ $articles->links() }}</div>
                    @endif
                @endif
            </div>

            <aside class="space-y-6">
                @if ($topCategories->isNotEmpty())
                    <section class="rounded-card border border-[#E4E8EF] bg-white p-4">
                        <h2 class="mb-3 border-b-2 border-brand-600 pb-2 text-base font-bold text-[#171B28]">Sering Menulis Tentang</h2>
                        <ul class="flex flex-wrap gap-2">
                            @foreach ($topCategories as $cat)
                                <li>
                                    <a href="{{ $cat->parent_id && $cat->parent ? route('public.kategori.sub', [$cat->parent->slug, $cat->slug]) : route('public.kategori', $cat->slug) }}"
                                       class="inline-flex items-center gap-1.5 rounded-full border border-[#E4E8EF] px-3 py-1 text-xs font-semibold text-[#3B4152] transition hover:border-brand-600 hover:text-brand-600">
                                        {{ $cat->name }}
                                        <span class="rounded-full bg-[#F1F3F7] px-1.5 text-[10px] text-[#848CA3]">{{ $cat->articles_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <x-public.ad-slot placement="sidebar" height="h-64" :placeholder="false" />

                @if ($popularArticles->isNotEmpty())
                    <section class="rounded-card border border-[#E4E8EF] bg-white p-4">
                        <h2 class="mb-3 border-b-2 border-brand-600 pb-2 text-base font-bold text-[#171B28]">Terpopuler dari {{ \Illuminate\Support\Str::of($penulis->name)->explode(' ')->first() }}</h2>
                        <ol class="space-y-3">
                            @foreach ($popularArticles as $item)
                                <li class="group flex gap-3">
                                    <span class="w-6 shrink-0 text-2xl font-extrabold leading-none text-[#CDD3DF] group-hover:text-brand-600">{{ $loop->iteration }}</span>
                                    <a href="{{ $item->publicUrl() }}" class="text-sm font-semibold leading-snug text-[#171B28] group-hover:text-brand-600">{{ $item->title }}</a>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <x-public.follow-us />
            </aside>
        </div>
    </div>
</x-layouts.public>
