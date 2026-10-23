<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SchoolCoordinateController;
use App\Http\Controllers\SchoolImportController;
use App\Http\Controllers\SchoolPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'admin'
            ? redirect()->route('dashboard')
            : redirect()->route('school.dashboard');
    }

    return view('welcome');
})->name('home');

Route::post('/login', [LoginController::class, 'store'])->middleware('guest')->name('login');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin/sekolah')->name('admin.schools.')->group(function () {
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

Route::middleware(['auth', 'role:operator_sekolah'])->prefix('operator-sekolah')->name('school.')->group(function () {
    Route::get('/', [SchoolPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/aset', [SchoolPortalController::class, 'assets'])->name('assets.index');
    Route::get('/profil', [SchoolPortalController::class, 'profile'])->name('profile.edit');
    Route::post('/profil/koordinat', [SchoolCoordinateController::class, 'store'])->name('coordinates.store');
});
