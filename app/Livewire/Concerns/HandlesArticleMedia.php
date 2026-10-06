<?php

namespace App\Livewire\Concerns;

use App\Models\Article;
use App\Models\LibraryMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Logika bersama untuk pemilihan media (gambar sampul & OG) di form Artikel.
 * Media fisik disalin ke koleksi artikel saat form disimpan (syncArticleMedia()),
 * bukan saat dipilih, agar tidak ada media yatim bila form dibatalkan.
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
     * Panggil SEBELUM menulis apa pun ke database. Menolak (403) bila media
     * terpilih dari Pustaka Media bukan milik user dan user tidak punya media.view-any.
     */
    protected function authorizeArticleMedia(): void
    {
        foreach ([$this->featured_image_id, $this->og_image_id] as $mediaId) {
            $libraryMedia = $this->findLibraryMedia($mediaId);

            if ($libraryMedia) {
                $this->authorizeLibraryMedia($libraryMedia);
            }
        }
    }

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

        // Koleksi singleFile() otomatis mengganti file lama setelah yang baru berhasil ditambahkan.
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
            return null; // mis. media milik artikel itu sendiri (sudah terpasang) — tidak perlu disalin ulang
        }

        /** @var LibraryMedia|null $libraryMedia */
        $libraryMedia = $media->model;

        return $libraryMedia;
    }

    private function authorizeLibraryMedia(LibraryMedia $libraryMedia): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Anda harus login untuk menggunakan gambar ini.');
        }

        if (Gate::forUser($user)->check('media.view-any')) {
            return;
        }

        abort_unless(
            Gate::forUser($user)->check('media.view-own') && (int) $libraryMedia->uploaded_by === (int) $user->id,
            403,
            'Anda tidak punya izin menggunakan gambar ini.'
        );
    }
}