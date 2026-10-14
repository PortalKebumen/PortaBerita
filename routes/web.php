<?php

use App\Http\Controllers\Admin\ArticleLifecycleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Public\AdTrackingController;
use App\Http\Controllers\Public\PublicController;
use App\Models\Article;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
| Urutan penting: /kategori/{parent}/{sub} harus di atas /kategori/{slug}.
*/
Route::controller(PublicController::class)->name('public.')->group(function () {
    Route::get('/', 'beranda')->name('beranda');
    Route::get('/kategori/{parent}/{sub}', 'kategori')->name('kategori.sub');
    Route::get('/kategori/{slug}', 'kategori')->name('kategori');
    Route::get('/profile/{user}/{slug?}', 'byline')->whereNumber('user')->name('byline');
    Route::get('/penulis/{user}', fn (\App\Models\User $user) => redirect($user->profileUrl(), 301))->whereNumber('user');
    Route::get('/artikel/{slug}', 'artikel')->name('artikel');
    Route::get('/cari', 'cari')->name('cari');
});

/*
|--------------------------------------------------------------------------
| Tracking iklan (publik)
|--------------------------------------------------------------------------
| TODO: dua set route ini fungsinya tumpang tindih (lihat catatan di chat).
| Setelah diputuskan, hapus salah satunya.
*/
Route::get('/ads/{advertisement}/click', [AdTrackingController::class, 'trackClick'])
    ->middleware('throttle:60,1')
    ->name('ads.click');
Route::post('/ads/{advertisement}/impression', [AdTrackingController::class, 'recordImpression'])
    ->middleware('throttle:120,1')
    ->name('ads.impression');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // login, forgot-password, reset-password
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'can:dashboard.view'])
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
        Route::view('pengaturan', 'admin.pengaturan', ['title' => 'Pengaturan'])
            ->can('settings.view')
            ->name('pengaturan.index');

        // Hanya untuk pengembangan lokal, tidak terdaftar di staging/produksi
        if (app()->environment('local')) {
            Route::view('test-media-picker', 'admin.test-media-picker')->name('test-media-picker');
            Route::view('test-editor', 'admin.test-editor')->name('test-editor');
        }
    });