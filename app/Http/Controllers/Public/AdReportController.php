<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdMetric;
use App\Models\Advertisement;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Laporan tayangan & klik untuk pengiklan, dibuka lewat tautan bertanda tangan (signed URL) dari
 * Admin > Iklan, tanpa perlu akun. Tautan berlaku sampai 90 hari setelah iklan berakhir.
 */
class AdReportController extends Controller
{
    public function __invoke(Advertisement $advertisement): View
    {
        $from = $advertisement->start_date->copy()->startOfDay();
        $to = min($advertisement->end_date->copy()->endOfDay(), now()->endOfDay());

        $rows = AdMetric::query()
            ->where('advertisement_id', $advertisement->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, type, COUNT(*) as total')
            ->groupBy('day', 'type')
            ->get()
            ->groupBy('day');

        $daily = $from->lte($to) ? collect(CarbonPeriod::create($from, $to->copy()->startOfDay()))
            ->map(function ($date) use ($rows) {
                $day = $rows->get($date->toDateString(), collect());
                $impressions = (int) $day->firstWhere('type', 'impression')?->total;
                $clicks = (int) $day->firstWhere('type', 'click')?->total;

                return ['date' => $date, 'impressions' => $impressions, 'clicks' => $clicks];
            })->reverse()->values() : collect();

        $impressions = $daily->sum('impressions');
        $clicks = $daily->sum('clicks');

        return view('public.ad-report', [
            'ad' => $advertisement,
            'daily' => $daily,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0,
            'placement' => Advertisement::PLACEMENTS[$advertisement->placement]['label'] ?? $advertisement->placement,
        ]);
    }
}
