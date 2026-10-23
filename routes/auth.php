<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        return match ($user?->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'operator_sekolah' => redirect()->route('school.dashboard'),
            default => abort(403, 'Akun tidak memiliki peran yang valid.'),
        };
    }

    return view('welcome');
})->name('home');

Route::post('/login', [LoginController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
