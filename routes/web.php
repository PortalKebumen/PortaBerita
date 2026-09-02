<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('artikel', fn () => view('admin.coming-soon', ['title' => 'Artikel']))->name('artikel.index');
    Route::get('kategori-tag', fn () => view('admin.coming-soon', ['title' => 'Kategori & Tag']))->name('kategori-tag.index');
    Route::get('media-library', fn () => view('admin.coming-soon', ['title' => 'Media Library']))->name('media-library.index');
    Route::get('iklan', fn () => view('admin.coming-soon', ['title' => 'Iklan']))->name('iklan.index');
    Route::get('pengguna-role', fn () => view('admin.coming-soon', ['title' => 'Pengguna & Role']))->name('pengguna-role.index');
    Route::get('activity-log', fn () => view('admin.coming-soon', ['title' => 'Activity Log']))->name('activity-log.index');
    Route::get('pengaturan', fn () => view('admin.coming-soon', ['title' => 'Pengaturan']))->name('pengaturan.index');
});