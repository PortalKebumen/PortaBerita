@php
    $r = $this->report;
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $pct = fn ($n, $d = 1) => number_format($n, $d, ',', '.') . '%';
    $card = 'bg-white border border-[#E4E8EF] rounded-2xl shadow-sm';
    $th = 'text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#E4E8EF] whitespace-nowrap';
    $thNum = 'text-right text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#E4E8EF] whitespace-nowrap';
@endphp

<div class="space-y-6">
    {{-- Filter periode + ekspor (satu baris di atas semua grafik) --}}
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex flex-wrap rounded-lg border border-[#E4E8EF] bg-white p-1" role="group" aria-label="Pilih periode">
                @foreach (\App\Services\Analytics\AnalyticsReport::PRESETS as $value => $label)
                    <button type="button" wire:click="setPreset('{{ $value }}')"
                            @class(['rounded-md px-3 py-1.5 text-[12.5px] font-semibold transition-colors',
                                    'bg-brand-600 text-white' => $preset === (string) $value,
                                    'text-[#3B4152] hover:bg-[#F1F3F7]' => $preset !== (string) $value])
                            aria-pressed="{{ $preset === (string) $value ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>

            {{-- Rentang bebas; dibuat ulang tiap periode berubah agar selalu menampilkan rentang yang aktif --}}
            <div class="relative w-full sm:w-64" wire:ignore wire:key="range-{{ $r->from->toDateString() }}-{{ $r->to->toDateString() }}" x-data="{
                    init() {
                        flatpickr(this.$refs.range, {
                            mode: 'range',
                            dateFormat: 'Y-m-d',
                            altInput: true,
                            altFormat: 'd M Y',
                            maxDate: 'today',
                            defaultDate: [@js($r->from->toDateString()), @js($r->to->toDateString())],
                            onClose: (dates, str, instance) => {
                                if (dates.length === 2) {
                                    $wire.setRange(instance.formatDate(dates[0], 'Y-m-d'), instance.formatDate(dates[1], 'Y-m-d'));
                                }
                            },
                        });
                    }
                }">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="pointer-events-none absolute left-3.5 top-1/2 z-10 -translate-y-1/2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <input type="text" x-ref="range" @class(['form-input pl-9', 'border-brand-500 ring-1 ring-brand-500' => $preset === 'kustom']) placeholder="Pilih rentang tanggal" aria-label="Rentang tanggal kustom" readonly>
            </div>
        </div>

        {{-- Ekspor tab yang sedang dibuka dengan periode yang sama --}}
        @can('analytics.export')
        <details class="relative self-start xl:self-auto">
            <summary class="btn-secondary flex cursor-pointer list-none items-center gap-2 [&::-webkit-details-marker]:hidden">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v12M12 16l-4-4M12 16l4-4"/><path d="M4 18v2h16v-2"/></svg>
                Ekspor
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
            </summary>
            <div class="absolute right-0 z-20 mt-1.5 w-60 overflow-hidden rounded-xl border border-[#E4E8EF] bg-white py-1 shadow-lg">
                @foreach (['xlsx' => ['Excel (.xlsx)', 'Satu tabel per sheet, siap diolah'], 'csv' => ['CSV (.csv)', 'Untuk Google Sheets / aplikasi lain']] as $format => [$label, $hint])
                    <a href="{{ route('admin.analitik.ekspor', array_merge($this->periodQuery(), ['tab' => $tab, 'format' => $format])) }}"
                       class="block px-4 py-2.5 hover:bg-[#F7F8FA]">
                        <span class="block text-[13px] font-semibold text-[#171B28]">{{ $label }}</span>
                        <span class="block text-[11.5px] text-[#848CA3]">{{ $hint }}</span>
                    </a>
                @endforeach
                <p class="border-t border-[#E4E8EF] px-4 py-2 text-[11px] text-[#848CA3]">
                    {{ $tab === 'iklan' ? 'Performa iklan' : 'Konten & tayangan' }} · {{ $r->periodLabel() }}
                </p>
            </div>
        </details>
        @endcan
    </div>

    <p class="-mt-3 text-[13px] text-[#6C7387]">
        Periode <strong class="font-semibold text-[#171B28]">{{ $r->periodLabel() }}</strong> ({{ $fmt($r->days) }} hari),
        dibandingkan dengan {{ $r->prevFrom->translatedFormat('d M Y') }} – {{ $r->prevTo->translatedFormat('d M Y') }}.
    </p>

    {{-- Tab --}}
    @if ($this->canContent() && $this->canAds())
        <div class="flex gap-1 border-b border-[#E4E8EF]">
            @foreach (['konten' => 'Konten & Tayangan', 'iklan' => 'Performa Iklan'] as $key => $label)
                <button type="button" wire:click="setTab('{{ $key }}')"
                        @class(['-mb-px border-b-2 px-4 py-2.5 text-[13px] font-semibold transition-colors',
                                'border-brand-600 text-brand-600' => $tab === $key,
                                'border-transparent text-[#6C7387] hover:text-[#171B28]' => $tab !== $key])>{{ $label }}</button>
            @endforeach
        </div>
    @endif

    <div wire:loading.class="opacity-60" class="space-y-6 transition-opacity">
    @if ($tab === 'konten')
        @php
            $s = $r->contentSummary();
            $daily = $r->dailyViews();
            $peakDay = collect($daily)->sortDesc()->keys()->first();
            $g = $r->granularity();
            $perLabel = ['day' => 'Harian', 'week' => 'per Pekan', 'month' => 'per Bulan'][$g];
        @endphp

        {{-- KPI --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="{{ $card }} px-5 py-4">
                <div class="text-[12.5px] font-semibold text-[#6C7387]">Total Tayangan</div>
                <div class="mt-1.5 text-[28px] font-semibold leading-none">{{ $fmt($s['views']) }}</div>
                <x-admin.trend-badge :change="$s['change']" class="mt-2" />
            </div>
            <div class="{{ $card }} px-5 py-4">
                <div class="text-[12.5px] font-semibold text-[#6C7387]">Artikel Terbit</div>
                <div class="mt-1.5 text-[28px] font-semibold leading-none">{{ $fmt($s['published']) }}</div>
                <x-admin.trend-badge :change="\App\Services\Analytics\AnalyticsReport::change($s['published'], $s['prev_published'])" class="mt-2" />
            </div>
            <div class="{{ $card }} px-5 py-4">
                <div class="text-[12.5px] font-semibold text-[#6C7387]">Artikel Dibaca</div>
                <div class="mt-1.5 text-[28px] font-semibold leading-none">{{ $fmt($s['viewed_articles']) }}</div>
                <div class="mt-2 text-[11.5px] text-[#848CA3]">Artikel yang mendapat tayangan</div>
            </div>
            <div class="{{ $card }} px-5 py-4">
                <div class="text-[12.5px] font-semibold text-[#6C7387]">Rata-rata per Artikel</div>
                <div class="mt-1.5 text-[28px] font-semibold leading-none">{{ number_format($s['avg_per_article'], 1, ',', '.') }}</div>
                <div class="mt-2 text-[11.5px] text-[#848CA3]">Tayangan per artikel yang dibaca</div>
            </div>
        </div>

        {{-- Tren harian --}}
        <div class="{{ $card }} p-5">
            <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                <h3 class="text-[15px] font-bold">Tren Tayangan {{ $perLabel }}</h3>
                @if ($peakDay && $daily[$peakDay] > 0)
                    <span class="text-[12px] text-[#6C7387]">Hari tertinggi: <strong class="text-[#171B28]">{{ $fmt($daily[$peakDay]) }}</strong> pada {{ \Carbon\Carbon::parse($peakDay)->translatedFormat('d M Y') }}</span>
                @endif
            </div>
            <x-admin.bar-chart :data="$r->bucket($daily)" :granularity="$g" unit="tayangan" />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Per kategori --}}
            <div class="{{ $card }} p-5">
                <h3 class="text-[15px] font-bold">Tayangan per Kategori</h3>
                <p class="mb-3 mt-0.5 text-[12px] text-[#6C7387]">Sub-kategori digabung ke kategori utamanya.</p>
                <x-admin.bar-list :rows="$r->viewsByCategory()->map(fn ($c) => [
                    'label' => $c['name'],
                    'value' => $c['views'],
                    'sub' => $c['articles'] . ' artikel · ' . $pct($c['share']) . ' dari total',
                ])->all()" />
            </div>

            {{-- Artikel teratas --}}
            <div class="{{ $card }} p-5">
                <h3 class="text-[15px] font-bold">Artikel Teratas</h3>
                <p class="mb-3 mt-0.5 text-[12px] text-[#6C7387]">10 artikel dengan tayangan terbanyak di periode ini.</p>
                <x-admin.bar-list :rows="$r->topArticles(10)->values()->map(fn ($a, $i) => [
                    'label' => ($i + 1) . '. ' . $a->title,
                    'value' => $a->views,
                    'sub' => $a->author_name ?? 'Tanpa penulis',
                    'href' => url('/artikel/' . $a->slug),
                ])->all()" />
            </div>
        </div>

        {{-- Per penulis --}}
        @php $authors = $r->viewsByAuthor(); @endphp
        <div class="{{ $card }} overflow-hidden">
            <div class="px-5 py-4">
                <h3 class="text-[15px] font-bold">Tayangan per Penulis</h3>
                <p class="mt-0.5 text-[12px] text-[#6C7387]">Artikel terbit = yang terbit di periode ini; tayangan dihitung dari semua artikel penulis yang dibaca di periode ini.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] border-collapse">
                    <thead>
                        <tr>
                            <th class="{{ $th }}">Penulis</th>
                            <th class="{{ $thNum }}">Artikel Terbit</th>
                            <th class="{{ $thNum }}">Artikel Dibaca</th>
                            <th class="{{ $thNum }}">Tayangan</th>
                            <th class="{{ $thNum }}">Rata-rata</th>
                            <th class="{{ $th }} w-40">Porsi</th>
                            <th class="{{ $th }}">Artikel Teratas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($authors as $a)
                            <tr class="border-b border-[#E4E8EF] last:border-0">
                                <td class="whitespace-nowrap px-4 py-3 text-[13px] font-semibold">
                                    @if ($a['id'] && ($author = \App\Models\User::find($a['id'])))
                                        <a href="{{ $author->profileUrl() }}" target="_blank" class="hover:text-brand-600">{{ $a['name'] }}</a>
                                    @else
                                        {{ $a['name'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px]">{{ $fmt($a['published']) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px]">{{ $fmt($a['articles']) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px] font-semibold">{{ $fmt($a['views']) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px]">{{ number_format($a['avg'], 1, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#F1F3F7]">
                                            <div class="h-full rounded-full bg-brand-500" style="width: {{ $a['share'] }}%"></div>
                                        </div>
                                        <span class="w-11 text-right font-mono text-[11.5px] text-[#6C7387]">{{ $pct($a['share']) }}</span>
                                    </div>
                                </td>
                                <td class="max-w-[260px] px-4 py-3 text-[12.5px]">
                                    @if ($a['top'])
                                        <a href="{{ url('/artikel/' . $a['top']->slug) }}" target="_blank" class="block truncate hover:text-brand-600" title="{{ $a['top']->title }}">{{ $a['top']->title }}</a>
                                        <span class="text-[11px] text-[#848CA3]">{{ $fmt($a['top']->views) }} tayangan</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-[13px] text-[#848CA3]">Belum ada tayangan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Lokasi pembaca --}}
        @php
            $loc = $r->locationSummary();
            $locPct = fn ($n) => $loc['total'] ? $pct($n / $loc['total'] * 100) : '—';
            $localCity = config('analytics.local.city');
            $localRegion = \App\Services\Analytics\GeoLocator::regionLabel(config('analytics.local.region'), 'ID');
            $geoReady = app(\App\Services\Analytics\GeoLocator::class)->available();
        @endphp
        <div class="{{ $card }} overflow-hidden">
            <div class="flex flex-wrap items-start justify-between gap-2 px-5 py-4">
                <div>
                    <h3 class="text-[15px] font-bold">Lokasi Pembaca</h3>
                    <p class="mt-0.5 text-[12px] text-[#6C7387]">
                        Perkiraan wilayah dari alamat IP; alamat IP tidak disimpan. Lokasi tercatat untuk {{ $fmt($loc['total']) }} tayangan.
                    </p>
                </div>
            </div>

            @if (! $geoReady)
                <div class="mx-5 mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[12.5px] text-amber-800">
                    Database lokasi belum tersedia, sehingga tayangan baru tercatat sebagai "Tidak diketahui".
                    Jalankan <code class="font-mono">php artisan geoip:update</code> di server.
                </div>
            @endif

            @if ($loc['total'] === 0)
                <p class="px-5 pb-8 pt-2 text-center text-[13px] text-[#848CA3]">Belum ada data lokasi pada periode ini. Lokasi mulai tercatat sejak fitur ini aktif.</p>
            @else
                {{-- Porsi pembaca lokal vs luar daerah --}}
                <div class="grid grid-cols-2 divide-[#E4E8EF] border-y border-[#E4E8EF] sm:grid-cols-4 sm:divide-x">
                    @foreach ([
                        ["Dari {$localCity}", $loc['local_city']],
                        ["Dari {$localRegion}", $loc['local_region']],
                        ['Luar ' . $localRegion, max(0, $loc['domestic'] - $loc['local_region'])],
                        ['Luar negeri', $loc['foreign']],
                    ] as [$label, $value])
                        <div class="px-5 py-3">
                            <div class="text-[11.5px] font-semibold text-[#6C7387]">{{ $label }}</div>
                            <div class="mt-0.5 text-[20px] font-semibold leading-tight">{{ $locPct($value) }}</div>
                            <div class="text-[11px] text-[#848CA3]">{{ $fmt($value) }} tayangan</div>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-2">
                    <div>
                        <h4 class="mb-2 text-[13px] font-bold text-[#3B4152]">Per Provinsi / Negara</h4>
                        <x-admin.bar-list :rows="$r->viewsByRegion()->take(12)->map(fn ($g) => [
                            'label' => $g['label'],
                            'value' => $g['views'],
                            'sub' => $pct($g['share']) . ' dari tayangan berlokasi',
                        ])->all()" />
                    </div>
                    <div>
                        <h4 class="mb-2 text-[13px] font-bold text-[#3B4152]">Kota / Kabupaten Teratas</h4>
                        <x-admin.bar-list :rows="$r->viewsByCity(12)->map(fn ($g) => [
                            'label' => $g['label'],
                            'value' => $g['views'],
                            'sub' => $pct($g['share']),
                        ])->all()" empty="Belum ada kota yang terdeteksi." />
                    </div>
                </div>

                @if ($loc['unknown'])
                    <p class="px-5 pb-1 text-[11.5px] text-[#848CA3]">{{ $locPct($loc['unknown']) }} tayangan tanpa lokasi (IP tidak dikenali, jaringan lokal, atau sebelum database lokasi tersedia).</p>
                @endif
            @endif

            <p class="px-5 pb-4 pt-1 text-[11px] text-[#848CA3]">
                Lokasi dari jaringan seluler bisa terbaca di kota server operator; angka per kota adalah perkiraan.
                <a href="{{ config('analytics.geoip.attribution_url') }}" target="_blank" rel="noopener" class="underline hover:text-brand-600">{{ config('analytics.geoip.attribution') }}</a>
            </p>
        </div>

        <p class="text-[11.5px] text-[#848CA3]">
            Tayangan dihitung sekali per sesi pembaca; bot mesin pencari dan pratinjau redaksi tidak dihitung.
            Rekap harian tercatat sejak fitur Laporan Analitik aktif; total sepanjang masa tetap tersedia di daftar artikel.
        </p>
    @else
        @php
            $s = $r->adsSummary();
            $daily = $r->adsDaily();
            $g = $r->granularity();
            $perLabel = ['day' => 'per Hari', 'week' => 'per Pekan', 'month' => 'per Bulan'][$g];
            $ctrDiff = $s['prev_impressions'] > 0 ? round($s['ctr'] - $s['prev_ctr'], 2) : null;
        @endphp

        {{-- KPI --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="{{ $card }} px-5 py-4">
                <div class="text-[12.5px] font-semibold text-[#6C7387]">Impresi</div>
                <div class="mt-1.5 text-[28px] font-semibold leading-none">{{ $fmt($s['impressions']) }}</div>
                <x-admin.trend-badge :change="$s['impressions_change']" class="mt-2" />
            </div>
            <div class="{{ $card }} px-5 py-4">
                <div class="text-[12.5px] font-semibold text-[#6C7387]">Klik</div>
                <div class="mt-1.5 text-[28px] font-semibold leading-none">{{ $fmt($s['clicks']) }}</div>
                <x-admin.trend-badge :change="$s['clicks_change']" class="mt-2" />
            </div>
            <div class="{{ $card }} px-5 py-4">
                <div class="text-[12.5px] font-semibold text-[#6C7387]">CTR</div>
                <div class="mt-1.5 text-[28px] font-semibold leading-none">{{ $pct($s['ctr'], 2) }}</div>
                @if ($ctrDiff === null)
                    <div class="mt-2 text-[11.5px] text-[#848CA3]">Klik ÷ impresi</div>
                @else
                    <span @class(['mt-2 inline-flex items-center gap-1 text-[11.5px] font-semibold', 'text-success' => $ctrDiff >= 0, 'text-danger' => $ctrDiff < 0])>
                        {{ $ctrDiff >= 0 ? '▲' : '▼' }} {{ number_format(abs($ctrDiff), 2, ',', '.') }} poin
                        <span class="font-normal text-[#848CA3]">vs periode sebelumnya</span>
                    </span>
                @endif
            </div>
            <div class="{{ $card }} px-5 py-4">
                <div class="text-[12.5px] font-semibold text-[#6C7387]">Iklan Tayang</div>
                <div class="mt-1.5 text-[28px] font-semibold leading-none">{{ $fmt($s['active_ads']) }}</div>
                <div class="mt-2 text-[11.5px] text-[#848CA3]">Sedang aktif hari ini</div>
            </div>
        </div>

        {{-- Dua grafik terpisah: skala impresi & klik berbeda jauh, jadi tidak digabung dalam satu sumbu --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="{{ $card }} p-5">
                <h3 class="mb-4 text-[15px] font-bold">Impresi {{ $perLabel }}</h3>
                <x-admin.bar-chart :data="$r->bucket($daily['impressions'])" :granularity="$g" unit="impresi" height="h-36" />
            </div>
            <div class="{{ $card }} p-5">
                <h3 class="mb-4 text-[15px] font-bold">Klik {{ $perLabel }}</h3>
                <x-admin.bar-chart :data="$r->bucket($daily['clicks'])" :granularity="$g" unit="klik" height="h-36" bar="bg-brand-300" />
            </div>
        </div>

        {{-- Per lokasi --}}
        <div class="{{ $card }} overflow-hidden">
            <div class="px-5 py-4"><h3 class="text-[15px] font-bold">Performa per Lokasi</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] border-collapse">
                    <thead>
                        <tr>
                            <th class="{{ $th }}">Lokasi</th>
                            <th class="{{ $thNum }}">Iklan</th>
                            <th class="{{ $thNum }}">Impresi</th>
                            <th class="{{ $thNum }}">Klik</th>
                            <th class="{{ $thNum }}">CTR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($r->adsByPlacement() as $p)
                            <tr class="border-b border-[#E4E8EF] last:border-0">
                                <td class="px-4 py-3 text-[13px] font-semibold">{{ $p['label'] }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px]">{{ $fmt($p['ads']) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px]">{{ $fmt($p['impressions']) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px]">{{ $fmt($p['clicks']) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px] font-semibold">{{ $p['impressions'] ? $pct($p['ctr'], 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Per iklan --}}
        @php
            $statusMeta = [
                'active' => ['Tayang', 'badge-success'],
                'scheduled' => ['Terjadwal', 'badge-info'],
                'expired' => ['Berakhir', 'badge-neutral'],
                'inactive' => ['Nonaktif', 'badge-neutral'],
            ];
        @endphp
        <div class="{{ $card }} overflow-hidden">
            <div class="px-5 py-4">
                <h3 class="text-[15px] font-bold">Performa per Iklan</h3>
                <p class="mt-0.5 text-[12px] text-[#6C7387]">Iklan yang tayang atau tercatat di periode ini, diurutkan dari impresi terbanyak.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] border-collapse">
                    <thead>
                        <tr>
                            <th class="{{ $th }}">Pengiklan</th>
                            <th class="{{ $th }}">Status</th>
                            <th class="{{ $th }}">Kontrak</th>
                            <th class="{{ $thNum }}">Impresi</th>
                            <th class="{{ $thNum }}">Klik</th>
                            <th class="{{ $thNum }}">CTR</th>
                            <th class="{{ $th }} w-36">Porsi Impresi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($r->adsByAdvertisement() as $row)
                            @php
                                $ad = $row['ad'];
                                [$statusLabel, $statusClass] = $statusMeta[$ad->effective_status] ?? [ucfirst($ad->effective_status), 'badge-neutral'];
                                $daysLeft = $ad->end_date ? (int) today()->diffInDays($ad->end_date, false) : null;
                            @endphp
                            <tr class="border-b border-[#E4E8EF] last:border-0">
                                <td class="px-4 py-3">
                                    <div class="text-[13px] font-semibold">{{ $ad->advertiser_name }}</div>
                                    <div class="text-[11px] text-[#848CA3]">{{ \App\Models\Advertisement::PLACEMENTS[$ad->placement]['label'] ?? $ad->placement }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3"><span class="{{ $statusClass }}"><span class="badge-dot"></span>{{ $statusLabel }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-[12px] text-[#3B4152]">
                                    {{ $ad->start_date?->translatedFormat('d M') }} – {{ $ad->end_date?->translatedFormat('d M Y') }}
                                    @if ($daysLeft !== null && $daysLeft >= 0 && $ad->effective_status === 'active')
                                        <div class="text-[11px] {{ $daysLeft <= 7 ? 'font-semibold text-warning' : 'text-[#848CA3]' }}">sisa {{ $daysLeft }} hari</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px]">{{ $fmt($row['impressions']) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px]">{{ $fmt($row['clicks']) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[12.5px] font-semibold">{{ $row['impressions'] ? $pct($row['ctr'], 2) : '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#F1F3F7]">
                                            <div class="h-full rounded-full bg-brand-500" style="width: {{ $row['share'] }}%"></div>
                                        </div>
                                        <span class="w-11 text-right font-mono text-[11.5px] text-[#6C7387]">{{ $pct($row['share']) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-[13px] text-[#848CA3]">Belum ada data iklan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-[11.5px] text-[#848CA3]">
            Impresi dicatat sekali per iklan per muat halaman, hanya saat iklan benar-benar terlihat di layar. CTR = klik ÷ impresi.
        </p>
    @endif
    </div>
</div>
