<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'beranda'])->name('public.beranda');
Route::get('/kategori/{slug}', [PublicController::class, 'kategori'])->name('public.kategori');
Route::get('/penulis/{user}', [PublicController::class, 'byline'])->name('public.byline');
Route::get('/artikel/{slug}', [PublicController::class, 'artikel'])->name('public.artikel');

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

    // rute utama kategori & tag diarahin langsung ke CategoryController
    Route::get('kategori-tag', [CategoryController::class, 'index'])->name('kategori-tag.index');
    // Route::get('kategori-tag', fn () => view('admin.coming-soon', ['title' => 'Kategori & Tag']))->name('kategori-tag.index'); <- versi lawas

    Route::get('media-library', fn () => view('admin.coming-soon', ['title' => 'Media Library']))->name('media-library.index');
    Route::get('iklan', fn () => view('admin.coming-soon', ['title' => 'Iklan']))->name('iklan.index');
    Route::get('pengguna-role', fn () => view('admin.coming-soon', ['title' => 'Pengguna & Role']))->name('pengguna-role.index');
    Route::get('activity-log', fn () => view('admin.coming-soon', ['title' => 'Activity Log']))->name('activity-log.index');
    Route::get('pengaturan', fn () => view('admin.coming-soon', ['title' => 'Pengaturan']))->name('pengaturan.index');

    // resource rute untuk operasi CRUD
    Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit', 'index']);
    Route::resource('tags', TagController::class)->except(['create', 'show', 'edit']);
});