<?php

namespace App\Services\Analytics;

use App\Models\Advertisement;
use App\Models\Category;
use Carbon\Carbon;

/**
 * Isi ekspor Laporan Analitik sebagai daftar tabel (satu tabel = satu sheet Excel / satu bagian CSV).
 * Deret waktu selalu per hari di ekspor agar bisa diolah lagi, apa pun satuan grafiknya di layar.
 */
class AnalyticsExport
{
    public function __construct(private readonly AnalyticsReport $report) {}

    /**
     * @return list<array{title: string, rows: list<list<mixed>>}>
     */
    public function tables(string $tab): array
    {
        return $tab === 'iklan' ? $this->adTables() : $this->contentTables();
    }

    public function filename(string $tab, string $extension): string
    {
        return sprintf(
            'laporan-analitik-%s_%s_%s.%s',
            $tab === 'iklan' ? 'iklan' : 'konten',
            $this->report->from->toDateString(),
            $this->report->to->toDateString(),
            $extension
        );
    }

    /** @return list<array{title: string, rows: list<list<mixed>>}> */
    private function contentTables(): array
    {
        $r = $this->report;
        $s = $r->contentSummary();
        $categories = Category::query()->get(['id', 'name', 'parent_id'])->keyBy('id');

        $categoryPath = function (?int $id) use ($categories): string {
            $category = $id ? ($categories[$id] ?? null) : null;
            if (! $category) {
                return '';
            }
            $parent = $category->parent_id ? ($categories[$category->parent_id] ?? null) : null;

            return $parent ? "{$parent->name} › {$category->name}" : $category->name;
        };

        return [
            ['title' => 'Ringkasan', 'rows' => [
                ['Metrik', 'Periode Ini', 'Periode Sebelumnya', 'Perubahan (%)'],
                ['Periode', $r->periodLabel(), $r->prevFrom->translatedFormat('d M Y') . ' – ' . $r->prevTo->translatedFormat('d M Y'), ''],
                ['Total tayangan', $s['views'], $s['prev_views'], $s['change']],
                ['Artikel terbit', $s['published'], $s['prev_published'], AnalyticsReport::change($s['published'], $s['prev_published'])],
                ['Artikel dibaca', $s['viewed_articles'], '', ''],
                ['Rata-rata tayangan per artikel', $s['avg_per_article'], '', ''],
                ['Diekspor pada', now()->translatedFormat('d M Y H:i'), '', ''],
            ]],
            ['title' => 'Tayangan Harian', 'rows' => array_merge(
                [['Tanggal', 'Tayangan']],
                collect($r->dailyViews())->map(fn ($views, $day) => [$day, $views])->values()->all()
            )],
            ['title' => 'Per Kategori', 'rows' => array_merge(
                [['Kategori', 'Artikel Dibaca', 'Tayangan', 'Porsi (%)']],
                $r->viewsByCategory()->map(fn ($c) => [$c['name'], $c['articles'], $c['views'], $c['share']])->all()
            )],
            ['title' => 'Per Penulis', 'rows' => array_merge(
                [['Penulis', 'Artikel Terbit (periode)', 'Artikel Dibaca', 'Tayangan', 'Rata-rata per Artikel', 'Porsi (%)', 'Artikel Teratas', 'Tayangan Artikel Teratas']],
                $r->viewsByAuthor()->map(fn ($a) => [
                    $a['name'], $a['published'], $a['articles'], $a['views'], $a['avg'], $a['share'],
                    $a['top']?->title, $a['top']?->views,
                ])->all()
            )],
            ['title' => 'Lokasi Pembaca', 'rows' => array_merge(
                [['Negara', 'Provinsi', 'Kota / Kabupaten', 'Tayangan', 'Porsi (%)']],
                $r->locationRows()->map(fn ($l) => [$l['country'], $l['region'], $l['city'], $l['views'], $l['share']])->all(),
                [[], ['Sumber lokasi: ' . config('analytics.geoip.attribution') . ' (' . config('analytics.geoip.attribution_url') . '). Perkiraan dari alamat IP; IP tidak disimpan.']]
            )],
            ['title' => 'Per Artikel', 'rows' => array_merge(
                [['Judul', 'Kategori', 'Penulis', 'Tanggal Terbit', 'Tayangan (periode)', 'URL']],
                $r->articles()->map(fn ($a) => [
                    $a->title,
                    $categoryPath($a->category_id),
                    $a->author_name,
                    $a->published_at ? Carbon::parse($a->published_at)->format('Y-m-d H:i') : '',
                    $a->views,
                    url('/artikel/' . $a->slug),
                ])->all()
            )],
        ];
    }

    /** @return list<array{title: string, rows: list<list<mixed>>}> */
    private function adTables(): array
    {
        $r = $this->report;
        $s = $r->adsSummary();
        $daily = $r->adsDaily();
        $statusLabels = ['active' => 'Tayang', 'scheduled' => 'Terjadwal', 'expired' => 'Berakhir', 'inactive' => 'Nonaktif'];

        return [
            ['title' => 'Ringkasan', 'rows' => [
                ['Metrik', 'Periode Ini', 'Periode Sebelumnya', 'Perubahan (%)'],
                ['Periode', $r->periodLabel(), $r->prevFrom->translatedFormat('d M Y') . ' – ' . $r->prevTo->translatedFormat('d M Y'), ''],
                ['Impresi', $s['impressions'], $s['prev_impressions'], $s['impressions_change']],
                ['Klik', $s['clicks'], $s['prev_clicks'], $s['clicks_change']],
                ['CTR (%)', $s['ctr'], $s['prev_ctr'], ''],
                ['Iklan tayang hari ini', $s['active_ads'], '', ''],
                ['Diekspor pada', now()->translatedFormat('d M Y H:i'), '', ''],
            ]],
            ['title' => 'Harian', 'rows' => array_merge(
                [['Tanggal', 'Impresi', 'Klik', 'CTR (%)']],
                collect($daily['impressions'])->map(fn ($impressions, $day) => [
                    $day, $impressions, $daily['clicks'][$day], AnalyticsReport::ctr($daily['clicks'][$day], $impressions),
                ])->values()->all()
            )],
            ['title' => 'Per Lokasi', 'rows' => array_merge(
                [['Lokasi', 'Ruang Maks (px)', 'Jumlah Iklan', 'Impresi', 'Klik', 'CTR (%)']],
                $r->adsByPlacement()->map(fn ($p) => [
                    $p['label'],
                    (Advertisement::PLACEMENTS[$p['placement']]['max_width'] ?? '') . '×' . (Advertisement::PLACEMENTS[$p['placement']]['max_height'] ?? ''),
                    $p['ads'], $p['impressions'], $p['clicks'], $p['ctr'],
                ])->all()
            )],
            ['title' => 'Per Iklan', 'rows' => array_merge(
                [['Pengiklan', 'Lokasi', 'Status', 'Mulai', 'Berakhir', 'Impresi', 'Klik', 'CTR (%)', 'Porsi Impresi (%)', 'URL Tujuan']],
                $r->adsByAdvertisement()->map(fn ($row) => [
                    $row['ad']->advertiser_name,
                    Advertisement::PLACEMENTS[$row['ad']->placement]['label'] ?? $row['ad']->placement,
                    $statusLabels[$row['ad']->effective_status] ?? $row['ad']->effective_status,
                    $row['ad']->start_date?->toDateString(),
                    $row['ad']->end_date?->toDateString(),
                    $row['impressions'], $row['clicks'], $row['ctr'], $row['share'],
                    $row['ad']->target_url,
                ])->all()
            )],
        ];
    }
}
