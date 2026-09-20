<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(): View
    {
        $todayStr = Carbon::today()->toDateString();
        $sevenDaysLater = Carbon::today()->addDays(7)->toDateString();

        // Iklan Aktif
        $totalIklanAktif = Advertisement::where('status', 'active')
            ->where('start_date', '<=', $todayStr)
            ->where('end_date', '>=', $todayStr)
            ->count();

        // Iklan yang akan berakhir dalam 7 hari
        $expiringAds = Advertisement::where('status', 'active')
            ->where('end_date', '>=', $todayStr)
            ->where('end_date', '<=', $sevenDaysLater)
            ->orderBy('end_date')
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'totalPengguna' => User::count(),
            'totalIklanAktif' => $totalIklanAktif,
            'expiringAds' => $expiringAds,
            'recentActivities' => Activity::latest()->take(4)->get(),
        ]);
    }
}