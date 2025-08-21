<?php

use App\Http\Controllers\Client\SecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Security Routes
|--------------------------------------------------------------------------
|
| Here are the routes for user security management including security
| dashboard, events, sessions, and security settings.
|
*/

Route::middleware(['auth', 'verified'])->prefix('profile/security')->name('profile.security.')->group(function () {
    // Security Dashboard
    Route::get('/', [SecurityController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-data', [SecurityController::class, 'dashboardData'])->name('dashboard.data');
    
    // Security Events
    Route::get('/events', [SecurityController::class, 'events'])->name('events');
    Route::post('/events/{event}/resolve', [SecurityController::class, 'resolveSecurityEvent'])->name('events.resolve');
    
    // Session Management
    Route::get('/sessions', [SecurityController::class, 'sessions'])->name('sessions');
    Route::post('/sessions/terminate', [SecurityController::class, 'terminateSession'])->name('sessions.terminate');
    Route::post('/sessions/terminate-all', [SecurityController::class, 'terminateAllOtherSessions'])->name('sessions.terminate-all');
    
    // Password Management
    Route::post('/password/change', [SecurityController::class, 'changePassword'])->name('password.change');
    Route::post('/password/check-strength', [SecurityController::class, 'checkPasswordStrength'])->name('password.check-strength');
    
    // Security Recommendations
    Route::get('/recommendations', [SecurityController::class, 'recommendations'])->name('recommendations');
    
    // Data Export
    Route::get('/export', [SecurityController::class, 'exportSecurityData'])->name('export');
});