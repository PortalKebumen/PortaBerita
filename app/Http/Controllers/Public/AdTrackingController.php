<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\AdMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdTrackingController extends Controller
{
    /**
     * Record an impression for an advertisement.
     */
    public function recordImpression(Request $request, Advertisement $advertisement): JsonResponse
    {
        // Catat impresi hanya jika iklan berstatus aktif
        if ($advertisement->effective_status === 'active') {
            AdMetric::create([
                'advertisement_id' => $advertisement->id,
                'type' => 'impression',
                'ip_hash' => hash('sha256', $request->ip() . now()->toDateString()),
                'created_at' => now(),
            ]);
        }

        return response()->json(['status' => 'recorded']);
    }

    /**
     * Track a click on an advertisement and redirect to its target URL.
     */
    public function trackClick(Request $request, Advertisement $advertisement): RedirectResponse
    {
        AdMetric::create([
            'advertisement_id' => $advertisement->id,
            'type' => 'click',
            'ip_hash' => hash('sha256', $request->ip() . now()->toDateString()),
            'created_at' => now(),
        ]);

        return redirect()->away($advertisement->target_url);
    }
}
