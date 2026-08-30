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

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

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

        // Funding Sources
        Route::post('/settings/funding-sources', [SettingController::class, 'storeFundingSource'])->name('settings.funding-sources.store');
        Route::put('/settings/funding-sources/{fundingSource}', [SettingController::class, 'updateFundingSource'])->name('settings.funding-sources.update');
        Route::delete('/settings/funding-sources/{fundingSource}', [SettingController::class, 'destroyFundingSource'])->name('settings.funding-sources.destroy');

        // Clear Expenditures & Receipts
        Route::delete('/settings/clear-expenditures', [SettingController::class, 'clearExpenditures'])->name('settings.clear-expenditures');
        Route::delete('/settings/clear-receipts', [SettingController::class, 'clearReceipts'])->name('settings.clear-receipts');
    });

    Route::middleware('permission:manage budget revision')->group(function () {
        Route::post('/settings/replikasi', [SettingController::class, 'buatReplikasi'])->name('settings.replikasi');
        Route::post('/settings/active-version', [SettingController::class, 'setActiveVersion'])->name('settings.active-version');
        Route::delete('/settings/version/{version}', [SettingController::class, 'destroyVersion'])->name('settings.destroy-version');

        Route::post('/settings/import-rba', [\App\Http\Controllers\RbaImportController::class, 'import'])->name('settings.import-rba');
    });

    // Budget year selector dapat diakses semua user yang sudah login
    Route::post('/settings/budget-year', [SettingController::class, 'setBudgetYear'])->name('settings.budget-year');

    // =========================================================
    // Master Data (minimal: view master data)
    // =========================================================
    Route::middleware('permission:manage master data')->group(function () {
        Route::resource('account-codes', AccountCodeController::class)->except(['index', 'show']);
        Route::resource('vendors', VendorController::class)->except(['index', 'show']);
    });

    Route::middleware('permission:view master data')->group(function () {
        Route::resource('account-codes', AccountCodeController::class)->except(['show']);
        Route::resource('receipt-types', \App\Http\Controllers\ReceiptTypeController::class)->except(['show', 'create', 'edit']);
        Route::resource('settings', SettingController::class)->only(['index', 'store']);
        Route::resource('vendors', VendorController::class)->only(['index', 'show']);
    });

    // =========================================================
    // Modul RBA / Perencanaan (minimal: view rba)
    // =========================================================
    Route::middleware('permission:view rba')->group(function () {
        Route::get('/rba/pendapatan', [RbaDocumentController::class, 'index'])->name('rba.pendapatan');
        Route::get('/rba/belanja', [RbaDocumentController::class, 'index'])->name('rba.belanja');
        Route::get('/rba/{rbaDocument}', [RbaDetailController::class, 'builder'])->name('rba.builder');
    });

    Route::middleware('permission:manage rba')->group(function () {
        Route::post('/rba/documents', [RbaDocumentController::class, 'store'])->name('rba.documents.store');
        Route::put('/rba/documents/{rbaDocument}', [RbaDocumentController::class, 'update'])->name('rba.documents.update');
        Route::delete('/rba/documents/{rbaDocument}', [RbaDocumentController::class, 'destroy'])->name('rba.documents.destroy');
        Route::post('/rba/{rbaDocument}/details', [RbaDetailController::class, 'store'])->name('rba.store');
        Route::put('/rba/details/{rbaDetail}', [RbaDetailController::class, 'update'])->name('rba.update');
        Route::delete('/rba/details/{rbaDetail}', [RbaDetailController::class, 'destroy'])->name('rba.destroy');
    });

    // =========================================================
    // Modul Bendahara Penerimaan
    // =========================================================
    Route::post('/receipts/import', [\App\Http\Controllers\ReceiptController::class, 'import'])->name('receipts.import');
    Route::resource('receipts', \App\Http\Controllers\ReceiptController::class);
    Route::patch('/receipts/{receipt}/status', [\App\Http\Controllers\ReceiptController::class, 'updateStatus'])->name('receipts.status');
    Route::get('/receipts/{receipt}/print', [\App\Http\Controllers\ReceiptController::class, 'print'])->name('receipts.print');

    // =========================================================
    // Modul Bendahara / Pengeluaran (SPPD -> OPD -> SPD)
    // =========================================================
    Route::post('/expenditures/import', [\App\Http\Controllers\ExpenditureImportController::class, 'import'])->name('expenditures.import');
    Route::get('/expenditures/sppd', [\App\Http\Controllers\ExpenditureController::class, 'sppdIndex'])->name('expenditures.sppd');
    Route::get('/expenditures/opd', [\App\Http\Controllers\ExpenditureController::class, 'opdIndex'])->name('expenditures.opd');
    Route::get('/expenditures/spd', [\App\Http\Controllers\ExpenditureController::class, 'spdIndex'])->name('expenditures.spd');

    Route::get('/expenditures/{expenditure}/print-sppd', [\App\Http\Controllers\ExpenditureController::class, 'printSppd'])->name('expenditures.print-sppd');
    Route::get('/expenditures/{expenditure}/print-spm', [\App\Http\Controllers\ExpenditureController::class, 'printSpm'])->name('expenditures.print-spm');
    Route::get('/expenditures/{expenditure}/print-ringkasan', [\App\Http\Controllers\ExpenditureController::class, 'printRingkasan'])->name('expenditures.print-ringkasan');
    Route::get('/expenditures/{expenditure}/print-lembar-peneliti', [\App\Http\Controllers\ExpenditureController::class, 'printLembarPeneliti'])->name('expenditures.print-lembar-peneliti');
    Route::get('/expenditures/{expenditure}/print-surat-pengantar', [\App\Http\Controllers\ExpenditureController::class, 'printSuratPengantar'])->name('expenditures.print-surat-pengantar');
    Route::get('/expenditures/{expenditure}/print-surat-pernyataan', [\App\Http\Controllers\ExpenditureController::class, 'printSuratPernyataan'])->name('expenditures.print-surat-pernyataan');
    Route::get('/expenditures/{expenditure}/print-surat-verifikasi', [\App\Http\Controllers\ExpenditureController::class, 'printSuratVerifikasi'])->name('expenditures.print-surat-verifikasi');
    Route::get('/expenditures/{expenditure}/print-kwitansi', [\App\Http\Controllers\ExpenditureController::class, 'printKwitansi'])->name('expenditures.print-kwitansi');
    Route::get('/expenditures/{expenditure}/print-opd', [\App\Http\Controllers\ExpenditureController::class, 'printOpd'])->name('expenditures.print-opd');
    Route::get('/expenditures/{expenditure}/print-spd', [\App\Http\Controllers\ExpenditureController::class, 'printSpd'])->name('expenditures.print-spd');

    Route::resource('expenditures', \App\Http\Controllers\ExpenditureController::class);
    Route::patch('/expenditures/{expenditure}/status', [\App\Http\Controllers\ExpenditureController::class, 'updateStatus'])->name('expenditures.status');

    // =========================================================
    // Modul Akuntansi (Jurnal Umum)
    // =========================================================
    Route::post('journals/{journal}/post', [\App\Http\Controllers\JournalController::class, 'post'])->name('journals.post');
    Route::resource('journals', \App\Http\Controllers\JournalController::class);

    // Modul Akuntansi (Jurnal Penyesuaian)
    Route::post('adjustments/{adjustment}/post', [\App\Http\Controllers\AdjustmentController::class, 'post'])->name('adjustments.post');
    Route::resource('adjustments', \App\Http\Controllers\AdjustmentController::class);

    // =========================================================
    // Modul Laporan
    // =========================================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/lra', [\App\Http\Controllers\Report\LraController::class, 'index'])->name('lra.index');
        Route::get('/lra/data', [\App\Http\Controllers\Report\LraController::class, 'data'])->name('lra.data');

        Route::get('/lak', [\App\Http\Controllers\Report\LakController::class, 'index'])->name('lak.index');
        Route::get('/lak/data', [\App\Http\Controllers\Report\LakController::class, 'data'])->name('lak.data');

        Route::get('/ledger', [\App\Http\Controllers\Report\LedgerController::class, 'index'])->name('ledger.index');
        Route::get('/ledger/data', [\App\Http\Controllers\Report\LedgerController::class, 'data'])->name('ledger.data');

        Route::get('/trial-balance', [\App\Http\Controllers\Report\TrialBalanceController::class, 'index'])->name('trial-balance.index');
        Route::get('/trial-balance/data', [\App\Http\Controllers\Report\TrialBalanceController::class, 'data'])->name('trial-balance.data');

        Route::get('/opening-balance', [\App\Http\Controllers\Report\OpeningBalanceController::class, 'index'])->name('opening-balance.index');
        Route::get('/opening-balance/data', [\App\Http\Controllers\Report\OpeningBalanceController::class, 'data'])->name('opening-balance.data');
        Route::post('/opening-balance', [\App\Http\Controllers\Report\OpeningBalanceController::class, 'store'])->name('opening-balance.store');

        Route::get('/closing-entry', [\App\Http\Controllers\Report\ClosingEntryController::class, 'index'])->name('closing-entry.index');
        Route::get('/closing-entry/data', [\App\Http\Controllers\Report\ClosingEntryController::class, 'data'])->name('closing-entry.data');
        Route::post('/closing-entry', [\App\Http\Controllers\Report\ClosingEntryController::class, 'store'])->name('closing-entry.store');

        Route::get('/balance-sheet', [\App\Http\Controllers\Report\BalanceSheetController::class, 'index'])->name('balance-sheet.index');
        Route::get('/balance-sheet/data', [\App\Http\Controllers\Report\BalanceSheetController::class, 'data'])->name('balance-sheet.data');
    });
});

// HANYA UNTUK DEPLOYMENT AWAL / MAINTENANCE (Hapus atau beri proteksi setelah dipakai)
Route::get('/dev-artisan/{cmd}', function ($cmd) {
    if (request('key') !== 'secret') {
        abort(403, 'Unauthorized');
    }

    switch ($cmd) {
        case 'migrate':
            Artisan::call('migrate', ['--force' => true]);
            return 'Database Migrated Successfully!<br><pre>' . Artisan::output() . '</pre>';

        case 'db-seed':
            // Menjalankan DatabaseSeeder
            Artisan::call('db:seed', ['--force' => true]);
            return 'Database Seeded Successfully!<br><pre>' . Artisan::output() . '</pre>';

        case 'migrate-seed':
            // Menjalankan Migration sekaligus Seeder
            Artisan::call('migrate', ['--force' => true, '--seed' => true]);
            return 'Database Migrated & Seeded Successfully!<br><pre>' . Artisan::output() . '</pre>';

        case 'storage-link':
            Artisan::call('storage:link');
            return 'Storage Link Created!';

        case 'optimize-clear':
            Artisan::call('optimize:clear');
            return 'Cache Cleared!';

        default:
            return 'Command not allowed!';
    }
});
