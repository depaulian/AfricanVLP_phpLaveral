<?php

use App\Http\Controllers\Admin\DocumentVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Document Management Routes
|--------------------------------------------------------------------------
|
| These routes handle document verification and management for administrators.
|
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Document verification routes
    Route::prefix('documents')->name('documents.')->group(function () {
        
        // Verification queue management
        Route::prefix('verification')->name('verification.')->group(function () {
            Route::get('/', [DocumentVerificationController::class, 'index'])->name('index');
            Route::get('/{document}', [DocumentVerificationController::class, 'show'])->name('show');
            Route::post('/{document}/verify', [DocumentVerificationController::class, 'verify'])->name('verify');
            Route::post('/bulk-verify', [DocumentVerificationController::class, 'bulkVerify'])->name('bulk-verify');
            Route::get('/history', [DocumentVerificationController::class, 'history'])->name('history');
        });
        
        // Document management
        Route::get('/expiring', [DocumentVerificationController::class, 'expiring'])->name('expiring');
        Route::get('/expired', [DocumentVerificationController::class, 'expired'])->name('expired');
        Route::get('/{document}/download', [DocumentVerificationController::class, 'download'])->name('download');
        
        // Analytics and reporting
        Route::get('/statistics', [DocumentVerificationController::class, 'statistics'])->name('statistics');
        Route::post('/export-report', [DocumentVerificationController::class, 'exportReport'])->name('export-report');
    });
});