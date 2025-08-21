<?php

use App\Http\Controllers\Client\SkillMatchingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Skill Matching Routes
|--------------------------------------------------------------------------
|
| Routes for skill and interest matching functionality
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Skill Matching Dashboard
    Route::get('/skills/matching', [SkillMatchingController::class, 'index'])
        ->name('skills.matching.index');
    
    // API Routes for AJAX requests
    Route::prefix('api/skills')->name('api.skills.')->group(function () {
        
        // Matching opportunities
        Route::get('/matching/opportunities', [SkillMatchingController::class, 'getMatchingOpportunities'])
            ->name('matching.opportunities');
        
        // Skill gap analysis
        Route::get('/gaps', [SkillMatchingController::class, 'getSkillGaps'])
            ->name('gaps');
        
        // Trending skills
        Route::get('/trending', [SkillMatchingController::class, 'getTrendingSkills'])
            ->name('trending');
        
        // Skill suggestions
        Route::get('/suggestions', [SkillMatchingController::class, 'getSkillSuggestions'])
            ->name('suggestions');
        
        // Content recommendations
        Route::get('/recommendations', [SkillMatchingController::class, 'getContentRecommendations'])
            ->name('recommendations');
        
        // Similar users
        Route::get('/similar-users', [SkillMatchingController::class, 'getSimilarUsers'])
            ->name('similar-users');
        
        // Skill endorsements
        Route::post('/skills/{skill}/endorsements/request', [SkillMatchingController::class, 'requestEndorsement'])
            ->name('endorsements.request');
        
        Route::get('/skills/{skill}/endorsers/potential', [SkillMatchingController::class, 'getPotentialEndorsers'])
            ->name('endorsers.potential');
        
        Route::post('/endorsements/{endorsement}/respond', [SkillMatchingController::class, 'respondToEndorsement'])
            ->name('endorsements.respond');
        
        Route::get('/skills/{skill}/endorsements/stats', [SkillMatchingController::class, 'getEndorsementStats'])
            ->name('endorsements.stats');
        
        // Skill import
        Route::post('/import', [SkillMatchingController::class, 'importSkills'])
            ->name('import');
        
        // Match explanation
        Route::get('/match-explanation', [SkillMatchingController::class, 'getMatchExplanation'])
            ->name('match-explanation');
    });
});