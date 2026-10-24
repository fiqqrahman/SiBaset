<?php

use App\Http\Controllers\SchoolCoordinateController;
use App\Http\Controllers\SchoolPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:operator_sekolah'])
    ->prefix('operator-sekolah')
    ->name('school.')
    ->group(function () {
        Route::get('/', [SchoolPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/aset', [SchoolPortalController::class, 'assets'])->name('assets.index');
        Route::get('/profil', [SchoolPortalController::class, 'profile'])->name('profile.edit');
        Route::post('/profil/koordinat', [SchoolCoordinateController::class, 'store'])->name('coordinates.store');
    });
