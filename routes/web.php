<?php

use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Public\AdTrackingController;
use App\Http\Controllers\Public\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'beranda'])->name('public.beranda');
Route::get('/kategori/{parent}/{sub}', [PublicController::class, 'kategori'])->name('public.kategori.sub');
Route::get('/kategori/{slug}', [PublicController::class, 'kategori'])->name('public.kategori');
Route::get('/penulis/{user}', [PublicController::class, 'byline'])->name('public.byline');
Route::get('/artikel/{slug}', [PublicController::class, 'artikel'])->name('public.artikel');

// Public Ad Tracking routes
Route::post('/ads/{advertisement}/impression', [AdTrackingController::class, 'recordImpression'])->name('ads.impression');
Route::get('/ads/{advertisement}/click', [AdTrackingController::class, 'trackClick'])->name('ads.click');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'can:dashboard.view'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('artikel', fn () => view('admin.coming-soon', ['title' => 'Artikel']))->can('articles.view')->name('artikel.index');

    // rute utama kategori & tag diarahin langsung ke CategoryController
    Route::get('kategori-tag', [CategoryController::class, 'index'])->can('categories.view')->name('kategori-tag.index');

    Route::get('media-library', [MediaLibraryController::class, 'index'])->can('media.view')->name('media-library.index');
    Route::post('media-library', [MediaLibraryController::class, 'store'])->name('media-library.store');
    Route::put('media-library/{media}', [MediaLibraryController::class, 'update'])->name('media-library.update');
    Route::delete('media-library/{media}', [MediaLibraryController::class, 'destroy'])->name('media-library.destroy');
    Route::get('iklan', [AdvertisementController::class, 'index'])->can('ads.view')->name('iklan.index');
    Route::get('pengguna-role', fn () => view('admin.pengguna-role'))->can('users.view')->name('pengguna-role.index');
    Route::get('activity-log', fn () => view('admin.activity-log'))->can('activity-log.view')->name('activity-log.index');
    Route::get('pengaturan', fn () => view('admin.coming-soon', ['title' => 'Pengaturan']))->can('settings.view')->name('pengaturan.index');

    // resource rute untuk operasi CRUD kategori & tag
    Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit', 'index']);
    Route::resource('tags', TagController::class)->except(['create', 'show', 'edit']);
    Route::resource('advertisements', AdvertisementController::class)->except(['create', 'show', 'edit']);
});