<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MediaLibraryController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/media-library', [MediaLibraryController::class, 'index'])->name('media-library.index');
    Route::post('/media-library', [MediaLibraryController::class, 'store'])->name('media-library.store');
    Route::put('/media-library/{media}', [MediaLibraryController::class, 'update'])->name('media-library.update');
    Route::delete('/media-library/{media}', [MediaLibraryController::class, 'destroy'])->name('media-library.destroy');
});