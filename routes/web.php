<?php

use App\Http\Controllers\Admin\AnalyticsExportController;
use App\Http\Controllers\Admin\ArticleLifecycleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Public\AdTrackingController;
use App\Http\Controllers\Public\PublicController;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
| Urutan penting: /kategori/{parent}/{sub} harus di atas /kategori/{slug}.
*/
// robots.txt dinamis: production mengizinkan crawling + alamat sitemap, testing/staging (SEO_INDEXING=false) melarang semua
Route::get('/robots.txt', function () {
    $body = config('system.indexing')
        ? "User-agent: *\nDisallow:\n\nSitemap: ".url('sitemap.xml')."\n"
        : "User-agent: *\nDisallow: /\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

// Batas akses per IP: lihat AppServiceProvider::configureRateLimiting()
Route::controller(PublicController::class)->name('public.')->middleware('throttle:public')->group(function () {
    Route::get('/', 'beranda')->name('beranda');
    Route::get('/kategori/{parent}/{sub}', 'kategori')->name('kategori.sub');
    Route::get('/kategori/{slug}', 'kategori')->name('kategori');
    Route::get('/profile/{user}/{slug?}', 'byline')->whereNumber('user')->name('byline');
    Route::get('/penulis/{user}', fn (User $user) => redirect($user->profileUrl(), 301))->whereNumber('user');
    Route::get('/artikel/{slug}', 'artikel')->name('artikel');
    Route::get('/cari', 'cari')->middleware('throttle:search')->name('cari');
    Route::get('/halaman/{slug}', 'halaman')->name('halaman');
});

/*
|--------------------------------------------------------------------------
| Tracking iklan (publik)
|--------------------------------------------------------------------------
| TODO: dua set route ini fungsinya tumpang tindih (lihat catatan di chat).
| Setelah diputuskan, hapus salah satunya.
*/
Route::get('/ads/{advertisement}/click', [AdTrackingController::class, 'trackClick'])
    ->middleware('throttle:ads-click')
    ->name('ads.click');
Route::post('/ads/{advertisement}/impression', [AdTrackingController::class, 'recordImpression'])
    ->middleware('throttle:ads-impression')
    ->name('ads.impression');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
// Alamat login rahasia (config auth.login_path / ADMIN_LOGIN_PATH); /login & /forgot-password lama = 404
Route::middleware('guest')->prefix(config('auth.login_path'))->group(function () {
    // login, lupa password, reset password
    Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/', [AuthenticatedSessionController::class, 'store'])->middleware(['throttle:login', 'antispam']);

    Route::get('lupa-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('lupa-password', [PasswordResetLinkController::class, 'store'])->middleware(['throttle:password-reset', 'antispam'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware(['throttle:password-reset', 'antispam'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', \App\Http\Middleware\EnsurePasswordIsChanged::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Profil Saya: semua pengguna yang login, termasuk yang wajib mengganti kata sandi sementara
        Route::view('profil', 'admin.profil')->name('profil');
        // Panduan pengguna: isi menyesuaikan role masing-masing
        Route::view('panduan', 'admin.panduan')->name('panduan');
    });

Route::middleware(['auth', \App\Http\Middleware\EnsurePasswordIsChanged::class, 'can:dashboard.view'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('pencarian', GlobalSearchController::class)
            ->middleware('can:articles.view')
            ->name('pencarian');

        // Artikel
        Route::view('artikel', 'admin.artikel')
            ->can('articles.view')
            ->name('artikel.index');

        Route::view('artikel/create', 'admin.artikel-create')
            ->can('articles.create')
            ->name('artikel.create');

        Route::get('artikel/{article}/edit', fn (Article $article) => view('admin.artikel-edit', compact('article')))
            ->can('update', 'article')
            ->name('artikel.edit');

        // Alur kerja artikel (submit, approve, reject, publish, schedule, archive)
        Route::controller(ArticleLifecycleController::class)
            ->prefix('artikel/{article}')
            ->name('artikel.')
            ->group(function () {
                Route::post('submit', 'submit')->name('submit');
                Route::post('approve', 'approve')->name('approve');
                Route::post('reject', 'reject')->name('reject');
                Route::post('publish', 'publish')->name('publish');
                Route::post('schedule', 'schedule')->name('schedule');
                Route::post('archive', 'archive')->name('archive');
            });

        // Halaman Livewire lain
        Route::view('kategori-tag', 'admin.kategori-tag')->can('categories.view')->name('kategori-tag.index');
        Route::view('media-library', 'admin.media-library')->can('media.view')->name('media-library.index');
        Route::view('iklan', 'admin.iklan')->can('ads.view')->name('iklan.index');
        Route::view('pengguna-role', 'admin.pengguna-role')->can('users.view')->name('pengguna-role.index');
        Route::view('activity-log', 'admin.activity-log')->can('activity-log.view')->name('activity-log.index');
        Route::view('analitik', 'admin.analitik', ['title' => 'Laporan Analitik'])->can('view-analytics')->name('analitik.index');
        Route::get('analitik/ekspor', AnalyticsExportController::class)->can('analytics.export')->name('analitik.ekspor');
        Route::view('halaman', 'admin.halaman', ['title' => 'Halaman'])->can('pages.view')->name('halaman.index');
        Route::view('menu', 'admin.menu', ['title' => 'Menu'])->can('menus.view')->name('menu.index');
        Route::view('pengaturan', 'admin.pengaturan', ['title' => 'Pengaturan'])
            ->can('settings.view')
            ->name('pengaturan.index');

        // Hanya untuk pengembangan lokal, tidak terdaftar di staging/produksi
        if (app()->environment('local')) {
            Route::view('test-media-picker', 'admin.test-media-picker')->name('test-media-picker');
            Route::view('test-editor', 'admin.test-editor')->name('test-editor');
        }
    });
/*
|--------------------------------------------------------------------------
| 404 untuk alamat yang tidak terdaftar
|--------------------------------------------------------------------------
| Lewat route (bukan 404 bawaan router) agar middleware web/sesi berjalan, sehingga halaman 404
| mengenali pengguna yang sudah masuk: tampil di layout admin untuk /admin/*, layout publik selainnya.
*/
Route::fallback(fn () => abort(404));
