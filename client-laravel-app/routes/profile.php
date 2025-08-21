<?php

use App\Http\Controllers\Client\UserProfileController;
use App\Http\Controllers\Api\UserProfileApiController;
use App\Http\Controllers\Client\SecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
|
| Here are the routes for user profile management functionality.
| These routes handle both web and API endpoints for profile operations.
|
*/

// Web Routes for Profile Management
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Main profile routes
    Route::get('/profile', [UserProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [UserProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/upload-image', [UserProfileController::class, 'uploadImage'])->name('profile.upload-image');
    
    // Skills management
    Route::get('/profile/skills', [UserProfileController::class, 'skills'])->name('profile.skills');
    Route::post('/profile/skills', [UserProfileController::class, 'addSkill'])->name('profile.skills.add');
    Route::put('/profile/skills/{skill}', [UserProfileController::class, 'updateSkill'])->name('profile.skills.update');
    Route::delete('/profile/skills/{skill}', [UserProfileController::class, 'removeSkill'])->name('profile.skills.remove');
    
    // Volunteering interests
    Route::get('/profile/interests', [UserProfileController::class, 'interests'])->name('profile.interests');
    Route::post('/profile/interests', [UserProfileController::class, 'addInterest'])->name('profile.interests.add');
    Route::delete('/profile/interests/{interest}', [UserProfileController::class, 'removeInterest'])->name('profile.interests.remove');
    
    // Volunteering history
    Route::get('/profile/history', [UserProfileController::class, 'history'])->name('profile.history');
    Route::get('/profile/history/create', [UserProfileController::class, 'createHistory'])->name('profile.history.create');
    Route::post('/profile/history', [UserProfileController::class, 'storeHistory'])->name('profile.history.store');
    Route::get('/profile/history/{history}/edit', [UserProfileController::class, 'editHistory'])->name('profile.history.edit');
    Route::put('/profile/history/{history}', [UserProfileController::class, 'updateHistory'])->name('profile.history.update');
    
    // Documents management
    Route::get('/profile/documents', [UserProfileController::class, 'documents'])->name('profile.documents');
    Route::get('/profile/documents/create', [UserProfileController::class, 'createDocument'])->name('profile.documents.create');
    Route::post('/profile/documents', [UserProfileController::class, 'uploadDocument'])->name('profile.documents.store');
    Route::get('/profile/documents/{document}', [UserProfileController::class, 'showDocument'])->name('profile.documents.show');
    Route::get('/profile/documents/{document}/download', [UserProfileController::class, 'downloadDocument'])->name('profile.documents.download');
    Route::delete('/profile/documents/{document}', [UserProfileController::class, 'deleteDocument'])->name('profile.documents.destroy');
    
    // Enhanced document management
    Route::post('/profile/documents/{document}/share', [UserProfileController::class, 'shareDocument'])->name('profile.documents.share');
    Route::post('/profile/documents/{document}/backup', [UserProfileController::class, 'backupDocument'])->name('profile.documents.backup');
    Route::patch('/profile/documents/{document}/metadata', [UserProfileController::class, 'updateDocumentMetadata'])->name('profile.documents.update-metadata');
    Route::get('/profile/documents/{document}/activity', [UserProfileController::class, 'documentActivity'])->name('profile.documents.activity');
    Route::get('/profile/documents/expiring', [UserProfileController::class, 'expiringDocuments'])->name('profile.documents.expiring');
    Route::get('/profile/documents/statistics', [UserProfileController::class, 'documentStatistics'])->name('profile.documents.statistics');
    
    // Alumni organizations
    Route::get('/profile/alumni', [UserProfileController::class, 'alumni'])->name('profile.alumni');
    Route::post('/profile/alumni', [UserProfileController::class, 'addAlumni'])->name('profile.alumni.add');
    Route::put('/profile/alumni/{alumni}', [UserProfileController::class, 'updateAlumni'])->name('profile.alumni.update');
    Route::delete('/profile/alumni/{alumni}', [UserProfileController::class, 'removeAlumni'])->name('profile.alumni.remove');
    
    // Registration progress
    Route::get('/profile/registration', [UserProfileController::class, 'registration'])->name('profile.registration');
    Route::post('/profile/registration/complete-step', [UserProfileController::class, 'completeStep'])->name('profile.registration.complete-step');
    
    // Profile statistics and analytics
    Route::get('/profile/statistics', [UserProfileController::class, 'statistics'])->name('profile.statistics');
    Route::get('/profile/matching-opportunities', [UserProfileController::class, 'matchingOpportunities'])->name('profile.matching-opportunities');
    Route::get('/profile/export', [UserProfileController::class, 'export'])->name('profile.export');
    
    // Security routes
    Route::prefix('profile/security')->name('profile.security.')->group(function () {
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
});

// Public profile view
Route::get('/users/{user}/profile', [UserProfileController::class, 'show'])->name('profile.show');

// Shared document access (public route)
Route::get('/documents/shared/{token}', [UserProfileController::class, 'sharedDocument'])->name('documents.shared');

// API Routes for Profile Management
Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Profile API endpoints
    Route::get('/profile', [UserProfileApiController::class, 'profile']);
    Route::put('/profile', [UserProfileApiController::class, 'updateProfile']);
    
    // Skills API endpoints
    Route::get('/profile/skills', [UserProfileApiController::class, 'skills']);
    Route::post('/profile/skills', [UserProfileApiController::class, 'addSkill']);
    Route::put('/profile/skills/{skill}', [UserProfileApiController::class, 'updateSkill']);
    Route::delete('/profile/skills/{skill}', [UserProfileApiController::class, 'deleteSkill']);
    
    // Interests API endpoints
    Route::get('/profile/interests', [UserProfileApiController::class, 'interests']);
    Route::post('/profile/interests', [UserProfileApiController::class, 'addInterest']);
    Route::delete('/profile/interests/{interest}', [UserProfileApiController::class, 'deleteInterest']);
    
    // History API endpoints
    Route::get('/profile/history', [UserProfileApiController::class, 'history']);
    Route::post('/profile/history', [UserProfileApiController::class, 'addHistory']);
    Route::put('/profile/history/{history}', [UserProfileApiController::class, 'updateHistory']);
    Route::delete('/profile/history/{history}', [UserProfileApiController::class, 'deleteHistory']);
    
    // Documents API endpoints
    Route::get('/profile/documents', [UserProfileApiController::class, 'documents']);
    Route::post('/profile/documents', [UserProfileApiController::class, 'uploadDocument']);
    Route::delete('/profile/documents/{document}', [UserProfileApiController::class, 'deleteDocument']);
    
    // Alumni API endpoints
    Route::get('/profile/alumni', [UserProfileApiController::class, 'alumni']);
    Route::post('/profile/alumni', [UserProfileApiController::class, 'addAlumni']);
    Route::put('/profile/alumni/{alumni}', [UserProfileApiController::class, 'updateAlumni']);
    Route::delete('/profile/alumni/{alumni}', [UserProfileApiController::class, 'deleteAlumni']);
    
    // Registration and progress API endpoints
    Route::get('/profile/registration-progress', [UserProfileApiController::class, 'registrationProgress']);
    Route::post('/profile/complete-step', [UserProfileApiController::class, 'completeStep']);
    
    // Analytics and data API endpoints
    Route::get('/profile/statistics', [UserProfileApiController::class, 'statistics']);
    Route::get('/profile/matching-opportunities', [UserProfileApiController::class, 'matchingOpportunities']);
    Route::get('/profile/export', [UserProfileApiController::class, 'export']);
    Route::get('/profile/search-users', [UserProfileApiController::class, 'searchUsers']);
});