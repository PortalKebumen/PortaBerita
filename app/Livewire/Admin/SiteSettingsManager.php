<?php

namespace App\Livewire\Admin;

use App\Jobs\GenerateSitemap;
use App\Models\Article;
use App\Models\SiteSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SiteSettingsManager extends Component
{
    // Umum
    public string $siteName = '';
    public string $tagline = '';
    public string $editorialEmail = '';
    public string $phone = '';
    public string $address = '';
    public string $timezone = 'Asia/Jakarta';
    public string $locale = 'id';

    // SEO
    public string $metaTitle = '';
    public string $metaDescription = '';
    public string $gaId = '';
    public string $gscVerification = '';

    // Media sosial
    public string $facebook = '';
    public string $instagram = '';
    public string $xTwitter = '';
    public string $youtube = '';
    public string $whatsapp = '';

    // Gambar (lewat MediaPicker)
    public ?int $logoMediaId = null;
    public ?string $logoPreviewUrl = null;
    public bool $removeLogo = false;

    public ?int $ogMediaId = null;
    public ?string $ogPreviewUrl = null;
    public bool $removeOg = false;

    public bool $showResetModal = false;

    public function mount(): void
    {
        $this->authorize('settings.view');
        $this->loadFromModel();
    }

    public function rules(): array
    {
        return [
            'siteName' => 'required|string|max:100',
            'tagline' => 'nullable|string|max:160',
            'editorialEmail' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'timezone' => 'required|in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura',
            'locale' => 'required|in:id,en',

            'metaTitle' => 'nullable|string|max:70',
            'metaDescription' => 'nullable|string|max:160',
            'gaId' => ['nullable', 'regex:/^(G|GT|UA|AW)-[A-Za-z0-9\-]+$/'],
            'gscVerification' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-]+$/'],

            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'xTwitter' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'whatsapp' => ['nullable', 'regex:/^\+?[0-9\s\-]{8,20}$/'],

            'logoMediaId' => 'nullable|integer|exists:media,id',
            'ogMediaId' => 'nullable|integer|exists:media,id',
        ];
    }

    public function messages(): array
    {
        return [
            'siteName.required' => 'Nama situs wajib diisi.',
            'editorialEmail.email' => 'Format email redaksi tidak valid.',
            'metaTitle.max' => 'Meta title maksimal 70 karakter.',
            'metaDescription.max' => 'Meta description maksimal 160 karakter.',
            'gaId.regex' => 'Format Google Analytics ID tidak valid (contoh: G-ABC123XYZ).',
            'gscVerification.regex' => 'Isi hanya kode verifikasinya (tanpa tag <meta>).',
            'facebook.url' => 'Tautan Facebook harus berupa URL lengkap (https://...).',
            'instagram.url' => 'Tautan Instagram harus berupa URL lengkap (https://...).',
            'xTwitter.url' => 'Tautan X (Twitter) harus berupa URL lengkap (https://...).',
            'youtube.url' => 'Tautan YouTube harus berupa URL lengkap (https://...).',
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid (contoh: +62 812-3456-7890).',
        ];
    }

    #[On('media-picker-selected')]
    public function handleMediaSelected(string $target, array $media): void
    {
        if ($target === 'site-logo') {
            $this->logoMediaId = $media['id'] ?? null;
            $this->logoPreviewUrl = $media['url'] ?? null;
            $this->removeLogo = false;
        }

        if ($target === 'site-og') {
            $this->ogMediaId = $media['id'] ?? null;
            $this->ogPreviewUrl = $media['url'] ?? null;
            $this->removeOg = false;
        }
    }

    public function clearLogo(): void
    {
        $this->logoMediaId = null;
        $this->logoPreviewUrl = null;
        $this->removeLogo = true;
    }

    public function clearOg(): void
    {
        $this->ogMediaId = null;
        $this->ogPreviewUrl = null;
        $this->removeOg = true;
    }

    public function save(): void
    {
        $this->authorize('settings.update');

        // Boleh paste seluruh tag <meta ... content="KODE">: ambil kodenya saja.
        $this->gscVerification = $this->normalizeVerification($this->gscVerification);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('settings-focus-tab', tab: $this->tabForField((string) array_key_first($e->errors())));
            $this->dispatch('flash-message', type: 'error', text: 'Pengaturan belum bisa disimpan. Periksa isian yang ditandai merah.');
            throw $e;
        }

        try {
            $current = SiteSetting::values();

            $logo = $this->resolveImage((string) ($current['logo'] ?? ''), $this->logoMediaId, $this->removeLogo, 'logo');
            $og = $this->resolveImage((string) ($current['og_image'] ?? ''), $this->ogMediaId, $this->removeOg, 'og');

            SiteSetting::setMany([
                'site_name' => $this->siteName,
                'tagline' => $this->tagline ?: null,
                'editorial_email' => $this->editorialEmail ?: null,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
                'timezone' => $this->timezone,
                'locale' => $this->locale,
                'logo' => $logo,
                'meta_title' => $this->metaTitle ?: null,
                'meta_description' => $this->metaDescription ?: null,
                'og_image' => $og,
                'ga_id' => $this->gaId ?: null,
                'gsc_verification' => $this->gscVerification ?: null,
                'facebook' => $this->facebook ?: null,
                'instagram' => $this->instagram ?: null,
                'x_twitter' => $this->xTwitter ?: null,
                'youtube' => $this->youtube ?: null,
                'whatsapp' => $this->whatsapp ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('flash-message', type: 'error', text: 'Gagal menyimpan pengaturan. Silakan coba lagi.');

            return;
        }

        $this->loadFromModel();
        $this->dispatch('flash-message', type: 'success', text: 'Pengaturan berhasil disimpan.');
    }

    public function cancelChanges(): void
    {
        $this->resetErrorBag();
        $this->loadFromModel();
        $this->dispatch('flash-message', type: 'info', text: 'Perubahan dibatalkan.');
    }

    // ==================== SITEMAP ====================

    #[Computed]
    public function sitemapInfo(): array
    {
        $file = public_path('sitemap.xml');
        $exists = is_file($file);

        return [
            'url' => url('/sitemap.xml'),
            'exists' => $exists,
            'updated' => $exists
                ? Carbon::createFromTimestamp(filemtime($file))->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i')
                : null,
        ];
    }

    public function regenerateSitemap(): void
    {
        $this->authorize('settings.update');

        try {
            GenerateSitemap::dispatchSync();
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('flash-message', type: 'error', text: 'Gagal memperbarui sitemap. Cek log untuk detailnya.');

            return;
        }

        unset($this->sitemapInfo);
        $this->dispatch('flash-message', type: 'success', text: 'Sitemap berhasil diperbarui.');
    }

    // ==================== ZONA BAHAYA ====================

    public function clearCache(): void
    {
        $this->authorize('settings.update');

        Cache::flush();
        Artisan::call('view:clear');

        $this->dispatch('flash-message', type: 'success', text: 'Cache situs berhasil dibersihkan.');
    }

    public function exportData()
    {
        $this->authorize('settings.update');

        $filename = 'export-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () {
            $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

            echo '{"exported_at":' . json_encode(now()->toIso8601String());
            echo ',"settings":' . json_encode(SiteSetting::values(), $flags);

            echo ',"users":[';
            $first = true;
            User::query()->with('roles:id,name')->orderBy('id')->chunk(500, function ($users) use (&$first, $flags) {
                foreach ($users as $u) {
                    echo ($first ? '' : ',') . json_encode([
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'roles' => $u->roles->pluck('name'),
                        'created_at' => $u->created_at?->toIso8601String(),
                    ], $flags);
                    $first = false;
                }
            });

            echo '],"articles":[';
            $first = true;
            Article::query()->with(['author:id,name,email', 'category:id,name,slug'])->orderBy('id')
                ->chunk(500, function ($articles) use (&$first, $flags) {
                    foreach ($articles as $a) {
                        echo ($first ? '' : ',') . json_encode([
                            'id' => $a->id,
                            'title' => $a->title,
                            'slug' => $a->slug,
                            'excerpt' => $a->excerpt,
                            'content' => $a->content,
                            'status' => $a->status->value,
                            'category' => $a->category?->name,
                            'author' => $a->author?->email,
                            'is_breaking' => (bool) $a->is_breaking,
                            'is_advertorial' => (bool) $a->is_advertorial,
                            'published_at' => $a->published_at?->toIso8601String(),
                            'created_at' => $a->created_at?->toIso8601String(),
                        ], $flags);
                        $first = false;
                    }
                });
            echo ']}';
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function openResetModal(): void
    {
        $this->authorize('settings.update');
        $this->showResetModal = true;
    }

    public function closeResetModal(): void
    {
        $this->showResetModal = false;
    }

    public function resetToDefaults(): void
    {
        $this->authorize('settings.update');

        $current = SiteSetting::values();
        foreach (['logo', 'og_image'] as $key) {
            if (! empty($current[$key])) {
                Storage::disk('public')->delete($current[$key]);
            }
        }

        SiteSetting::reset();
        $this->loadFromModel();
        $this->showResetModal = false;

        $this->dispatch('flash-message', type: 'success', text: 'Pengaturan dikembalikan ke nilai bawaan.');
    }

    // ==================== HELPER ====================

    private function loadFromModel(): void
    {
        $s = SiteSetting::values();

        $this->siteName = (string) $s['site_name'];
        $this->tagline = (string) $s['tagline'];
        $this->editorialEmail = (string) $s['editorial_email'];
        $this->phone = (string) $s['phone'];
        $this->address = (string) $s['address'];
        $this->timezone = (string) ($s['timezone'] ?: 'Asia/Jakarta');
        $this->locale = (string) ($s['locale'] ?: 'id');

        $this->metaTitle = (string) $s['meta_title'];
        $this->metaDescription = (string) $s['meta_description'];
        $this->gaId = (string) $s['ga_id'];
        $this->gscVerification = (string) $s['gsc_verification'];

        $this->facebook = (string) $s['facebook'];
        $this->instagram = (string) $s['instagram'];
        $this->xTwitter = (string) $s['x_twitter'];
        $this->youtube = (string) $s['youtube'];
        $this->whatsapp = (string) $s['whatsapp'];

        $this->logoMediaId = null;
        $this->logoPreviewUrl = $s['logo_url'];
        $this->removeLogo = false;

        $this->ogMediaId = null;
        $this->ogPreviewUrl = $s['og_image_url'];
        $this->removeOg = false;
    }

    /** Salin media pilihan dari Pustaka Media (copy, jadi aslinya tetap ada). */
    private function resolveImage(string $currentPath, ?int $mediaId, bool $remove, string $prefix): ?string
    {
        $disk = Storage::disk('public');

        if ($mediaId && ($media = Media::find($mediaId))) {
            $ext = pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'png';
            $path = 'settings/' . $prefix . '-' . now()->timestamp . '.' . $ext;

            $disk->put($path, $media->stream());

            if ($currentPath !== '') {
                $disk->delete($currentPath);
            }

            return $path;
        }

        if ($remove) {
            if ($currentPath !== '') {
                $disk->delete($currentPath);
            }

            return null;
        }

        return $currentPath !== '' ? $currentPath : null;
    }

    private function normalizeVerification(string $value): string
    {
        $value = trim($value);

        if (preg_match('/content=["\']([^"\']+)["\']/i', $value, $m)) {
            return $m[1];
        }

        return $value;
    }

    private function tabForField(string $field): string
    {
        return match (true) {
            in_array($field, ['metaTitle', 'metaDescription', 'gaId', 'gscVerification', 'ogMediaId'], true) => 'seo',
            in_array($field, ['facebook', 'instagram', 'xTwitter', 'youtube', 'whatsapp'], true) => 'sosial',
            default => 'umum',
        };
    }

    public function render()
    {
        return view('livewire.admin.site-settings-manager');
    }
}