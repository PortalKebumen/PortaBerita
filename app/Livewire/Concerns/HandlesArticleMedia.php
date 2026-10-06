<?php

namespace App\Livewire\Concerns;

use App\Models\Article;
use App\Models\LibraryMedia;
use Livewire\Attributes\On;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Logika bersama untuk pemilihan media (gambar sampul & OG) di form Artikel.
 *
 * Menyimpan ID media yang dipilih plus URL pratinjaunya, sehingga:
 *  - pratinjau bisa langsung ditampilkan di form, dan
 *  - media fisik disalin ke koleksi artikel saat form disimpan
 *    (lihat syncArticleMedia()), bukan saat dipilih — agar tidak ada
 *    media yatim bila pengguna membatalkan form.
 */
trait HandlesArticleMedia
{
    public ?int $featured_image_id = null;
    public ?string $featured_image_url = null;

    public ?int $og_image_id = null;
    public ?string $og_image_url = null;

    #[On('media-picker-selected')]
    public function handleMediaSelected(string $target, array $media): void
    {
        $mediaId = $media['id'] ?? null;
        $url = $media['url'] ?? null;

        if ($target === 'article-featured') {
            $this->featured_image_id = $mediaId;
            $this->featured_image_url = $url;
        } elseif ($target === 'article-og') {
            $this->og_image_id = $mediaId;
            $this->og_image_url = $url;
        }
    }

    protected function resolveArticleMediaUrl(?int $mediaId): ?string
    {
        $libraryMedia = $this->findLibraryMedia($mediaId);

        return $libraryMedia
            ? $libraryMedia->getFirstMedia('library')?->getUrl('medium')
            : null;
    }

    /**
     * Salin media terpilih (gambar sampul/OG) dari Pustaka Media ke koleksi
     * media artikel. Media yang sudah ada diganti sepenuhnya (single file).
     */
    protected function syncArticleMedia(Article $article): void
    {
        $this->syncSingleCollection($article, 'featured', $this->featured_image_id);
        $this->syncSingleCollection($article, 'og', $this->og_image_id);
    }

    private function syncSingleCollection(Article $article, string $collection, ?int $mediaId): void
    {
        if (! $mediaId) {
            return;
        }

        $source = $this->findLibraryMedia($mediaId);
        $sourceMedia = $source?->getFirstMedia('library');

        if (! $sourceMedia) {
            return;
        }

        $article->clearMediaCollection($collection);
        $article->copyMedia($sourceMedia->getPath())
            ->usingFileName($sourceMedia->file_name)
            ->toMediaCollection($collection);
    }

    private function findLibraryMedia(?int $mediaId): ?LibraryMedia
    {
        if (! $mediaId) {
            return null;
        }

        /** @var Media|null $media */
        $media = Media::find($mediaId);

        if (! $media || $media->model_type !== LibraryMedia::class) {
            return null;
        }

        return $media->model;
    }
}
