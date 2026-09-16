<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Public\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'beranda'])->name('public.beranda');
Route::get('/kategori/{slug}', [PublicController::class, 'kategori'])->name('public.kategori');
Route::get('/penulis/{user}', [PublicController::class, 'byline'])->name('public.byline');
Route::get('/artikel/{slug}', [PublicController::class, 'artikel'])->name('public.artikel');

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
    Route::get('kategori-tag', fn () => view('admin.coming-soon', ['title' => 'Kategori & Tag']))->can('categories.view')->name('kategori-tag.index');
    Route::get('media-library', fn () => view('admin.coming-soon', ['title' => 'Media Library']))->can('media.view')->name('media-library.index');
    Route::get('iklan', fn () => view('admin.coming-soon', ['title' => 'Iklan']))->can('ads.view')->name('iklan.index');
    Route::get('pengguna-role', fn () => view('admin.coming-soon', ['title' => 'Pengguna & Role']))->can('users.view')->name('pengguna-role.index');
    Route::get('activity-log', fn () => view('admin.coming-soon', ['title' => 'Activity Log']))->can('activity-log.view')->name('activity-log.index');
    Route::get('pengaturan', fn () => view('admin.coming-soon', ['title' => 'Pengaturan']))->can('settings.view')->name('pengaturan.index');
});
