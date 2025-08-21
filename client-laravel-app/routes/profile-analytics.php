<?php

use App\Http\Controllers\Admin\ProfileAnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Analytics Routes
|--------------------------------------------------------------------------
|
| Routes for profile analytics and insights dashboard
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('profile-analytics')->name('profile-analytics.')->group(function () {
        // Main dashboard
        Route::get('/', [ProfileAnalyticsController::class, 'index'])->name('index');
        
        // AJAX endpoints for analytics data
        Route::get('/user-engagement', [ProfileAnalyticsController::class, 'userEngagement'])->name('user-engagement');
        Route::get('/profile-completion', [ProfileAnalyticsController::class, 'profileCompletion'])->name('profile-completion');
        Route::get('/user-behavior', [ProfileAnalyticsController::class, 'userBehavior'])->name('user-behavior');
        Route::get('/demographics', [ProfileAnalyticsController::class, 'demographics'])->name('demographics');
        Route::get('/profile-performance', [ProfileAnalyticsController::class, 'profilePerformance'])->name('profile-performance');
        
        // Export and utility endpoints
        Route::get('/export', [ProfileAnalyticsController::class, 'export'])->name('export');
        Route::post('/clear-cache', [ProfileAnalyticsController::class, 'clearCache'])->name('clear-cache');
        Route::get('/realtime-updates', [ProfileAnalyticsController::class, 'realtimeUpdates'])->name('realtime-updates');
    });
});