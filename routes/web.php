<?php

use App\Http\Controllers\Crm;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::prefix('gestao-crm')->name('crm.')->middleware('noindex')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Crm\LoginController::class, 'create'])->name('login');
        Route::post('login', [Crm\LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/', Crm\DashboardController::class)->name('dashboard');
        Route::post('logout', [Crm\LoginController::class, 'destroy'])->name('logout');
    });
});
