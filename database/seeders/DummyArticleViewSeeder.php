<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyArticleViewSeeder extends Seeder
{
    /**
     * Tayangan harian dummy 90 hari terakhir untuk Laporan Analitik: ramai di hari-hari awal terbit
     * lalu menurun, sedikit lebih sepi di akhir pekan. Dilewati untuk artikel yang sudah punya data.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        mt_srand(2027); // angka acak tetap, hasil seeder selalu sama

        $start = today()->subDays(89);

        Article::query()
            ->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->whereNotExists(fn ($q) => $q->from('article_daily_views')->whereColumn('article_daily_views.article_id', 'articles.id'))
            ->orderBy('id')
            ->each(function (Article $article) use ($start) {
                $first = $article->published_at->copy()->startOfDay()->max($start);
                $peak = mt_rand(40, 260) * ($article->is_breaking ? 2 : 1);
                $rows = [];

                for ($day = $first->copy(), $age = 0; $day->lte(today()); $day->addDay(), $age++) {
                    $decay = max(0.04, exp(-$age / mt_rand(4, 12)));
                    $weekend = $day->isWeekend() ? 0.75 : 1;
                    $views = (int) round($peak * $decay * $weekend * mt_rand(80, 120) / 100);

                    if ($views > 0) {
                        $rows[] = ['article_id' => $article->id, 'date' => $day->toDateString(), 'views' => $views];
                    }
                }

                DB::table('article_daily_views')->insert($rows);

                // Total sepanjang masa minimal sebesar jumlah rekap harian
                $sum = array_sum(array_column($rows, 'views'));
                DB::table('articles')->where('id', $article->id)->where('views_count', '<', $sum)->update(['views_count' => $sum]);
            });
    }
}
