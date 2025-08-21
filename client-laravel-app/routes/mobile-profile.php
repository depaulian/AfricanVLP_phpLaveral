<?php

use App\Http\Controllers\Client\MobileProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Profile Routes
|--------------------------------------------------------------------------
|
| These routes handle mobile-optimized profile functionality including
| touch-friendly interfaces, camera integration, and offline capabilities.
|
*/

Route::middleware(['auth', 'verified'])->prefix('mobile/profile')->name('profile.mobile.')->group(function () {
    
    // Main mobile profile dashboard
    Route::get('/', [MobileProfileController::class, 'dashboard'])->name('dashboard');
    
    // Profile editing
    Route::get('/edit', [MobileProfileController::class, 'edit'])->name('edit');
    Route::put('/update', [MobileProfileController::class, 'update'])->name('update');
    
    // Image upload with camera support
    Route::post('/upload-image', [MobileProfileController::class, 'uploadImage'])->name('upload-image');
    
    // Document management
    Route::get('/documents', [MobileProfileController::class, 'documents'])->name('documents');
    Route::post('/documents/upload', [MobileProfileController::class, 'uploadDocument'])->name('documents.upload');
    Route::get('/documents/{document}/view', [MobileProfileController::class, 'viewDocument'])->name('documents.view');
    Route::get('/documents/{document}/download', [MobileProfileController::class, 'downloadDocument'])->name('documents.download');
    Route::delete('/documents/{document}', [MobileProfileController::class, 'deleteDocument'])->name('documents.destroy');
    
    // Skills management
    Route::get('/skills', [MobileProfileController::class, 'skills'])->name('skills');
    Route::post('/skills', [MobileProfileController::class, 'addSkill'])->name('skills.add');
    
    // Volunteering history
    Route::get('/history', [MobileProfileController::class, 'history'])->name('history');
    Route::get('/history/create', [MobileProfileController::class, 'createHistory'])->name('history.create');
    
    // Profile sharing and networking
    Route::get('/share', [MobileProfileController::class, 'share'])->name('share');
    
    // Offline sync and data management
    Route::get('/sync', [MobileProfileController::class, 'syncData'])->name('sync');
    
    // Mobile notifications
    Route::get('/notifications', [MobileProfileController::class, 'notifications'])->name('notifications');
    Route::post('/notifications', [MobileProfileController::class, 'updateNotifications'])->name('notifications.update');
    
    // App configuration for mobile clients
    Route::get('/config', [MobileProfileController::class, 'appConfig'])->name('config');
});

// API routes for mobile app integration
Route::middleware(['auth:sanctum'])->prefix('api/mobile/profile')->name('api.mobile.profile.')->group(function () {
    
    // Profile data endpoints
    Route::get('/data', [MobileProfileController::class, 'syncData'])->name('data');
    Route::post('/sync', [MobileProfileController::class, 'syncData'])->name('sync');
    
    // Quick actions
    Route::post('/image', [MobileProfileController::class, 'uploadImage'])->name('image.upload');
    Route::post('/document', [MobileProfileController::class, 'uploadDocument'])->name('document.upload');
    Route::post('/skill', [MobileProfileController::class, 'addSkill'])->name('skill.add');
    
    // Configuration
    Route::get('/config', [MobileProfileController::class, 'appConfig'])->name('config');
    
    // Notifications
    Route::post('/notifications', [MobileProfileController::class, 'updateNotifications'])->name('notifications');
});