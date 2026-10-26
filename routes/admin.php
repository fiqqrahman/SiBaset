<?php

use App\Http\Controllers\AssetAdminController;
use App\Http\Controllers\SchoolAssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SchoolCoordinateController;
use App\Http\Controllers\SchoolImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Backward-compatible URL for existing bookmarks.
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
        ->name('dashboard.legacy');

    Route::prefix('administrator')->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/notifikasi', [DashboardController::class, 'notifications'])->name('notifications');

        Route::prefix('aset')->name('assets.')->group(function () {
            Route::get('/', [AssetAdminController::class, 'index'])->name('index');
            Route::get('/sekolah/{school}/data', [AssetAdminController::class, 'schoolAssets'])->name('school-data');
            Route::get('/sekolah/{school}/perolehan', [AssetAdminController::class, 'schoolAcquisitions'])->name('school-acquisitions');
            Route::post('/perolehan/{acquisition}/review', [AssetAdminController::class, 'reviewAcquisition'])->name('acquisitions.review');
            Route::post('/perubahan/{changeRequest}/review', [AssetAdminController::class, 'reviewChangeRequest'])->name('changes.review');
            Route::post('/jenis', [AssetAdminController::class, 'storeType'])->name('types.store');
            Route::post('/jenis/{type}/toggle', [AssetAdminController::class, 'toggleType'])->name('types.toggle');
            Route::get('/dokumen/{document}', [SchoolAssetController::class, 'downloadDocument'])->name('documents.download');
        });

        Route::prefix('sekolah')->name('schools.')->group(function () {
            Route::get('/import', [SchoolImportController::class, 'index'])->name('import.index');
            Route::post('/import/preview', [SchoolImportController::class, 'preview'])->name('import.preview');
            Route::post('/import/confirm', [SchoolImportController::class, 'confirm'])->name('import.confirm');
            Route::delete('/import/preview', [SchoolImportController::class, 'cancel'])->name('import.cancel');

            Route::get('/koordinat', [SchoolCoordinateController::class, 'adminIndex'])->name('coordinates.index');
            Route::post('/koordinat/{school}/verify', [SchoolCoordinateController::class, 'verifySchool'])->name('coordinates.verify');
            Route::post('/pengajuan-koordinat/{coordinateRequest}/approve', [SchoolCoordinateController::class, 'approve'])->name('coordinate-requests.approve');
            Route::post('/pengajuan-koordinat/{coordinateRequest}/reject', [SchoolCoordinateController::class, 'reject'])->name('coordinate-requests.reject');
        });
    });
});
