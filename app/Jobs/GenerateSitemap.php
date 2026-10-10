<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $timeout = 300;
    public int $uniqueFor = 600;

    private const CHUNK = 10000;

    public function handle(): void
    {
        $files = [];

        // 1) Beranda + kategori
        $pages = Sitemap::create()->add(
            Url::create(url('/'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_HOURLY)
                ->setPriority(1.0)
        );

        Category::query()->whereNotNull('slug')->orderBy('id')->get(['id', 'slug'])
            ->each(function ($category) use ($pages) {
                $pages->add(
                    Url::create(url('/kategori/' . $category->slug))
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.6)
                );
            });

        $pages->writeToFile(public_path('sitemap-pages.xml'));
        $files[] = 'sitemap-pages.xml';

        // 2) Artikel terbit (kecuali noindex), dipecah per CHUNK
        $page = 1;

        Article::query()
            ->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now())
            ->whereDoesntHave('seoMeta', fn ($q) => $q->where('noindex', true))
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(self::CHUNK, function ($articles) use (&$files, &$page) {
                $sitemap = Sitemap::create();

                foreach ($articles as $article) {
                    $sitemap->add(
                        Url::create($article->publicUrl())
                            ->setLastModificationDate($article->updated_at)
                            ->setPriority(0.8)
                    );
                }

                $name = "sitemap-articles-{$page}.xml";
                $sitemap->writeToFile(public_path($name));
                $files[] = $name;
                $page++;
            });

        // 3) Hapus file artikel lama yang tidak terpakai lagi
        foreach (glob(public_path('sitemap-articles-*.xml')) ?: [] as $old) {
            if (! in_array(basename($old), $files, true)) {
                @unlink($old);
            }
        }

        // 4) Index
        $index = SitemapIndex::create();
        foreach ($files as $file) {
            $index->add(url('/' . $file));
        }
        $index->writeToFile(public_path('sitemap.xml'));

        Log::info('GenerateSitemap selesai: ' . count($files) . ' file sitemap.');
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateSitemap gagal: ' . $e->getMessage());
    }
}