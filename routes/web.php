<?php

use App\Http\Controllers\DeployController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Token-protected post-deploy webhook for shared hosting (no SSH).
// Applies pending migrations + refreshes caches. Inert unless APP_ENV=production
// and DEPLOY_TOKEN is set. See deploy.md for usage.
Route::get('/_deploy/{token}', DeployController::class)
    ->middleware('throttle:6,1')
    ->name('deploy.run');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Batches
    Route::resource('batches', \App\Http\Controllers\BatchController::class);

    // Students
    Route::resource('students', \App\Http\Controllers\StudentController::class);
    Route::post('students/{student}/notes', [\App\Http\Controllers\StudentNoteController::class, 'store'])->name('students.notes.store');
    Route::delete('notes/{studentNote}', [\App\Http\Controllers\StudentNoteController::class, 'destroy'])->name('students.notes.destroy');
    Route::get('api/calculate-fee-plan', [\App\Http\Controllers\StudentController::class, 'calculateFeePlan'])->name('api.fee-plan.calculate');

    // Payments & Receipts
    Route::get('payments/snapshot', [\App\Http\Controllers\PaymentController::class, 'studentSnapshot'])->name('payments.snapshot');
    Route::get('payments/create', [\App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');
    Route::get('payments/{payment}/receipt', [\App\Http\Controllers\PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('payments/{payment}/receipt.pdf', [\App\Http\Controllers\PaymentController::class, 'receiptPdf'])->name('payments.pdf');
    Route::get('payments/{payment}/whatsapp', [\App\Http\Controllers\PaymentController::class, 'whatsappReceipt'])->name('payments.whatsapp');
    Route::resource('payments', \App\Http\Controllers\PaymentController::class)->except(['show']);

    // Monthly Ledger (matrix)
    Route::get('ledger', [\App\Http\Controllers\LedgerController::class, 'index'])->name('ledger.index');
    Route::get('ledger/cell', [\App\Http\Controllers\LedgerController::class, 'cell'])->name('ledger.cell');

    // Dues & Defaulters
    Route::get('dues', [\App\Http\Controllers\DuesController::class, 'index'])->name('dues.index');

    // Reports & Exports
    Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/monthly.pdf', [\App\Http\Controllers\ReportController::class, 'monthlyPdf'])->name('reports.monthly.pdf');
    Route::get('reports/batches.pdf', [\App\Http\Controllers\ReportController::class, 'batchPdf'])->name('reports.batches.pdf');
    Route::get('reports/export/{type}', [\App\Http\Controllers\ReportController::class, 'csv'])
        ->whereIn('type', ['students', 'payments', 'batches'])
        ->name('reports.export');

    // Expenses
    Route::get('expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Activity log
    Route::get('activity', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activity.index');

    // Settings & portal users
    Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/users', [\App\Http\Controllers\SettingController::class, 'storeUser'])->name('settings.users.store');
    Route::delete('settings/users/{user}', [\App\Http\Controllers\SettingController::class, 'destroyUser'])->name('settings.users.destroy');

    // Global Ctrl/Cmd+K search
    Route::get('search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
