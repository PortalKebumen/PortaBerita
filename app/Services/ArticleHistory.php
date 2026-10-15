<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\SeoMeta;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

/**
 * Riwayat versi artikel untuk audit, dibangun dari Activity Log (tanpa tabel tambahan).
 *
 * Setiap log "updated" menyimpan nilai lama & baru dari kolom yang berubah. Isi artikel pada
 * suatu versi disusun ulang dengan berjalan mundur dari kondisi sekarang sambil menerapkan
 * nilai lama tiap log. Perubahan SEO & gambar (bukan kolom tabel articles) digabungkan ke log
 * yang sama dengan kunci "seo.*" / "featured_image" / "og_image" lewat recordExtras().
 */
class ArticleHistory
{
    /** Log artikel tidak ikut terhapus oleh "Bersihkan log lama" sebelum berumur sekian tahun. */
    public const RETENTION_YEARS = 2;

    /** Kolom yang ditampilkan di riwayat, berikut labelnya. */
    public const FIELDS = [
        'title' => 'Judul',
        'excerpt' => 'Ringkasan',
        'content' => 'Isi Artikel',
        'category_id' => 'Kategori',
        'author_id' => 'Penulis',
        'slug' => 'Slug / URL',
        'status' => 'Status',
        'is_breaking' => 'Breaking News',
        'is_advertorial' => 'Advertorial',
        'scheduled_at' => 'Jadwal Terbit',
        'published_at' => 'Tanggal Terbit',
        'featured_image' => 'Gambar Utama',
        'og_image' => 'Gambar OG',
        'seo.meta_title' => 'Meta Title',
        'seo.meta_description' => 'Meta Description',
        'seo.noindex' => 'No Index',
        'seo.nofollow' => 'No Follow',
    ];

    /** Kolom yang dikembalikan saat "Pulihkan versi ini"; status, slug, jadwal & gambar tetap lewat alur kerja. */
    public const RESTORABLE = ['title', 'excerpt', 'content', 'category_id', 'seo.meta_title', 'seo.meta_description'];

    /**
     * Kondisi tambahan (di luar kolom articles) yang ikut diaudit.
     *
     * @return array<string, mixed>
     */
    public function extrasSnapshot(Article $article): array
    {
        $article->unsetRelation('seoMeta')->unsetRelation('media');
        $seo = $article->seoMeta;

        return [
            'seo.meta_title' => $seo?->meta_title,
            'seo.meta_description' => $seo?->meta_description,
            'seo.noindex' => (bool) $seo?->noindex,
            'seo.nofollow' => (bool) $seo?->nofollow,
            'featured_image' => $article->getFirstMedia('featured')?->file_name,
            'og_image' => $article->getFirstMedia('og')?->file_name,
        ];
    }

    /**
     * Gabungkan perubahan SEO/gambar ke log simpan yang baru saja dibuat, atau buat log baru
     * bila kolom artikel sendiri tidak berubah.
     *
     * @param  array<string, mixed>  $before  hasil extrasSnapshot() sebelum disimpan
     */
    public function recordExtras(Article $article, array $before, int $sinceActivityId): void
    {
        $after = $this->extrasSnapshot($article);
        $changedKeys = array_keys(array_filter($after, fn ($value, $key) => $value !== ($before[$key] ?? null), ARRAY_FILTER_USE_BOTH));

        if ($changedKeys === []) {
            return;
        }

        $old = array_intersect_key($before, array_flip($changedKeys));
        $new = array_intersect_key($after, array_flip($changedKeys));

        $activity = Activity::query()
            ->where('subject_type', $article->getMorphClass())
            ->where('subject_id', $article->getKey())
            ->where('event', 'updated')
            ->where('id', '>', $sinceActivityId)
            ->latest('id')
            ->first();

        if ($activity) {
            $changes = collect($activity->attribute_changes)->toArray();
            $activity->attribute_changes = collect([
                'old' => array_merge($changes['old'] ?? [], $old),
                'attributes' => array_merge($changes['attributes'] ?? [], $new),
            ]);
            $activity->save();

            return;
        }

        activity($article->getActivitylogOptions()->logName)
            ->performedOn($article)
            ->causedBy(Auth::user())
            ->event('updated')
            ->withChanges(['old' => $old, 'attributes' => $new])
            ->log('Artikel diperbarui oleh ' . (Auth::user()?->name ?? 'Sistem'));
    }

    /**
     * Daftar versi terbaru lebih dulu. Tiap versi: activity, before & after (isi artikel sebelum/
     * sesudah log itu) dan changed (kolom yang berubah). Log alur kerja tanpa perubahan kolom
     * (mis. "Artikel disetujui") ikut tampil sebagai catatan.
     *
     * @return Collection<int, array{activity: Activity, before: array<string, mixed>, after: array<string, mixed>, changed: list<string>}>
     */
    public function versions(Article $article): Collection
    {
        $activities = Activity::query()
            ->with('causer')
            ->where('subject_type', $article->getMorphClass())
            ->where('subject_id', $article->getKey())
            ->orderByDesc('id')
            ->get();

        $state = $this->currentState($article);
        $versions = collect();

        foreach ($activities as $activity) {
            $changes = collect($activity->attribute_changes)->toArray();
            $old = array_intersect_key($changes['old'] ?? [], self::FIELDS);
            $new = array_intersect_key($changes['attributes'] ?? [], self::FIELDS);

            $after = $state;
            $before = $state;

            if ($activity->event === 'created') {
                // Sebelum dibuat belum ada isi apa pun
                foreach (array_keys($new) as $key) {
                    $before[$key] = null;
                }
            } else {
                foreach ($old as $key => $value) {
                    $before[$key] = $value;
                }
            }

            $changed = array_values(array_filter(
                array_keys(self::FIELDS),
                fn ($key) => (array_key_exists($key, $new) || array_key_exists($key, $old))
                    && $this->normalize($before[$key] ?? null) !== $this->normalize($after[$key] ?? null)
            ));

            $versions->push(compact('activity', 'before', 'after', 'changed'));
            $state = $before;
        }

        return $versions;
    }

    /**
     * Kembalikan kolom RESTORABLE ke isi setelah versi $activityId, atau ke isi sebelum versi itu
     * ($beforeChange = membatalkan perubahan tsb). Pemulihan dicatat sebagai versi baru.
     *
     * @return list<string> kolom yang benar-benar dikembalikan (kosong bila sudah sama)
     */
    public function restore(Article $article, int $activityId, bool $beforeChange = false): array
    {
        $version = $this->versions($article)->first(fn ($v) => $v['activity']->id === $activityId);
        abort_if($version === null, 404);

        $current = $this->currentState($article);
        $target = array_intersect_key($version[$beforeChange ? 'before' : 'after'], array_flip(self::RESTORABLE));
        $changedKeys = array_values(array_filter(
            self::RESTORABLE,
            fn ($key) => $this->normalize($target[$key] ?? null) !== $this->normalize($current[$key] ?? null)
        ));

        if ($changedKeys === []) {
            return [];
        }

        // Kategori yang sudah dihapus tidak bisa dipulihkan; judul & isi wajib ada, jadi nilai kosong dilewati
        $changedKeys = array_values(array_filter($changedKeys, fn ($key) => match ($key) {
            'category_id' => Category::whereKey($target[$key])->exists(),
            'title', 'content' => filled($target[$key] ?? null),
            default => true,
        }));

        if ($changedKeys === []) {
            return [];
        }

        DB::transaction(function () use ($article, $target, $changedKeys, $current, $version, $beforeChange) {
            $articleFields = [];
            $seoFields = [];
            foreach ($changedKeys as $key) {
                str_starts_with($key, 'seo.')
                    ? $seoFields[substr($key, 4)] = $target[$key]
                    : $articleFields[$key] = $target[$key];
            }

            // Log otomatis dimatikan sementara; pemulihan dicatat manual sebagai satu log yang jelas
            $article->disableLogging();
            try {
                if ($articleFields) {
                    $article->update($articleFields);
                }
                if ($seoFields) {
                    SeoMeta::updateOrCreate(['article_id' => $article->id], $seoFields);
                }
            } finally {
                $article->enableLogging();
            }

            $when = $version['activity']->created_at->translatedFormat('d M Y, H:i');
            $description = $beforeChange
                ? "Perubahan {$when} dibatalkan oleh "
                : "Artikel dipulihkan ke versi {$when} oleh ";
            activity($article->getActivitylogOptions()->logName)
                ->performedOn($article)
                ->causedBy(Auth::user())
                ->event('updated')
                ->withChanges([
                    'old' => array_intersect_key($current, array_flip($changedKeys)),
                    'attributes' => array_intersect_key($target, array_flip($changedKeys)),
                ])
                ->withProperties(['restored_from' => $version['activity']->id, 'restore_mode' => $beforeChange ? 'before' : 'after'])
                ->log($description . (Auth::user()?->name ?? 'Sistem'));
        });

        return $changedKeys;
    }

    /**
     * Nilai siap tampil untuk satu kolom (bukan untuk isi artikel, yang ditampilkan sebagai diff).
     */
    public function display(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($key) {
            'category_id' => Category::find($value)?->name ?? "Kategori #{$value} (dihapus)",
            'author_id' => User::find($value)?->name ?? "Pengguna #{$value}",
            'status' => ucfirst((string) $value),
            'is_breaking', 'is_advertorial', 'seo.noindex', 'seo.nofollow' => $value ? 'Ya' : 'Tidak',
            'scheduled_at', 'published_at' => rescue(fn () => Carbon::parse($value)->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i'), (string) $value, false),
            default => (string) $value,
        };
    }

    /** @return array<string, mixed> */
    private function currentState(Article $article): array
    {
        $fresh = $article->fresh() ?? $article;
        $state = [];
        foreach (array_keys(self::FIELDS) as $key) {
            if (! str_contains($key, '.') && ! in_array($key, ['featured_image', 'og_image'], true)) {
                $value = $fresh->getAttribute($key);
                $state[$key] = $value instanceof \BackedEnum ? $value->value : $value;
            }
        }

        return array_merge($state, $this->extrasSnapshot($fresh));
    }

    /** Samakan bentuk nilai (tanggal, boolean, angka) agar perbandingan tidak salah karena format. */
    private function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->utc()->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/', $value)) {
            return rescue(fn () => Carbon::parse($value)->utc()->format('Y-m-d H:i:s'), $value, false);
        }

        return (string) $value;
    }
}
