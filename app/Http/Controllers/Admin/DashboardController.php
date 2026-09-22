<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $recentActivities = collect();

        if ($user->can('activity-log.view') && $user->canAny(['activity-log.view-any', 'activity-log.view-own'])) {
            $query = Activity::query()->with('causer');

            if (! $user->can('activity-log.view-any')) {
                $query->where('causer_type', $user->getMorphClass())->where('causer_id', $user->getKey());
            }

            $recentActivities = $query->latest()->take(4)->get();
        }

        $totalIklanAktif = null;
        $expiringAds = collect();

        if ($user->can('ads.view')) {
            $todayStr = Carbon::today()->toDateString();
            $sevenDaysLater = Carbon::today()->addDays(7)->toDateString();

            $totalIklanAktif = Advertisement::where('status', 'active')
                ->where('start_date', '<=', $todayStr)
                ->where('end_date', '>=', $todayStr)
                ->count();

            $expiringAds = Advertisement::where('status', 'active')
                ->where('end_date', '>=', $todayStr)
                ->where('end_date', '<=', $sevenDaysLater)
                ->orderBy('end_date')
                ->take(5)
                ->get();
        }

        return view('admin.dashboard', [
            'totalPengguna' => $user->can('users.view') ? User::count() : null,
            'totalIklanAktif' => $totalIklanAktif,
            'expiringAds' => $expiringAds,
            'recentActivities' => $recentActivities,
        ]);
    }
}