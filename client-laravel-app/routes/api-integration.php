<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VolunteeringIntegrationController;

/*
|--------------------------------------------------------------------------
| Volunteering Integration API Routes
|--------------------------------------------------------------------------
|
| These routes handle external integrations for the volunteering system
| including organization dashboards, data exports, and social media.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1/volunteering')->group(function () {
    
    // Organization Statistics
    Route::get('/stats', [VolunteeringIntegrationController::class, 'getStats']);
    
    // Data API
    Route::get('/data', [VolunteeringIntegrationController::class, 'getData']);
    
    // Data Export
    Route::get('/export', [VolunteeringIntegrationController::class, 'export']);
    
    // Widget Data
    Route::get('/widgets/{widget}', [VolunteeringIntegrationController::class, 'getWidgetData']);
    
    // Social Media Integration
    Route::post('/social/generate', [VolunteeringIntegrationController::class, 'generateSocialContent']);
    
    // Event Management Sync
    Route::post('/sync/events', [VolunteeringIntegrationController::class, 'syncEvents']);
});

// Public API endpoints (with rate limiting)
Route::middleware(['throttle:60,1'])->prefix('v1/public/volunteering')->group(function () {
    
    // Public opportunity data (for external websites)
    Route::get('/opportunities', function () {
        return response()->json([
            'message' => 'Public API endpoint for volunteering opportunities',
            'documentation' => '/api/docs'
        ]);
    });
    
    // Organization public stats
    Route::get('/organizations/{id}/stats', function ($id) {
        return response()->json([
            'message' => 'Public stats for organization ' . $id,
            'documentation' => '/api/docs'
        ]);
    });
});