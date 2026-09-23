<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibraryMedia;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\Advertisement;
use App\Models\AdMetric;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdvertisementController extends Controller
{
    /**
     * Display list of advertisements, statistics, and filters.
     */
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // 1. KPI Widget Counts
        // Iklan Aktif: status 'active' dan berada dalam periode rentang start_date & end_date
        $activeAdsCount = Advertisement::where('status', 'active')
            ->where('start_date', '<=', $todayStr)
            ->where('end_date', '>=', $todayStr)
            ->count();

        // Total Impresi Bulan Ini
        $totalImpressionsMonth = AdMetric::where('type', 'impression')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Akan Berakhir < 7 Hari: status active, end_date antara hari ini dan 7 hari ke depan
        $sevenDaysLater = $today->copy()->addDays(7)->toDateString();
        $expiringSoonCount = Advertisement::where('status', 'active')
            ->where('end_date', '>=', $todayStr)
            ->where('end_date', '<=', $sevenDaysLater)
            ->count();

        // 2. Query Advertisements with Filters
        $query = Advertisement::withCount([
            'metrics as impressions_count' => fn ($q) => $q->where('type', 'impression'),
            'metrics as clicks_count' => fn ($q) => $q->where('type', 'click'),
        ]);

        // Filter: Search advertiser_name / target_url
        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('advertiser_name', 'like', "%{$search}%")
                    ->orWhere('advertiser_contact', 'like', "%{$search}%")
                    ->orWhere('target_url', 'like', "%{$search}%");
            });
        }

        // Filter: Placement
        if ($placement = $request->query('placement')) {
            if ($placement !== 'all') {
                $query->where('placement', $placement);
            }
        }

        // Filter: Status (active, scheduled, expired, inactive)
        if ($status = $request->query('status')) {
            if ($status === 'active') {
                $query->where('status', 'active')
                    ->where('start_date', '<=', $todayStr)
                    ->where('end_date', '>=', $todayStr);
            } elseif ($status === 'scheduled') {
                $query->where('status', 'active')
                    ->where('start_date', '>', $todayStr);
            } elseif ($status === 'expired') {
                $query->where(function ($q) use ($todayStr) {
                    $q->where('status', 'expired')
                        ->orWhere(function ($sub) use ($todayStr) {
                            $sub->where('status', '!=', 'inactive')
                                ->where('end_date', '<', $todayStr);
                        });
                });
            } elseif ($status === 'inactive') {
                $query->where('status', 'inactive');
            }
        }

        $advertisements = $query->latest()->paginate(10)->withQueryString();

        return view('admin.iklan', compact(
            'advertisements',
            'activeAdsCount',
            'totalImpressionsMonth',
            'expiringSoonCount'
        ));
    }

    /**
     * Store newly created advertisement.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'advertiser_name' => ['required', 'string', 'max:255'],
            'advertiser_contact' => ['nullable', 'string', 'max:255'],
            'target_url' => ['required', 'url', 'max:500'],
            'placement' => ['required', 'string', 'in:header,sidebar,inline_artikel,footer'],
            'status' => ['required', 'string', 'in:active,inactive,scheduled,expired'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'banner_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ]);

        $ad = Advertisement::create([
            'advertiser_name' => $validated['advertiser_name'],
            'advertiser_contact' => $validated['advertiser_contact'] ?? null,
            'target_url' => $validated['target_url'],
            'placement' => $validated['placement'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        if (! empty($validated['banner_media_id'])) {
            $this->attachBannerFromMedia($ad, (int) $validated['banner_media_id']);
        }

        return redirect()->route('admin.iklan.index')->with('success', 'Iklan berhasil ditambahkan!');
    }

    /**
     * Update existing advertisement.
     */
    public function update(Request $request, Advertisement $advertisement): RedirectResponse
    {
        $validated = $request->validate([
            'advertiser_name' => ['required', 'string', 'max:255'],
            'advertiser_contact' => ['nullable', 'string', 'max:255'],
            'target_url' => ['required', 'url', 'max:500'],
            'placement' => ['required', 'string', 'in:header,sidebar,inline_artikel,footer'],
            'status' => ['required', 'string', 'in:active,inactive,scheduled,expired'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'banner_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ]);

        $advertisement->update([
            'advertiser_name' => $validated['advertiser_name'],
            'advertiser_contact' => $validated['advertiser_contact'] ?? null,
            'target_url' => $validated['target_url'],
            'placement' => $validated['placement'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        if (! empty($validated['banner_media_id'])) {
            $this->attachBannerFromMedia($advertisement, (int) $validated['banner_media_id']);
        }

        return redirect()->route('admin.iklan.index')->with('success', 'Iklan berhasil diperbarui!');
    }

    /**
     * Delete advertisement and its banner and metrics.
     */
    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        // Hapus file banner dari storage jika ada
        $storageFiles = File::glob(storage_path("app/public/banners/ad-{$advertisement->id}.*"));
        foreach ($storageFiles as $f) {
            File::delete($f);
        }

        $publicFiles = File::glob(public_path("storage/banners/ad-{$advertisement->id}.*"));
        foreach ($publicFiles as $f) {
            File::delete($f);
        }

        $advertisement->delete();

        return redirect()->route('admin.iklan.index')->with('success', 'Iklan berhasil dihapus!');
    }

    protected function attachBannerFromMedia(Advertisement $advertisement, int $mediaId): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $media = Media::findOrFail($mediaId);

        if (! $user->can('media.view-any')) {
            $libraryMedia = $media->model_type === LibraryMedia::class ? $media->model : null;

            abort_unless(
                $user->can('media.view-own') && $libraryMedia && $libraryMedia->uploaded_by === $user->getKey(),
                403,
                'Anda tidak punya izin menggunakan gambar ini sebagai banner.'
            );
        }

        $oldBannerName = $advertisement->getFirstMedia('banner')?->file_name;

        $advertisement->clearMediaCollection('banner');
        $newMedia = $media->copy($advertisement, 'banner');

        $activity = activity('advertisement')
            ->causedBy($user)
            ->performedOn($advertisement)
            ->event('updated')
            ->withProperties(['file_name' => $newMedia->file_name])
            ->log('Banner iklan "'.$advertisement->advertiser_name.'" '.($oldBannerName ? 'diganti' : 'dipasang'));

        /** @var \Spatie\Activitylog\Models\Activity $activity */
        $activity->attribute_changes = [
            'old' => ['banner' => $oldBannerName],
            'attributes' => ['banner' => $newMedia->file_name],
        ];
        $activity->save();
    }
}
