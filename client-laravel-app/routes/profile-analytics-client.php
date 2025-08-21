<?php

use App\Http\Controllers\Client\ProfileAnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Analytics Routes (Client)
|--------------------------------------------------------------------------
|
| Routes for client-side profile analytics functionality
|
*/

Route::middleware(['auth', 'verified'])->prefix('profile/analytics')->name('profile.analytics.')->group(function () {
    
    // Main dashboard
    Route::get('/', [ProfileAnalyticsController::class, 'dashboard'])->name('dashboard');
    
    // API endpoints for AJAX requests
    Route::prefix('api')->name('api.')->group(function () {
        
        // Profile scoring
        Route::get('/score', [ProfileAnalyticsController::class, 'profileScore'])->name('score');
        Route::get('/score/history', [ProfileAnalyticsController::class, 'scoreHistory'])->name('score.history');
        
        // Behavioral analytics
        Route::get('/behavioral', [ProfileAnalyticsController::class, 'behavioralAnalytics'])->name('behavioral');
        Route::get('/activity-heatmap', [ProfileAnalyticsController::class, 'activityHeatmap'])->name('activity.heatmap');
        Route::get('/engagement-trends', [ProfileAnalyticsController::class, 'engagementTrends'])->name('engagement.trends');
        
        // Recommendations and insights
        Route::get('/recommendations', [ProfileAnalyticsController::class, 'recommendations'])->name('recommendations');
        Route::get('/comparative', [ProfileAnalyticsController::class, 'comparative'])->name('comparative');
        Route::get('/insights-summary', [ProfileAnalyticsController::class, 'insightsSummary'])->name('insights.summary');
        
        // Export functionality
        Route::post('/export', [ProfileAnalyticsController::class, 'export'])->name('export');
    });
});