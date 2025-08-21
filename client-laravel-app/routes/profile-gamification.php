<?php

use App\Http\Controllers\Client\ProfileGamificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Gamification Routes
|--------------------------------------------------------------------------
|
| Routes for profile gamification features including achievements,
| scoring, leaderboards, and progress tracking.
|
*/

Route::middleware(['auth', 'verified'])->prefix('profile/gamification')->name('profile.gamification.')->group(function () {
    // Dashboard
    Route::get('/', [ProfileGamificationController::class, 'dashboard'])->name('dashboard');
    
    // Achievements
    Route::get('/achievements', [ProfileGamificationController::class, 'achievements'])->name('achievements');
    
    // Leaderboard
    Route::get('/leaderboard', [ProfileGamificationController::class, 'leaderboard'])->name('leaderboard');
    
    // Strength Analysis
    Route::get('/strength-analysis', [ProfileGamificationController::class, 'strengthAnalysis'])->name('strength-analysis');
    
    // API endpoints
    Route::post('/recalculate-score', [ProfileGamificationController::class, 'recalculateScore'])->name('recalculate-score');
    Route::get('/suggestions', [ProfileGamificationController::class, 'suggestions'])->name('suggestions');
    Route::get('/achievement-stats', [ProfileGamificationController::class, 'achievementStats'])->name('achievement-stats');
    Route::get('/completion-progress', [ProfileGamificationController::class, 'completionProgress'])->name('completion-progress');
});