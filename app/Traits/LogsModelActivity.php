<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Support\Facades\Auth;

/**
 * Trait standar pencatatan activity log untuk seluruh modul admin (PK-25).
 *
 * CARA PAKAI di model lain (Kategori, Media, Iklan, Artikel, dst.):
 *
 *   use App\Traits\LogsModelActivity;
 *
 *   class Category extends Model
 *   {
 *       use LogsModelActivity;
 *
 *       // WAJIB diisi per model: kolom mana yang dicatat before/after-nya
 *       protected array $logAttributes = ['name', 'slug', 'parent_id'];
 *
 *       // Opsional: label yang tampil di deskripsi log (default: nama class)
 *       protected string $logLabel = 'Kategori';
 *   }
 *
 * Yang otomatis tercatat tanpa kode tambahan:
 * - Pelaku       -> user yang sedang login (causer_id/causer_type)
 * - Waktu        -> created_at pada baris log
 * - Objek        -> subject_type/subject_id (model & id yang berubah)
 * - Aksi         -> created / updated / deleted
 * - Before/After -> tersimpan di kolom attribute_changes (key "old" dan "attributes")
 *                   Akses lewat: $activity->attribute_changes['old'] / ['attributes']

 * Untuk aksi penting yang BUKAN sekadar create/update/delete (misal
 * "artikel dipublikasikan", "role diberikan ke pengguna"), catat manual:
 *
 *   activity('artikel')
 *       ->causedBy(auth()->user())
 *       ->performedOn($article)
 *       ->withProperties(['status_lama' => 'draft', 'status_baru' => 'published'])
 *       ->log('Artikel dipublikasikan');
 */

trait LogsModelActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->logAttributes ?? $this->fillable)
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName($this->logName ?? strtolower(class_basename($this)));
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $label = $this->logLabel ?? class_basename($this);
        $actor = Auth::user()?->name ?? 'Sistem';

        return match ($eventName) {
            'created' => "{$label} baru dibuat oleh {$actor}",
            'updated' => "{$label} diperbarui oleh {$actor}",
            'deleted' => "{$label} dihapus oleh {$actor}",
            default => "{$label} {$eventName} oleh {$actor}",
        };
    }
}