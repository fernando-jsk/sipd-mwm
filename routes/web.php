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

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::get('/activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    
    Route::resource('account-codes', AccountCodeController::class);
    Route::resource('vendors', VendorController::class);
    Route::resource('settings', SettingController::class)->only(['index', 'store']);
    Route::post('/settings/budget-year', [SettingController::class, 'setBudgetYear'])->name('settings.budget-year');

    // Modul RBA (Perencanaan)
    Route::get('/rba', [RbaDocumentController::class, 'index'])->name('rba.index');
    Route::post('/rba/documents', [RbaDocumentController::class, 'store'])->name('rba.documents.store');
    Route::get('/rba/{rbaDocument}', [RbaDetailController::class, 'builder'])->name('rba.builder');
    Route::post('/rba/{rbaDocument}/details', [RbaDetailController::class, 'store'])->name('rba.store');
    Route::put('/rba/details/{rbaDetail}', [RbaDetailController::class, 'update'])->name('rba.update');
    Route::delete('/rba/details/{rbaDetail}', [RbaDetailController::class, 'destroy'])->name('rba.destroy');
});
