<?php

use Illuminate\Support\Facades\Route;

use Inertia\Inertia;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dev-components', function () {
    return Inertia::render('DevComponents');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AccountCodeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\RbaDocumentController;
use App\Http\Controllers\RbaDetailController;

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // =========================================================
    // Manajemen User & Sistem (hanya super-admin)
    // =========================================================
    Route::middleware('permission:manage users')->group(function () {
        Route::resource('users', UserController::class);
    });

    Route::middleware('permission:manage roles')->group(function () {
        Route::resource('roles', RoleController::class);
    });

    Route::middleware('permission:view activity logs')->group(function () {
        Route::get('/activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    Route::middleware('permission:manage settings')->group(function () {
        Route::resource('settings', SettingController::class)->only(['index', 'store']);
    });

    Route::middleware('permission:manage budget revision')->group(function () {
        Route::post('/settings/replikasi', [SettingController::class, 'buatReplikasi'])->name('settings.replikasi');
        Route::post('/settings/active-version', [SettingController::class, 'setActiveVersion'])->name('settings.active-version');
        Route::delete('/settings/version/{version}', [SettingController::class, 'destroyVersion'])->name('settings.destroy-version');
    });

    // Budget year selector dapat diakses semua user yang sudah login
    Route::post('/settings/budget-year', [SettingController::class, 'setBudgetYear'])->name('settings.budget-year');

    // =========================================================
    // Master Data (minimal: view master data)
    // =========================================================
    Route::middleware('permission:view master data')->group(function () {
        Route::resource('account-codes', AccountCodeController::class)->only(['index', 'show']);
        Route::resource('vendors', VendorController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:manage master data')->group(function () {
        Route::resource('account-codes', AccountCodeController::class)->except(['index', 'show']);
        Route::resource('vendors', VendorController::class)->except(['index', 'show']);
    });

    // =========================================================
    // Modul RBA / Perencanaan (minimal: view rba)
    // =========================================================
    Route::middleware('permission:view rba')->group(function () {
        Route::get('/rba', [RbaDocumentController::class, 'index'])->name('rba.index');
        Route::get('/rba/{rbaDocument}', [RbaDetailController::class, 'builder'])->name('rba.builder');
    });

    Route::middleware('permission:manage rba')->group(function () {
        Route::post('/rba/documents', [RbaDocumentController::class, 'store'])->name('rba.documents.store');
        Route::post('/rba/{rbaDocument}/details', [RbaDetailController::class, 'store'])->name('rba.store');
        Route::put('/rba/details/{rbaDetail}', [RbaDetailController::class, 'update'])->name('rba.update');
        Route::delete('/rba/details/{rbaDetail}', [RbaDetailController::class, 'destroy'])->name('rba.destroy');
    });
});
