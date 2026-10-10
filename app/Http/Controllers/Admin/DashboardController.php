<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\ArticleStatus;
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

        // ===== Aktivitas =====
        $recentActivities = collect();

        if ($user->can('activity-log.view') && $user->canAny(['activity-log.view-any', 'activity-log.view-own'])) {
            $query = Activity::query()->with('causer');

            if (! $user->can('activity-log.view-any')) {
                $query->where('causer_type', $user->getMorphClass())->where('causer_id', $user->getKey());
            }

            $recentActivities = $query->latest()->take(4)->get();
        }

        // ===== Iklan =====
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

        // ===== Artikel =====
        $articleStats = null;
        $recentArticles = collect();
        $pendingReview = collect();

        if ($user->can('articles.view')) {
            // Penulis hanya melihat artikelnya sendiri (sama seperti list artikel)
            $base = Article::query();
            if (! $user->can('articles.view-any')) {
                $base->where('author_id', $user->getKey());
            }

            $counts = (clone $base)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $articleStats = [
                'total' => (int) $counts->sum(),
                'draft' => (int) ($counts[ArticleStatus::Draft->value] ?? 0),
                'submitted' => (int) ($counts[ArticleStatus::Submitted->value] ?? 0),
                'approved' => (int) ($counts[ArticleStatus::Approved->value] ?? 0),
                'rejected' => (int) ($counts[ArticleStatus::Rejected->value] ?? 0),
                'published' => (int) ($counts[ArticleStatus::Published->value] ?? 0),
            ];

            $recentArticles = (clone $base)
                ->with('author:id,name')
                ->latest()
                ->take(5)
                ->get();

            // Antrean review untuk redaktur: yang paling lama menunggu di atas
            if ($user->can('articles.approve')) {
                $pendingReview = Article::query()
                    ->where('status', ArticleStatus::Submitted->value)
                    ->with('author:id,name')
                    ->oldest('updated_at')
                    ->take(5)
                    ->get();
            }
        }

        return view('admin.dashboard', [
            'totalPengguna' => $user->can('users.view') ? User::count() : null,
            'totalIklanAktif' => $totalIklanAktif,
            'expiringAds' => $expiringAds,
            'recentActivities' => $recentActivities,
            'articleStats' => $articleStats,
            'recentArticles' => $recentArticles,
            'pendingReview' => $pendingReview,
        ]);
    }
}