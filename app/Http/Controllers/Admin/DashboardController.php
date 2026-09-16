<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        return view('admin.dashboard', [
            'totalPengguna' => $user->can('users.view') ? User::count() : null,
            'recentActivities' => $recentActivities,
        ]);
    }
}
