<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\AdMetric;
use App\Support\BotDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Pencatatan impresi & klik iklan. Agar laporan ke pengiklan jujur dan tidak bisa digelembungkan:
 * bot tidak dihitung, klik dihitung sekali per pengunjung per iklan per hari, dan impresi dibatasi
 * IMPRESSION_DAILY_CAP per pengunjung per iklan per hari. Pengunjung (pseudonim) = hash IP + tanggal.
 */
class AdTrackingController extends Controller
{
    private const IMPRESSION_DAILY_CAP = 30;

    /**
     * Record an impression for an advertisement.
     */
    public function recordImpression(Request $request, Advertisement $advertisement): JsonResponse
    {
        if ($advertisement->effective_status === 'active' && ! BotDetector::isBot($request)) {
            $visitor = $this->visitorHash($request);

            if ($this->countToday("ad-imp:{$advertisement->id}:{$visitor}") <= self::IMPRESSION_DAILY_CAP) {
                AdMetric::create([
                    'advertisement_id' => $advertisement->id,
                    'type' => 'impression',
                    'ip_hash' => $visitor,
                    'created_at' => now(),
                ]);
            }
        }

        return response()->json(['status' => 'recorded']);
    }

    /**
     * Track a click on an advertisement and redirect to its target URL.
     */
    public function trackClick(Request $request, Advertisement $advertisement): RedirectResponse
    {
        if ($advertisement->effective_status === 'active' && ! BotDetector::isBot($request)) {
            $visitor = $this->visitorHash($request);

            if ($this->countToday("ad-click:{$advertisement->id}:{$visitor}") === 1) {
                AdMetric::create([
                    'advertisement_id' => $advertisement->id,
                    'type' => 'click',
                    'ip_hash' => $visitor,
                    'created_at' => now(),
                ]);
            }
        }

        return redirect()->away($advertisement->target_url);
    }

    /** Hash IP + tanggal: cukup untuk membedakan pengunjung per hari tanpa menyimpan IP aslinya. */
    private function visitorHash(Request $request): string
    {
        return hash('sha256', $request->ip() . now()->toDateString());
    }

    /** Naikkan penghitung harian dan kembalikan nilainya (berlaku sampai akhir hari). */
    private function countToday(string $key): int
    {
        Cache::add($key, 0, now()->endOfDay());

        return (int) Cache::increment($key);
    }
}
