<?php

use App\Http\Controllers\SchoolAssetController;
use App\Http\Controllers\SchoolCoordinateController;
use App\Http\Controllers\SchoolPasswordController;
use App\Http\Controllers\SchoolPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:operator_sekolah'])
    ->prefix('operator-sekolah')
    ->name('school.')
    ->group(function () {
        Route::get('/', [SchoolPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/aset', [SchoolAssetController::class, 'index'])->name('assets.index');
        Route::post('/aset', [SchoolAssetController::class, 'store'])->name('assets.store');
        Route::get('/aset/perolehan/{acquisition}/perbaiki', [SchoolAssetController::class, 'editAcquisition'])->name('assets.acquisitions.edit');
        Route::put('/aset/perolehan/{acquisition}/perbaiki', [SchoolAssetController::class, 'resubmitAcquisition'])->name('assets.acquisitions.resubmit');
        Route::post('/aset/{asset}/perubahan', [SchoolAssetController::class, 'storeChangeRequest'])->name('assets.changes.store');
        Route::get('/aset/dokumen/{document}', [SchoolAssetController::class, 'downloadDocument'])->name('assets.documents.download');
        Route::get('/profil', [SchoolPortalController::class, 'profile'])->name('profile.edit');
        Route::put('/profil/kata-sandi', [SchoolPasswordController::class, 'update'])->name('password.update');
        Route::post('/pengingat-kata-sandi/tunda', [SchoolPasswordController::class, 'dismissReminder'])->name('password.reminder.dismiss');
        Route::post('/profil/koordinat', [SchoolCoordinateController::class, 'store'])->name('coordinates.store');
    });
