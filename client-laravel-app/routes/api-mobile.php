<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileVolunteeringController;

/*
|--------------------------------------------------------------------------
| Mobile API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes specifically designed for mobile applications.
| These routes handle GPS-based features, offline sync, push notifications,
| and mobile-optimized volunteering functionality.
|
*/

Route::middleware(['auth:sanctum'])->prefix('mobile')->group(function () {
    
    // Configuration and device info
    Route::get('/config', [MobileVolunteeringController::class, 'getConfig']);
    Route::get('/device-info', [MobileVolunteeringController::class, 'getDeviceInfo']);
    
    // Location-based features
    Route::get('/nearby-opportunities', [MobileVolunteeringController::class, 'getNearbyOpportunities']);
    
    // Time logging
    Route::post('/time-log', [MobileVolunteeringController::class, 'createTimeLog']);
    
    // Check-in/Check-out system
    Route::post('/check-in', [MobileVolunteeringController::class, 'checkIn']);
    Route::post('/check-out', [MobileVolunteeringController::class, 'checkOut']);
    Route::get('/active-check-ins', [MobileVolunteeringController::class, 'getActiveCheckIns']);
    
    // Offline functionality
    Route::get('/offline-data', [MobileVolunteeringController::class, 'getOfflineData']);
    Route::post('/sync', [MobileVolunteeringController::class, 'syncOfflineData']);
    
    // Push notifications
    Route::post('/fcm-token', [MobileVolunteeringController::class, 'updateFcmToken']);
    
});