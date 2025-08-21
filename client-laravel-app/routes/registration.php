<?php

use App\Http\Controllers\Client\RegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Registration Routes
|--------------------------------------------------------------------------
|
| These routes handle the multi-step registration workflow for users.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Main registration wizard
    Route::get('/registration', [RegistrationController::class, 'index'])
        ->name('registration.index');
    
    // Individual registration steps
    Route::get('/registration/step/{stepName}', [RegistrationController::class, 'step'])
        ->name('registration.step')
        ->where('stepName', 'basic_info|profile_details|interests|verification');
    
    // Process registration step
    Route::post('/registration/step/{stepName}', [RegistrationController::class, 'processStep'])
        ->name('registration.process-step')
        ->where('stepName', 'basic_info|profile_details|interests|verification');
    
    // Skip registration step
    Route::post('/registration/skip/{stepName}', [RegistrationController::class, 'skipStep'])
        ->name('registration.skip-step')
        ->where('stepName', 'interests');
    
    // AJAX endpoints
    Route::get('/registration/progress', [RegistrationController::class, 'progress'])
        ->name('registration.progress');
    
    Route::post('/registration/auto-save', [RegistrationController::class, 'autoSave'])
        ->name('registration.auto-save');
});

// Admin routes for registration analytics
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/registration/analytics', [RegistrationController::class, 'analytics'])
        ->name('admin.registration.analytics');
});