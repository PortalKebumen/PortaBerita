<?php

namespace App\Services\Analytics;

use App\Models\Advertisement;
use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\Category;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Laporan Analitik: tayangan artikel (dari article_daily_views) dan performa iklan (dari ad_metrics)
 * untuk satu periode, dibandingkan dengan periode sebelumnya yang sama panjang.
 *
 * $authorId diisi untuk membatasi laporan konten ke artikel milik satu penulis (dashboard Penulis).
 */
class AnalyticsReport
{
    /** Pilihan cepat periode; "kustom" memakai rentang tanggal bebas. */
    public const PRESETS = [
        '7' => '7 hari',
        '30' => '30 hari',
        '90' => '90 hari',
        'bulan-ini' => 'Bulan ini',
        'bulan-lalu' => 'Bulan lalu',
        'tahun-ini' => 'Tahun ini',
    ];

    /** Rentang terpanjang yang bisa dipilih (± 2 tahun) agar kueri tetap ringan. */
    public const MAX_DAYS = 731;

    public readonly int $days;

    public readonly CarbonImmutable $prevFrom;

    public readonly CarbonImmutable $prevTo;

    /** @var array<int, Collection<int, object>> */
    private array $articleViewsCache = [];

    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly ?int $authorId = null,
    ) {
        $this->days = (int) $from->diffInDays($to) + 1;
        $this->prevTo = $from->subDay();
        $this->prevFrom = $this->prevTo->subDays($this->days - 1);
    }

    /** N hari terakhir sampai hari ini (dipakai dashboard). */
    public static function make(int $days, ?int $authorId = null): self
    {
        $today = CarbonImmutable::today();

        return new self($today->subDays(max(1, $days) - 1), $today, $authorId);
    }

    /**
     * Dari pilihan periode (preset atau "kustom" + tanggal dari/sampai). Nilai tidak valid
     * dikembalikan ke 30 hari; tanggal sampai dibatasi hari ini dan rentang dibatasi MAX_DAYS.
     */
    public static function fromPeriod(?string $preset, ?string $from = null, ?string $to = null, ?int $authorId = null): self
    {
        $today = CarbonImmutable::today();

        if ($preset === 'kustom') {
            $start = self::parseDate($from);
            $end = self::parseDate($to);

            if ($start && $end) {
                if ($start->gt($end)) {
                    [$start, $end] = [$end, $start];
                }
                $end = $end->min($today);
                $start = $start->min($end)->max($end->subDays(self::MAX_DAYS - 1));

                return new self($start, $end, $authorId);
            }
        }

        return match ($preset) {
            '7', '90' => self::make((int) $preset, $authorId),
            'bulan-ini' => new self($today->startOfMonth(), $today, $authorId),
            'bulan-lalu' => new self($today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay(), $authorId),
            'tahun-ini' => new self($today->startOfYear(), $today, $authorId),
            default => self::make(30, $authorId),
        };
    }

    public function periodLabel(): string
    {
        return $this->from->isSameDay($this->to)
            ? $this->from->translatedFormat('d M Y')
            : $this->from->translatedFormat($this->from->isSameYear($this->to) ? 'd M' : 'd M Y') . ' – ' . $this->to->translatedFormat('d M Y');
    }

    /** Satuan grafik tren: per hari sampai ±3 bulan, per pekan sampai 1 tahun, selebihnya per bulan. */
    public function granularity(): string
    {
        return match (true) {
            $this->days <= 92 => 'day',
            $this->days <= 366 => 'week',
            default => 'month',
        };
    }

    /**
     * Kelompokkan deret harian ke satuan granularity(); kunci = tanggal awal kelompok (Y-m-d).
     *
     * @param  array<string, int>  $daily
     * @return array<string, int>
     */
    public function bucket(array $daily): array
    {
        $granularity = $this->granularity();
        if ($granularity === 'day') {
            return $daily;
        }

        $buckets = [];
        foreach ($daily as $day => $value) {
            $date = CarbonImmutable::parse($day);
            $key = ($granularity === 'week' ? $date->startOfWeek() : $date->startOfMonth())->max($this->from)->toDateString();
            $buckets[$key] = ($buckets[$key] ?? 0) + $value;
        }

        return $buckets;
    }

    private static function parseDate(?string $value): ?CarbonImmutable
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return rescue(fn () => CarbonImmutable::createFromFormat('!Y-m-d', $value), null, false) ?: null;
    }

    /* ===================== Konten ===================== */

    /**
     * Ringkasan konten: tayangan periode ini vs sebelumnya, artikel terbit, rata-rata per artikel.
     *
     * @return array{views: int, prev_views: int, change: ?float, published: int, prev_published: int, viewed_articles: int, avg_per_article: float}
     */
    public function contentSummary(): array
    {
        $current = $this->articleViews();
        $views = (int) $current->sum('views');
        $prevViews = (int) $this->viewsQuery($this->prevFrom, $this->prevTo)->sum('v.views');

        $published = fn ($from, $to) => $this->articleQuery()
            ->where('status', ArticleStatus::Published->value)
            ->whereBetween('published_at', [$from->startOfDay(), $to->endOfDay()])
            ->count();

        return [
            'views' => $views,
            'prev_views' => $prevViews,
            'change' => self::change($views, $prevViews),
            'published' => $published($this->from, $this->to),
            'prev_published' => $published($this->prevFrom, $this->prevTo),
            'viewed_articles' => $current->count(),
            'avg_per_article' => $current->count() ? round($views / $current->count(), 1) : 0.0,
        ];
    }

    /**
     * Tayangan per hari sepanjang periode (hari tanpa tayangan = 0).
     *
     * @return array<string, int> 'Y-m-d' => views
     */
    public function dailyViews(): array
    {
        $rows = $this->viewsQuery($this->from, $this->to)
            ->selectRaw('v.date as day, SUM(v.views) as total')
            ->groupBy('v.date')
            ->pluck('total', 'day');

        return $this->fillDays(fn (string $day) => (int) ($rows[$day] ?? 0));
    }

    /**
     * Tayangan per kategori utama (sub-kategori digabung ke induknya).
     *
     * @return Collection<int, array{name: string, articles: int, views: int, share: float}>
     */
    public function viewsByCategory(): Collection
    {
        $categories = Category::query()->get(['id', 'name', 'parent_id'])->keyBy('id');
        $total = max(1, (int) $this->articleViews()->sum('views'));

        return $this->articleViews()
            ->groupBy(function ($row) use ($categories) {
                $category = $row->category_id ? ($categories[$row->category_id] ?? null) : null;

                return $category?->parent_id ?? $category?->id ?? 0;
            })
            ->map(fn (Collection $rows, $id) => [
                'name' => $categories[$id]->name ?? 'Tanpa Kategori',
                'articles' => $rows->count(),
                'views' => (int) $rows->sum('views'),
                'share' => round($rows->sum('views') / $total * 100, 1),
            ])
            ->sortByDesc('views')
            ->values();
    }

    /**
     * Tayangan per penulis, dengan jumlah artikel terbit di periode & artikel teratasnya.
     *
     * @return Collection<int, array{id: int, name: string, published: int, articles: int, views: int, avg: float, share: float, top: ?object}>
     */
    public function viewsByAuthor(): Collection
    {
        $total = max(1, (int) $this->articleViews()->sum('views'));

        $published = $this->articleQuery()
            ->where('status', ArticleStatus::Published->value)
            ->whereBetween('published_at', [$this->from->startOfDay(), $this->to->endOfDay()])
            ->selectRaw('COALESCE(author_id, 0) as author_key, COUNT(*) as total')
            ->groupBy('author_key')
            ->pluck('total', 'author_key');

        // Artikel tanpa penulis dikelompokkan di kunci 0
        return $this->articleViews()
            ->groupBy(fn ($row) => (int) $row->author_id)
            ->map(fn (Collection $rows, $authorId) => [
                'id' => (int) $authorId,
                'name' => $rows->first()->author_name ?? 'Tanpa Penulis',
                'published' => (int) ($published[$authorId] ?? 0),
                'articles' => $rows->count(),
                'views' => (int) $rows->sum('views'),
                'avg' => round($rows->sum('views') / max(1, $rows->count()), 1),
                'share' => round($rows->sum('views') / $total * 100, 1),
                'top' => $rows->sortByDesc('views')->first(),
            ])
            ->sortByDesc('views')
            ->values();
    }

    /** @return Collection<int, object> artikel dengan tayangan terbanyak di periode */
    public function topArticles(int $limit = 10): Collection
    {
        return $this->articleViews()->sortByDesc('views')->take($limit)->values();
    }

    /** @return Collection<int, object> semua artikel yang dibaca di periode, terbanyak lebih dulu (untuk ekspor) */
    public function articles(): Collection
    {
        return $this->articleViews()->sortByDesc('views')->values();
    }

    /* ===================== Lokasi pembaca ===================== */

    /**
     * Ringkasan lokasi: tayangan yang lokasinya tercatat, porsi pembaca lokal (kota & provinsi),
     * luar negeri, dan yang tidak diketahui. Hanya untuk seluruh situs (bukan per penulis).
     *
     * @return array{total: int, known: int, local_city: int, local_region: int, domestic: int, foreign: int, unknown: int, countries: int}
     */
    public function locationSummary(): array
    {
        $rows = $this->locations();
        $local = config('analytics.local');
        $sum = fn (callable $filter) => (int) $rows->filter($filter)->sum('views');

        return [
            'total' => (int) $rows->sum('views'),
            'known' => $sum(fn ($r) => $r->country_code !== ''),
            'local_city' => $sum(fn ($r) => $r->country_code === $local['country'] && strcasecmp($r->city, $local['city']) === 0),
            'local_region' => $sum(fn ($r) => $r->country_code === $local['country'] && strcasecmp($r->region, $local['region']) === 0),
            'domestic' => $sum(fn ($r) => $r->country_code === $local['country']),
            'foreign' => $sum(fn ($r) => $r->country_code !== '' && $r->country_code !== $local['country']),
            'unknown' => $sum(fn ($r) => $r->country_code === ''),
            'countries' => $rows->where('country_code', '!=', '')->pluck('country_code')->unique()->count(),
        ];
    }

    /**
     * Tayangan per provinsi (Indonesia) atau per negara (luar negeri).
     *
     * @return Collection<int, array{label: string, country: string, views: int, share: float}>
     */
    public function viewsByRegion(): Collection
    {
        $local = config('analytics.local.country');

        return $this->groupLocations(function ($r) use ($local) {
            if ($r->country_code === '') {
                return ['Tidak diketahui', ''];
            }
            if ($r->country_code !== $local) {
                return ['Luar negeri · ' . ($r->country ?: $r->country_code), $r->country];
            }

            return [GeoLocator::regionLabel($r->region ?: null, $r->country_code), $r->country];
        });
    }

    /**
     * Tayangan per kota/kabupaten (yang kotanya diketahui).
     *
     * @return Collection<int, array{label: string, country: string, views: int, share: float}>
     */
    public function viewsByCity(int $limit = 15): Collection
    {
        return $this->groupLocations(
            fn ($r) => $r->city === '' ? null : [
                $r->city . ', ' . ($r->country_code === config('analytics.local.country')
                    ? GeoLocator::regionLabel($r->region ?: null, $r->country_code)
                    : ($r->country ?: $r->country_code)),
                $r->country,
            ]
        )->take($limit)->values();
    }

    /**
     * Semua baris lokasi lengkap (untuk ekspor).
     *
     * @return Collection<int, array{country: string, region: string, city: string, views: int, share: float}>
     */
    public function locationRows(): Collection
    {
        $total = max(1, (int) $this->locations()->sum('views'));

        return $this->locations()
            ->sortByDesc('views')
            ->map(fn ($r) => [
                'country' => $r->country ?: ($r->country_code ?: 'Tidak diketahui'),
                'region' => $r->country_code === '' ? '' : GeoLocator::regionLabel($r->region ?: null, $r->country_code),
                'city' => $r->city,
                'views' => (int) $r->views,
                'share' => round($r->views / $total * 100, 1),
            ])
            ->values();
    }

    /** @return Collection<int, object{country_code: string, country: string, region: string, city: string, views: int}> */
    private function locations(): Collection
    {
        // Rekap lokasi bersifat seluruh situs; laporan per penulis tidak memilikinya
        if ($this->authorId) {
            return collect();
        }

        return $this->articleViewsCache['locations'] ??= DB::table('article_daily_locations')
            ->whereBetween('date', [$this->from->toDateString(), $this->to->toDateString()])
            ->selectRaw('country_code, country, region, city, SUM(views) as views')
            ->groupBy('country_code', 'country', 'region', 'city')
            ->get()
            ->map(function ($row) {
                $row->views = (int) $row->views;

                return $row;
            });
    }

    /**
     * @param  callable(object): (array{0: string, 1: string}|null)  $key  [label, negara] atau null untuk dilewati
     * @return Collection<int, array{label: string, country: string, views: int, share: float}>
     */
    private function groupLocations(callable $key): Collection
    {
        $total = max(1, (int) $this->locations()->sum('views'));
        $groups = [];

        foreach ($this->locations() as $row) {
            if (! $k = $key($row)) {
                continue;
            }
            $groups[$k[0]] ??= ['label' => $k[0], 'country' => $k[1], 'views' => 0];
            $groups[$k[0]]['views'] += $row->views;
        }

        return collect($groups)
            ->map(fn ($g) => $g + ['share' => round($g['views'] / $total * 100, 1)])
            ->sortByDesc('views')
            ->values();
    }

    /* ===================== Iklan ===================== */

    /**
     * @return array{impressions: int, clicks: int, ctr: float, prev_impressions: int, prev_clicks: int, prev_ctr: float, impressions_change: ?float, clicks_change: ?float, active_ads: int}
     */
    public function adsSummary(): array
    {
        $count = fn ($from, $to) => DB::table('ad_metrics')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $now = $count($this->from, $this->to);
        $prev = $count($this->prevFrom, $this->prevTo);

        $impressions = (int) ($now['impression'] ?? 0);
        $clicks = (int) ($now['click'] ?? 0);
        $prevImpressions = (int) ($prev['impression'] ?? 0);
        $prevClicks = (int) ($prev['click'] ?? 0);

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => self::ctr($clicks, $impressions),
            'prev_impressions' => $prevImpressions,
            'prev_clicks' => $prevClicks,
            'prev_ctr' => self::ctr($prevClicks, $prevImpressions),
            'impressions_change' => self::change($impressions, $prevImpressions),
            'clicks_change' => self::change($clicks, $prevClicks),
            'active_ads' => Advertisement::activeRunning()->count(),
        ];
    }

    /**
     * Impresi & klik per hari.
     *
     * @return array{impressions: array<string, int>, clicks: array<string, int>}
     */
    public function adsDaily(): array
    {
        $rows = DB::table('ad_metrics')
            ->whereBetween('created_at', [$this->from->startOfDay(), $this->to->endOfDay()])
            ->selectRaw('DATE(created_at) as day, type, COUNT(*) as total')
            ->groupBy('day', 'type')
            ->get()
            ->groupBy('type')
            ->map(fn (Collection $r) => $r->pluck('total', 'day'));

        return [
            'impressions' => $this->fillDays(fn ($day) => (int) ($rows['impression'][$day] ?? 0)),
            'clicks' => $this->fillDays(fn ($day) => (int) ($rows['click'][$day] ?? 0)),
        ];
    }

    /**
     * Performa per iklan di periode, beserta lokasi, status tayang, dan sisa hari kontrak.
     *
     * @return Collection<int, array{ad: Advertisement, impressions: int, clicks: int, ctr: float, share: float}>
     */
    public function adsByAdvertisement(): Collection
    {
        $metrics = DB::table('ad_metrics')
            ->whereBetween('created_at', [$this->from->startOfDay(), $this->to->endOfDay()])
            ->selectRaw('advertisement_id, type, COUNT(*) as total')
            ->groupBy('advertisement_id', 'type')
            ->get()
            ->groupBy('advertisement_id');

        $totalImpressions = max(1, (int) $metrics->flatten(1)->where('type', 'impression')->sum('total'));

        return Advertisement::query()
            ->whereIn('id', $metrics->keys())
            ->orWhere(fn ($q) => $q->where('start_date', '<=', $this->to)->where('end_date', '>=', $this->from))
            ->get()
            ->map(function (Advertisement $ad) use ($metrics, $totalImpressions) {
                $rows = ($metrics[$ad->id] ?? collect())->pluck('total', 'type');
                $impressions = (int) ($rows['impression'] ?? 0);
                $clicks = (int) ($rows['click'] ?? 0);

                return [
                    'ad' => $ad,
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'ctr' => self::ctr($clicks, $impressions),
                    'share' => round($impressions / $totalImpressions * 100, 1),
                ];
            })
            ->sortByDesc('impressions')
            ->values();
    }

    /**
     * Performa per lokasi iklan (header, sidebar, inline, footer).
     *
     * @return Collection<int, array{placement: string, label: string, ads: int, impressions: int, clicks: int, ctr: float}>
     */
    public function adsByPlacement(): Collection
    {
        $byAd = $this->adsByAdvertisement();

        return collect(Advertisement::PLACEMENTS)->map(function (array $spec, string $placement) use ($byAd) {
            $rows = $byAd->filter(fn ($row) => $row['ad']->placement === $placement);
            $impressions = (int) $rows->sum('impressions');
            $clicks = (int) $rows->sum('clicks');

            return [
                'placement' => $placement,
                'label' => $spec['label'],
                'ads' => $rows->count(),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'ctr' => self::ctr($clicks, $impressions),
            ];
        })->values();
    }

    /* ===================== Bantuan ===================== */

    public static function ctr(int $clicks, int $impressions): float
    {
        return $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0.0;
    }

    /** Persentase perubahan terhadap periode sebelumnya; null bila periode sebelumnya kosong. */
    public static function change(int|float $current, int|float $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;
    }

    /**
     * Tayangan per artikel di periode ini, beserta judul, kategori & penulisnya.
     *
     * @return Collection<int, object{id: int, title: string, slug: string, category_id: ?int, author_id: ?int, author_name: ?string, published_at: ?string, views: int}>
     */
    private function articleViews(): Collection
    {
        return $this->articleViewsCache[0] ??= $this->viewsQuery($this->from, $this->to)
            ->leftJoin('users as u', 'u.id', '=', 'a.author_id')
            ->selectRaw('a.id, a.title, a.slug, a.category_id, a.author_id, u.name as author_name, a.published_at, SUM(v.views) as views')
            ->groupBy('a.id', 'a.title', 'a.slug', 'a.category_id', 'a.author_id', 'u.name', 'a.published_at')
            ->get()
            ->map(function ($row) {
                $row->views = (int) $row->views;

                return $row;
            });
    }

    private function viewsQuery(CarbonImmutable $from, CarbonImmutable $to): \Illuminate\Database\Query\Builder
    {
        return DB::table('article_daily_views as v')
            ->join('articles as a', 'a.id', '=', 'v.article_id')
            ->whereBetween('v.date', [$from->toDateString(), $to->toDateString()])
            ->when($this->authorId, fn ($q) => $q->where('a.author_id', $this->authorId));
    }

    private function articleQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Article::query()->when($this->authorId, fn ($q) => $q->where('author_id', $this->authorId));
    }

    /** @return array<string, int> */
    private function fillDays(callable $value): array
    {
        $days = [];
        foreach (CarbonPeriod::create($this->from, $this->to) as $day) {
            $key = $day->toDateString();
            $days[$key] = $value($key);
        }

        return $days;
    }
}
