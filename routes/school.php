<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| School operator UI preview routes
|--------------------------------------------------------------------------
|
| These routes render static UI samples only. They do not persist asset or
| profile changes and do not perform verification or approval.
| Protect these routes with authentication and role/ownership authorization
| before connecting real school data or write endpoints.
|
*/

Route::prefix('operator-sekolah')->name('school.')->group(function () {
    Route::view('/', 'pages.school.dashboard')->name('dashboard');
    Route::view('/aset', 'pages.school.assets.index')->name('assets.index');
    Route::view('/profil', 'pages.school.profile.edit')->name('profile.edit');
});
