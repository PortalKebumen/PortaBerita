<?php

namespace App\Livewire\Admin;

use App\Models\AdMetric;
use App\Models\Advertisement;
use App\Models\LibraryMedia;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Livewire\WithFileUploads;

class AdvertisementManager extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $placementFilter = 'all';
    public string $statusFilter = '';

    public bool $showAdModal = false;
    public ?int $editingAdId = null;

    public string $advertiserName = '';
    public string $advertiserContact = '';
    public string $targetUrl = '';
    public string $placement = 'header';
    public string $status = 'active';
    public string $startDate = '';
    public string $endDate = '';

    // Banner — sepenuhnya lewat Media Library (via MediaPicker)
    public ?int $bannerMediaId = null;
    public ?string $bannerPreviewUrl = null;
    public $directBannerFile = null; // Fallback untuk role tanpa akses Media Library (mis. Ads Manager)

    public bool $showDeleteModal = false;
    public ?int $deletingAdId = null;
    public string $deletingAdName = '';

    public function mount(): void
    {
        $this->startDate = Carbon::today()->toDateString();
        $this->endDate = Carbon::today()->addDays(14)->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPlacementFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[On('media-picker-selected')]
    public function handleMediaSelected(string $target, array $media): void
    {
        if ($target === 'ad-banner') {
            $this->bannerMediaId = $media['id'] ?? null;
            $this->bannerPreviewUrl = $media['url'] ?? null;
        }
    }

    #[Computed]
    public function stats(): array
    {
        $todayStr = Carbon::today()->toDateString();
        $startOfMonth = Carbon::today()->startOfMonth();
        $endOfMonth = Carbon::today()->endOfMonth();
        $sevenDaysLater = Carbon::today()->addDays(7)->toDateString();

        $activeAdsCount = Advertisement::where('status', 'active')
            ->where('start_date', '<=', $todayStr)
            ->where('end_date', '>=', $todayStr)
            ->count();

        $totalImpressionsMonth = AdMetric::where('type', 'impression')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $expiringSoonCount = Advertisement::where('status', 'active')
            ->where('end_date', '>=', $todayStr)
            ->where('end_date', '<=', $sevenDaysLater)
            ->count();

        return [
            'activeAdsCount' => $activeAdsCount,
            'totalImpressionsMonth' => Advertisement::formatMetricNumber($totalImpressionsMonth),
            'expiringSoonCount' => $expiringSoonCount,
        ];
    }

    #[Computed]
    public function advertisements(): LengthAwarePaginator
    {
        $todayStr = Carbon::today()->toDateString();

        $query = Advertisement::withCount([
            'metrics as impressions_count' => fn ($q) => $q->where('type', 'impression'),
            'metrics as clicks_count' => fn ($q) => $q->where('type', 'click'),
        ]);

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('advertiser_name', 'like', $term)
                  ->orWhere('advertiser_contact', 'like', $term)
                  ->orWhere('target_url', 'like', $term);
            });
        }

        if ($this->placementFilter !== 'all') {
            $query->where('placement', $this->placementFilter);
        }

        if (! empty($this->statusFilter)) {
            if ($this->statusFilter === 'active') {
                $query->where('status', 'active')
                    ->where('start_date', '<=', $todayStr)
                    ->where('end_date', '>=', $todayStr);
            } elseif ($this->statusFilter === 'scheduled') {
                $query->where('status', 'active')
                    ->where('start_date', '>', $todayStr);
            } elseif ($this->statusFilter === 'expired') {
                $query->where(function ($q) use ($todayStr) {
                    $q->where('status', 'expired')
                        ->orWhere(function ($sub) use ($todayStr) {
                            $sub->where('status', '!=', 'inactive')
                                ->where('end_date', '<', $todayStr);
                        });
                });
            } elseif ($this->statusFilter === 'inactive') {
                $query->where('status', 'inactive');
            }
        }

        return $query->latest()->paginate(10);
    }

    // ==================== CRUD ACTIONS ====================

    public function openAddModal(): void
    {
        $this->authorize('ads.create');

        $this->resetErrorBag();
        $this->editingAdId = null;
        $this->advertiserName = '';
        $this->advertiserContact = '';
        $this->targetUrl = '';
        $this->placement = 'header';
        $this->status = 'active';
        $this->startDate = Carbon::today()->toDateString();
        $this->endDate = Carbon::today()->addDays(14)->toDateString();
        $this->bannerMediaId = null;
        $this->bannerPreviewUrl = null;
        $this->directBannerFile = null;

        $this->showAdModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorize('ads.update');

        $ad = Advertisement::findOrFail($id);

        $this->resetErrorBag();
        $this->editingAdId = $ad->id;
        $this->advertiserName = $ad->advertiser_name;
        $this->advertiserContact = $ad->advertiser_contact ?? '';
        $this->targetUrl = $ad->target_url;
        $this->placement = $ad->placement;
        $this->status = $ad->status;
        $this->startDate = $ad->start_date ? $ad->start_date->toDateString() : '';
        $this->endDate = $ad->end_date ? $ad->end_date->toDateString() : '';
        $this->bannerMediaId = null;
        $this->bannerPreviewUrl = $ad->banner_url;
        $this->directBannerFile = null;

        $this->showAdModal = true;
    }

    public function save(): void
    {
        if ($this->editingAdId) {
            $this->authorize('ads.update');
        } else {
            $this->authorize('ads.create');
        }

        $validated = $this->validate([
            'advertiserName' => 'required|string|max:255',
            'advertiserContact' => 'nullable|string|max:255',
            'targetUrl' => 'required|url|max:2048',
            'placement' => 'required|in:header,sidebar,inline_artikel,footer',
            'status' => 'required|in:active,scheduled,inactive',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'bannerMediaId' => 'nullable|integer',
            'directBannerFile' => 'nullable|image|max:5120',
        ], [
            'advertiserName.required' => 'Nama pengiklan wajib diisi.',
            'targetUrl.required' => 'Target URL wajib diisi.',
            'targetUrl.url' => 'Format URL target tidak valid (gunakan https://).',
            'startDate.required' => 'Tanggal mulai wajib diisi.',
            'endDate.required' => 'Tanggal berakhir wajib diisi.',
            'endDate.after_or_equal' => 'Tanggal berakhir harus sama atau sesudah tanggal mulai.',
        ]);

        $payload = [
            'advertiser_name' => $validated['advertiserName'],
            'advertiser_contact' => $validated['advertiserContact'],
            'target_url' => $validated['targetUrl'],
            'placement' => $validated['placement'],
            'status' => $validated['status'],
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
        ];

        if ($this->editingAdId) {
            $ad = Advertisement::findOrFail($this->editingAdId);
            $ad->update($payload);
            $this->attachBanner($ad);

            $this->showAdModal = false;
            unset($this->advertisements);
            unset($this->stats);
            $this->dispatch('flash-message', type: 'success', text: 'Iklan berhasil diupdate');
        } else {
            $ad = Advertisement::create($payload);
            $this->attachBanner($ad);

            $this->showAdModal = false;
            unset($this->advertisements);
            unset($this->stats);
            $this->dispatch('flash-message', type: 'success', text: 'Iklan baru berhasil disimpan');
        }
    }

    /**
     * Salin media yang dipilih/diupload via MediaPicker menjadi banner iklan ini.
     * Pakai copy(), bukan move, supaya media aslinya tetap ada & reusable di Pustaka Media.
     */
    protected function attachBanner(Advertisement $ad): void
    {
        if ($this->bannerMediaId) {
            $media = Media::find($this->bannerMediaId);

            if ($media) {
                /** @var \App\Models\User $user */
                $user = Auth::user();

                if (! $user->can('media.view-any')) {
                    $libraryMedia = $media->model_type === LibraryMedia::class ? $media->model : null;

                    abort_unless(
                        $user->can('media.view-own') && $libraryMedia && $libraryMedia->uploaded_by === $user->getKey(),
                        403,
                        'Anda tidak punya izin menggunakan gambar ini sebagai banner.'
                    );
                }

                $oldBannerName = $ad->getFirstMedia('banner')?->file_name;
                $ad->clearMediaCollection('banner');
                $newMedia = $media->copy($ad, 'banner');

                $this->logBannerChange($ad, $oldBannerName, $newMedia->file_name);
                return;
            }
        }

        if ($this->directBannerFile) {
            $oldBannerName = $ad->getFirstMedia('banner')?->file_name;
            $ad->clearMediaCollection('banner');

            $newMedia = $ad->addMedia($this->directBannerFile->getRealPath())
                ->usingFileName($this->directBannerFile->getClientOriginalName())
                ->toMediaCollection('banner');

            $this->logBannerChange($ad, $oldBannerName, $newMedia->file_name);
        }
    }

    protected function logBannerChange(Advertisement $ad, ?string $oldName, string $newName): void
    {
        $activity = activity('advertisement')
            ->causedBy(Auth::user())
            ->performedOn($ad)
            ->event('updated')
            ->withProperties(['file_name' => $newName])
            ->log('Banner iklan "'.$ad->advertiser_name.'" '.($oldName ? 'diganti' : 'dipasang'));

        /** @var \Spatie\Activitylog\Models\Activity $activity */
        $activity->attribute_changes = [
            'old' => ['banner' => $oldName],
            'attributes' => ['banner' => $newName],
        ];
        $activity->save();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('ads.delete');

        $ad = Advertisement::findOrFail($id);
        $this->deletingAdId = $ad->id;
        $this->deletingAdName = $ad->advertiser_name;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('ads.delete');

        if (! $this->deletingAdId) {
            return;
        }

        $ad = Advertisement::findOrFail($this->deletingAdId);

        // Bersihkan file banner legacy (dari sebelum fitur ini pakai Media Library), kalau ada
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
            $legacyPath = public_path("storage/banners/ad-{$ad->id}.{$ext}");
            if (File::exists($legacyPath)) {
                File::delete($legacyPath);
            }
        }

        $ad->metrics()->delete();
        $ad->clearMediaCollection('banner');
        $ad->delete();

        $this->showDeleteModal = false;
        $this->deletingAdId = null;
        $this->deletingAdName = '';

        unset($this->advertisements);
        unset($this->stats);
        $this->dispatch('flash-message', type: 'success', text: 'Iklan berhasil dihapus');
    }

    public function closeModals(): void
    {
        $this->showAdModal = false;
        $this->showDeleteModal = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.advertisement-manager');
    }
}