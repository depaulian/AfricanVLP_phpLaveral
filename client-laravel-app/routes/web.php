<?php

use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\NewsController;
use App\Http\Controllers\Client\EventController;
use App\Http\Controllers\Client\OrganizationController;
use App\Http\Controllers\Client\ResourceController;
use App\Http\Controllers\Client\VolunteerController;
use App\Http\Controllers\Client\ForumController;
use App\Http\Controllers\Client\ForumAttachmentController;
use App\Http\Controllers\Client\ForumNotificationController;
use App\Http\Controllers\Client\AlumniForumsController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\MessageController;
use App\Http\Controllers\Client\NotificationController;
use App\Http\Controllers\Client\FileUploadController;
use App\Http\Controllers\Client\NewsletterController;
use App\Http\Controllers\Client\OrganizationInvitationController;
use App\Http\Controllers\Client\VolunteeringController;
use App\Http\Controllers\Client\VolunteerMatchingController;
use App\Http\Controllers\Client\VolunteerApplicationController;
use App\Http\Controllers\Client\VolunteeringAnalyticsController;
use App\Http\Controllers\Client\VolunteerNotificationController;
use App\Http\Controllers\Client\VolunteerFeedbackController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Client\OrganizationEventController;
use App\Http\Controllers\Client\OrganizationResourceController;
use App\Http\Controllers\Client\BlogController;
use App\Http\Controllers\Client\OpportunityController;
use App\Http\Controllers\Client\PageController;
use App\Http\Controllers\Client\RegistrationController;
use App\Http\Controllers\Client\OrganizationRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Homepage API Routes
Route::prefix('api/homepage')->name('api.homepage.')->group(function () {
    Route::get('/sliders', [HomeController::class, 'getSliders'])->name('sliders');
    Route::get('/sections', [HomeController::class, 'getPageSections'])->name('sections');
    Route::get('/featured', [HomeController::class, 'getFeaturedContent'])->name('featured');
    Route::get('/statistics', [HomeController::class, 'getStatistics'])->name('statistics');
    Route::get('/search', [HomeController::class, 'search'])->name('search');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Email Verification Routes
Route::get('/email/verify', [AuthController::class, 'showVerifyEmailForm'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->name('verification.send');

// Public Content Routes
Route::get('public/news', [NewsController::class, 'publicIndex'])->name('news.public');
Route::get('public/news/{news}', [NewsController::class, 'publicShow'])->name('news.public.show');
Route::get('public/events', [EventController::class, 'publicIndex'])->name('events.public');
Route::get('public/events/{event}', [EventController::class, 'publicShow'])->name('events.public.show');
Route::get('/public/blog', [BlogController::class, 'publicIndex'])->name('blog.public');
Route::get('/public/blog/{blog}', [BlogController::class, 'publicShow'])->name('blog.public.show');
Route::get('/opportunities', [OpportunityController::class, 'publicIndex'])->name('opportunities.public');
Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'publicShow'])->name('opportunities.public.show');
Route::get('/resources', [ResourceController::class, 'publicIndex'])->name('resources.public');
Route::get('/resources/{resource}', [ResourceController::class, 'publicShow'])->name('resources.public.show');

// Protected Client Routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/personalized-content', [DashboardController::class, 'getPersonalizedContent'])->name('dashboard.personalized');
    Route::get('/dashboard/activity-summary', [DashboardController::class, 'getActivitySummary'])->name('dashboard.activity');
    
    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/upload-image', [ProfileController::class, 'uploadImage'])->name('upload-image');
    });
    
    // News Routes
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::get('/{news}', [NewsController::class, 'show'])->name('show');
        Route::get('/tagged/{tag}', [NewsController::class, 'tagged'])->name('tagged');
    });
    
    // Events Routes
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('/{event}', [EventController::class, 'show'])->name('show');
        Route::post('/{event}/register', [EventController::class, 'register'])->name('register');
        Route::delete('/{event}/unregister', [EventController::class, 'unregister'])->name('unregister');
    });
    
    // Resources Routes
    Route::prefix('resources')->name('resources.')->group(function () {
        Route::get('/', [ResourceController::class, 'index'])->name('index');
        Route::get('/search', [ResourceController::class, 'search'])->name('search');
        Route::get('/type/{resourceType}', [ResourceController::class, 'type'])->name('type');
        Route::get('/category/{category}', [ResourceController::class, 'category'])->name('category');
        Route::get('/organization/{organization}', [ResourceController::class, 'organization'])->name('organization');
        Route::get('/{resource}', [ResourceController::class, 'show'])->name('show');
        Route::get('/{resource}/files/{file}/download', [ResourceController::class, 'download'])->name('download');
    });
    
    // Blog Routes
    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/search', [BlogController::class, 'search'])->name('search');
        Route::get('/category/{category}', [BlogController::class, 'category'])->name('category');
        Route::get('/author/{author}', [BlogController::class, 'author'])->name('author');
        Route::get('/organization/{organization}', [BlogController::class, 'organization'])->name('organization');
        Route::get('/tagged/{tag}', [BlogController::class, 'tagged'])->name('tagged');
        Route::get('/archive/{year}/{month?}', [BlogController::class, 'archive'])->name('archive');
        Route::get('/{blog}', [BlogController::class, 'show'])->name('show');
    });
    
    // Opportunity Routes
    Route::prefix('opportunities')->name('opportunities.')->group(function () {
        Route::get('/', [OpportunityController::class, 'index'])->name('index');
        Route::get('/search', [OpportunityController::class, 'search'])->name('search');
        Route::get('/category/{category}', [OpportunityController::class, 'category'])->name('category');
        Route::get('/type/{type}', [OpportunityController::class, 'type'])->name('type');
        Route::get('/my-applications', [OpportunityController::class, 'myApplications'])->name('my-applications');
        Route::get('/{opportunity}', [OpportunityController::class, 'show'])->name('show');
        Route::get('/{opportunity}/apply', [OpportunityController::class, 'apply'])->name('apply');
        Route::post('/{opportunity}/apply', [OpportunityController::class, 'storeApplication'])->name('apply.store');
        Route::delete('/applications/{application}/withdraw', [OpportunityController::class, 'withdrawApplication'])->name('applications.withdraw');
    });
    
    // File Management Routes
    Route::prefix('files')->name('files.')->group(function () {
        Route::get('/', [FileUploadController::class, 'index'])->name('index');
        Route::get('/create', [FileUploadController::class, 'create'])->name('create');
        Route::post('/', [FileUploadController::class, 'store'])->name('store');
        Route::get('/{file}', [FileUploadController::class, 'show'])->name('show');
        Route::get('/{file}/download', [FileUploadController::class, 'download'])->name('download');
        Route::get('/{file}/edit', [FileUploadController::class, 'edit'])->name('edit');
        Route::put('/{file}', [FileUploadController::class, 'update'])->name('update');
        Route::delete('/{file}', [FileUploadController::class, 'destroy'])->name('destroy');
        Route::post('/upload-profile-image', [FileUploadController::class, 'uploadProfileImage'])->name('upload-profile-image');
    });

    // Volunteering Routes
    Route::prefix('volunteering')->name('volunteering.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\VolunteeringController::class, 'index'])->name('index');
        Route::get('/{opportunity}', [\App\Http\Controllers\Client\VolunteeringController::class, 'show'])->name('show');
        Route::post('/{opportunity}/apply', [\App\Http\Controllers\Client\VolunteeringController::class, 'apply'])->name('apply');
        
        // Volunteer Application Routes
        Route::prefix('applications')->name('applications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'index'])->name('index');
            Route::get('/create/{opportunity}', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'create'])->name('create');
            Route::post('/store/{opportunity}', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'store'])->name('store');
            Route::get('/{application}', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'show'])->name('show');
            Route::get('/{application}/edit', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'edit'])->name('edit');
            Route::patch('/{application}', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'update'])->name('update');
            Route::patch('/{application}/withdraw', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'withdraw'])->name('withdraw');
            Route::post('/{application}/message', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'sendMessage'])->name('message');
            Route::get('/{application}/status', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'getStatus'])->name('status');
            Route::get('/{application}/download-pdf', [\App\Http\Controllers\Client\VolunteerApplicationController::class, 'downloadPdf'])->name('download-pdf');
        });

        // Volunteer Time Tracking Routes
        Route::prefix('time-logs')->name('time-logs.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'index'])->name('index');
            Route::get('/{timeLog}/edit', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'edit'])->name('edit');
            Route::patch('/{timeLog}', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'update'])->name('update');
            Route::delete('/{timeLog}', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'destroy'])->name('destroy');
            Route::get('/{timeLog}', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'show'])->name('show');
            Route::get('/export', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'export'])->name('export');
        });

        // Volunteer Recognition Routes
        Route::prefix('achievements')->name('achievements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'achievements'])->name('index');
            Route::post('/{userAchievement}/toggle-featured', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/{userAchievement}/toggle-public', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'togglePublic'])->name('toggle-public');
            Route::post('/{userAchievement}/share', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'shareAchievement'])->name('share');
            Route::get('/progress', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'getAchievementProgress'])->name('progress');
        });

        Route::prefix('certificates')->name('certificates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'certificates'])->name('index');
            Route::get('/{certificate}', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'showCertificate'])->name('show');
            Route::get('/{certificate}/download', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'downloadCertificate'])->name('download');
            Route::post('/{certificate}/toggle-public', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'toggleCertificatePublic'])->name('toggle-public');
        });

        Route::prefix('portfolio')->name('portfolio.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'portfolio'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'exportPortfolio'])->name('export');
        });

        Route::prefix('recognition')->name('recognition.')->group(function () {
            Route::get('/wall', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'recognitionWall'])->name('wall');
            Route::get('/leaderboards', [\App\Http\Controllers\Client\VolunteerRecognitionController::class, 'leaderboards'])->name('leaderboards');
        });

        // Volunteer Impact Tracking Routes
        Route::prefix('impact')->name('impact.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'dashboard'])->name('dashboard');
            Route::get('/records', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'records'])->name('records');
            Route::get('/create', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'create'])->name('create');
            Route::post('/store', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'store'])->name('store');
            Route::get('/records/{record}', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'show'])->name('show');
            Route::get('/records/{record}/edit', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'edit'])->name('edit');
            Route::put('/records/{record}', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'update'])->name('update');
            Route::post('/feedback', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'submitFeedback'])->name('feedback.submit');
            Route::get('/stories', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'stories'])->name('stories');
            Route::get('/stories/{story}', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'showStory'])->name('story');
            Route::get('/report', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'report'])->name('report');
            Route::get('/export', [\App\Http\Controllers\Client\VolunteerImpactController::class, 'export'])->name('export');
        });

        // Additional Time Log Routes
        Route::prefix('time-logs')->name('time-logs.')->group(function () {
            Route::post('/bulk-action', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/analytics', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'analytics'])->name('analytics');
            Route::post('/report', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'report'])->name('report');
        });

        // Assignment Time Logging Routes
        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::post('/{assignment}/log-hours', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'store'])->name('log-hours');
            Route::get('/{assignment}/logs', [\App\Http\Controllers\Client\VolunteerTimeLogController::class, 'getAssignmentLogs'])->name('logs');
        });
    });

    // File Management Routes (outside volunteering group)
    Route::prefix('files')->name('files.')->group(function () {
        Route::post('/upload-organization-logo/{organization}', [FileUploadController::class, 'uploadOrganizationLogo'])->name('upload-organization-logo');
        Route::get('/upload-progress', [FileUploadController::class, 'uploadProgress'])->name('upload-progress');
    });
    
    // Volunteering Routes
    Route::prefix('volunteer')->name('volunteer.')->group(function () {
        Route::get('/', [VolunteerController::class, 'index'])->name('index');
        Route::get('/opportunities', [VolunteerController::class, 'opportunities'])->name('opportunities');
        Route::get('/opportunities/{opportunity}', [VolunteerController::class, 'showOpportunity'])->name('opportunities.show');
        Route::post('/opportunities/{opportunity}/apply', [VolunteerController::class, 'applyForOpportunity'])->name('opportunities.apply');
        Route::post('/opportunities/{opportunity}/withdraw', [VolunteerController::class, 'withdrawApplication'])->name('opportunities.withdraw');
        Route::get('/my-applications', [VolunteerController::class, 'myApplications'])->name('my-applications');
        Route::get('/history', [VolunteerController::class, 'history'])->name('history');
        Route::get('/interests', [VolunteerController::class, 'interests'])->name('interests');
        Route::post('/interests', [VolunteerController::class, 'updateInterests'])->name('interests.update');
    });

    // New Volunteering System Routes
    Route::prefix('volunteering')->name('client.volunteering.')->group(function () {
        Route::get('/', [VolunteeringController::class, 'index'])->name('index');
        Route::get('/dashboard', [VolunteeringController::class, 'dashboard'])->name('dashboard');
        Route::get('/opportunities/{opportunity}', [VolunteeringController::class, 'show'])->name('show');
        Route::post('/opportunities/{opportunity}/apply', [VolunteeringController::class, 'apply'])->name('apply');
        Route::get('/applications', [VolunteeringController::class, 'myApplications'])->name('applications');
        Route::get('/assignments', [VolunteeringController::class, 'myAssignments'])->name('assignments');
        Route::get('/assignments/{assignment}', [VolunteeringController::class, 'showAssignment'])->name('assignment');
        Route::post('/log-hours', [VolunteeringController::class, 'logHours'])->name('log-hours');
        Route::get('/time-logs', [VolunteeringController::class, 'timeLogs'])->name('time-logs');
        Route::get('/certificates', [VolunteeringController::class, 'certificates'])->name('certificates');
        Route::get('/certificates/{assignment}/download', [VolunteeringController::class, 'downloadCertificate'])->name('certificate.download');
        
        // Volunteer Application Routes
        Route::prefix('applications')->name('applications.')->group(function () {
            Route::get('/', [VolunteerApplicationController::class, 'index'])->name('index');
            Route::get('/create/{opportunity}', [VolunteerApplicationController::class, 'create'])->name('create');
            Route::post('/store/{opportunity}', [VolunteerApplicationController::class, 'store'])->name('store');
            Route::get('/{application}', [VolunteerApplicationController::class, 'show'])->name('show');
            Route::patch('/{application}/withdraw', [VolunteerApplicationController::class, 'withdraw'])->name('withdraw');
            Route::post('/{application}/message', [VolunteerApplicationController::class, 'sendMessage'])->name('message');
            Route::get('/{application}/download-pdf', [VolunteerApplicationController::class, 'downloadPdf'])->name('download-pdf');
        });

        // Volunteer Matching Routes
        Route::prefix('matching')->name('matching.')->group(function () {
            Route::get('/recommendations', [VolunteerMatchingController::class, 'recommendations'])->name('recommendations');
            Route::get('/trending', [VolunteerMatchingController::class, 'trending'])->name('trending');
            Route::get('/opportunities/{opportunity}/explanation', [VolunteerMatchingController::class, 'matchExplanation'])->name('explanation');
            Route::get('/opportunities/{opportunity}/similar-volunteers', [VolunteerMatchingController::class, 'similarVolunteers'])->name('similar-volunteers');
            Route::get('/preferences', [VolunteerMatchingController::class, 'preferences'])->name('preferences');
            Route::get('/notification-preferences', [VolunteerMatchingController::class, 'notificationPreferences'])->name('preferences.get');
            Route::post('/notification-preferences', [VolunteerMatchingController::class, 'updateNotificationPreferences'])->name('preferences.update');
            Route::post('/trigger-recommendations', [VolunteerMatchingController::class, 'triggerRecommendations'])->name('trigger');
            Route::get('/profile-completion', [VolunteerMatchingController::class, 'profileCompletion'])->name('profile-completion');
            Route::post('/clear-cache', [VolunteerMatchingController::class, 'clearCache'])->name('clear-cache');
        });

        // Volunteering Analytics Routes
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'dashboard'])->name('dashboard');
            Route::get('/metrics', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'metrics'])->name('metrics');
            Route::get('/comparison', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'comparison'])->name('comparison');
            Route::post('/generate-report', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'generateReport'])->name('generate-report');
            Route::get('/scheduled-reports', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'scheduledReports'])->name('scheduled-reports');
            Route::post('/scheduled-reports', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'createScheduledReport'])->name('scheduled-reports.create');
            Route::put('/scheduled-reports/{report}', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'updateScheduledReport'])->name('scheduled-reports.update');
            Route::delete('/scheduled-reports/{report}', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'deleteScheduledReport'])->name('scheduled-reports.delete');
            Route::get('/api/data', [\App\Http\Controllers\Client\VolunteeringAnalyticsController::class, 'apiData'])->name('api-data');
        });

        // Volunteer Notification Routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'index'])->name('index');
            Route::get('/{notification}', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'show'])->name('show');
            Route::post('/{notification}/mark-read', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'markAsRead'])->name('mark-read');
            Route::post('/mark-all-read', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/{notification}', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-action', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/preferences', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'preferences'])->name('preferences');
            Route::post('/preferences', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'updatePreferences'])->name('preferences.update');
            Route::get('/api/unread-count', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'unreadCount'])->name('api.unread-count');
            Route::get('/api/recent', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'recent'])->name('api.recent');
            Route::get('/api/statistics', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'statistics'])->name('api.statistics');
            Route::post('/test', [\App\Http\Controllers\Client\VolunteerNotificationController::class, 'test'])->name('test');
        });

        // Volunteer Feedback Routes
        Route::prefix('feedback')->name('feedback.')->group(function () {
            Route::get('/', [VolunteerFeedbackController::class, 'index'])->name('index');
            Route::get('/create', [VolunteerFeedbackController::class, 'create'])->name('create');
            Route::post('/', [VolunteerFeedbackController::class, 'store'])->name('store');
            Route::get('/public', [VolunteerFeedbackController::class, 'public'])->name('public');
            Route::get('/{feedback}', [VolunteerFeedbackController::class, 'show'])->name('show');
            Route::get('/{feedback}/edit', [VolunteerFeedbackController::class, 'edit'])->name('edit');
            Route::put('/{feedback}', [VolunteerFeedbackController::class, 'update'])->name('update');
            Route::post('/{feedback}/respond', [VolunteerFeedbackController::class, 'respond'])->name('respond');
            Route::post('/{feedback}/request-follow-up', [VolunteerFeedbackController::class, 'requestFollowUp'])->name('request-follow-up');
            
            // API routes for feedback
            Route::get('/api/templates', [VolunteerFeedbackController::class, 'getTemplates'])->name('templates');
            Route::get('/api/tags', [VolunteerFeedbackController::class, 'getTags'])->name('tags');
            Route::get('/api/stats', [VolunteerFeedbackController::class, 'getStats'])->name('stats');
        });
    });
    
    // Messages Routes
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/create', [MessageController::class, 'create'])->name('create');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::get('/search', [MessageController::class, 'search'])->name('search');
        Route::get('/{conversation}', [MessageController::class, 'show'])->name('show');
        Route::post('/{conversation}/reply', [MessageController::class, 'storeMessage'])->name('reply');
        Route::post('/{conversation}/archive', [MessageController::class, 'archive'])->name('archive');
        Route::post('/{conversation}/unarchive', [MessageController::class, 'unarchive'])->name('unarchive');
    });

    // Notifications Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/read/all', [NotificationController::class, 'deleteRead'])->name('delete-read');
    });
    
    // Organization Invitations
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/my-invitations', [OrganizationInvitationController::class, 'myInvitations'])->name('my-invitations');
        Route::get('/pending-count', [OrganizationInvitationController::class, 'pendingCount'])->name('pending-count');
    });
    
});

// Organization-specific Routes (require organization membership)
Route::middleware(['auth', 'verified'])->prefix('organizations/{organization}')->name('organizations.')->group(function () {
    
    // Organization Dashboard
    Route::get('/', [OrganizationController::class, 'dashboard'])->name('dashboard');
    Route::get('/members', [OrganizationController::class, 'members'])->name('members');
    Route::get('/alumni', [OrganizationController::class, 'alumni'])->name('alumni');
    Route::get('/events', [OrganizationController::class, 'events'])->name('events');
    Route::get('/news', [OrganizationController::class, 'news'])->name('news');
    
    // Forum Routes
    Route::prefix('forum')->name('forum.')->group(function () {
        Route::get('/', [ForumController::class, 'index'])->name('index');
        Route::get('/threads/create', [ForumController::class, 'createThread'])->name('threads.create');
        Route::post('/threads', [ForumController::class, 'storeThread'])->name('threads.store');
        Route::get('/threads/{thread}', [ForumController::class, 'showThread'])->name('threads.show');
        Route::post('/threads/{thread}/posts', [ForumController::class, 'storePost'])->name('posts.store');
        Route::put('/posts/{post}', [ForumController::class, 'updatePost'])->name('posts.update');
        Route::delete('/posts/{post}', [ForumController::class, 'deletePost'])->name('posts.delete');
    });
    
    // Organization Event Management
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [OrganizationEventController::class, 'index'])->name('index');
        Route::get('/{event}', [OrganizationEventController::class, 'show'])->name('show');
        
        // Admin-only event management routes
        Route::middleware(['organization.admin'])->group(function () {
            Route::get('/create', [OrganizationEventController::class, 'create'])->name('create');
            Route::post('/', [OrganizationEventController::class, 'store'])->name('store');
            Route::get('/{event}/edit', [OrganizationEventController::class, 'edit'])->name('edit');
            Route::put('/{event}', [OrganizationEventController::class, 'update'])->name('update');
            Route::delete('/{event}', [OrganizationEventController::class, 'destroy'])->name('destroy');
            Route::post('/{event}/duplicate', [OrganizationEventController::class, 'duplicate'])->name('duplicate');
            Route::post('/{event}/volunteering-opportunities', [OrganizationEventController::class, 'addVolunteeringOpportunity'])->name('volunteering-opportunities.store');
        });
    });
    
    // Organization Resource Management
    Route::prefix('resources')->name('resources.')->group(function () {
        Route::get('/', [OrganizationResourceController::class, 'index'])->name('index');
        Route::get('/{resource}', [OrganizationResourceController::class, 'show'])->name('show');
        Route::get('/{resource}/files/{file}/download', [OrganizationResourceController::class, 'download'])->name('files.download');
        
        // Admin-only resource management routes
        Route::middleware(['organization.admin'])->group(function () {
            Route::get('/create', [OrganizationResourceController::class, 'create'])->name('create');
            Route::post('/', [OrganizationResourceController::class, 'store'])->name('store');
            Route::get('/{resource}/edit', [OrganizationResourceController::class, 'edit'])->name('edit');
            Route::put('/{resource}', [OrganizationResourceController::class, 'update'])->name('update');
            Route::delete('/{resource}', [OrganizationResourceController::class, 'destroy'])->name('destroy');
            Route::post('/{resource}/duplicate', [OrganizationResourceController::class, 'duplicate'])->name('duplicate');
            Route::delete('/{resource}/files/{file}', [OrganizationResourceController::class, 'deleteFile'])->name('files.delete');
        });
    });
    
    // Organization Management (for organization admins)
    Route::middleware(['organization.admin'])->group(function () {
        Route::get('/manage', [OrganizationController::class, 'manage'])->name('manage');
        Route::put('/update', [OrganizationController::class, 'update'])->name('update');
        Route::post('/invite-user', [OrganizationController::class, 'inviteUser'])->name('invite-user');
        Route::post('/remove-user/{user}', [OrganizationController::class, 'removeUser'])->name('remove-user');
        Route::post('/update-user-role/{user}', [OrganizationController::class, 'updateUserRole'])->name('update-user-role');
        Route::post('/add-to-alumni/{user}', [OrganizationController::class, 'addToAlumni'])->name('add-to-alumni');
    });
    
});

// Forum Routes (Global Forums)
Route::middleware(['auth', 'verified'])->prefix('forums')->name('forums.')->group(function () {
    
    // Forum listing and search
    Route::get('/', [ForumController::class, 'index'])->name('index');
    Route::get('/search', [ForumController::class, 'search'])->name('search');
    Route::get('/search/suggestions', [ForumController::class, 'searchSuggestions'])->name('search.suggestions');
    Route::get('/search/filters', [ForumController::class, 'getSearchFilters'])->name('search.filters');
    
    // Forum-specific routes
    Route::get('/{forum}', [ForumController::class, 'show'])->name('show');
    
    // Thread management
    Route::get('/{forum}/threads/create', [ForumController::class, 'createThread'])->name('threads.create');
    Route::post('/{forum}/threads', [ForumController::class, 'storeThread'])->name('threads.store');
    Route::get('/{forum}/threads/{thread}', [ForumController::class, 'showThread'])->name('threads.show');
    Route::get('/{forum}/threads/{thread}/edit', [ForumController::class, 'editThread'])->name('threads.edit');
    Route::put('/{forum}/threads/{thread}', [ForumController::class, 'updateThread'])->name('threads.update');
    Route::delete('/{forum}/threads/{thread}', [ForumController::class, 'deleteThread'])->name('threads.delete');
    
    // Thread moderation
    Route::post('/{forum}/threads/{thread}/pin', [ForumController::class, 'togglePin'])->name('threads.pin');
    Route::post('/{forum}/threads/{thread}/lock', [ForumController::class, 'toggleLock'])->name('threads.lock');
    
    // Post management
    Route::post('/{forum}/threads/{thread}/posts', [ForumController::class, 'storePost'])->name('posts.store');
    Route::get('/posts/{post}/edit', [ForumController::class, 'editPost'])->name('posts.edit');
    Route::put('/posts/{post}', [ForumController::class, 'updatePost'])->name('posts.update');
    Route::delete('/posts/{post}', [ForumController::class, 'deletePost'])->name('posts.delete');
    
    // Post interactions
    Route::post('/posts/{post}/vote', [ForumController::class, 'vote'])->name('posts.vote');
    Route::post('/posts/{post}/solution', [ForumController::class, 'markSolution'])->name('posts.solution');
    
    // Attachments
    Route::get('/attachments/{attachment}/download', [ForumAttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/attachments/{attachment}', [ForumAttachmentController::class, 'show'])->name('attachments.show');
    Route::get('/attachments/{attachment}/preview', [ForumAttachmentController::class, 'preview'])->name('attachments.preview');
    Route::delete('/attachments/{attachment}', [ForumAttachmentController::class, 'destroy'])->name('attachments.delete');
    Route::get('/attachments/stats', [ForumAttachmentController::class, 'stats'])->name('attachments.stats');
    
    // Forum Notification routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [ForumNotificationController::class, 'index'])->name('index');
        Route::get('/unread', [ForumNotificationController::class, 'unread'])->name('unread');
        Route::post('/{notification}/read', [ForumNotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [ForumNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/preferences', [ForumNotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [ForumNotificationController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/subscriptions', [ForumNotificationController::class, 'subscriptions'])->name('subscriptions');
        Route::post('/subscribe', [ForumNotificationController::class, 'subscribe'])->name('subscribe');
        Route::post('/unsubscribe', [ForumNotificationController::class, 'unsubscribe'])->name('unsubscribe');
        Route::delete('/subscriptions/{subscription}', [ForumNotificationController::class, 'removeSubscription'])->name('subscriptions.remove');
        Route::post('/subscriptions/{subscription}/toggle', [ForumNotificationController::class, 'toggleSubscription'])->name('subscriptions.toggle');
        Route::get('/check-subscription', [ForumNotificationController::class, 'checkSubscription'])->name('check-subscription');
        Route::get('/count', [ForumNotificationController::class, 'count'])->name('count');
    });
    
    // Alumni Forums Routes
    Route::prefix('alumni-forums')->name('alumni-forums.')->group(function () {
        // Organization-specific alumni forums
        Route::get('/organizations/{orgId}', [AlumniForumsController::class, 'index'])->name('index');
        Route::get('/organizations/{orgId}/add-thread', [AlumniForumsController::class, 'addNewThread'])->name('add-thread');
        Route::post('/organizations/{orgId}/store-thread', [AlumniForumsController::class, 'storeThread'])->name('store-thread');
        Route::get('/organizations/{orgId}/threads/{id}', [AlumniForumsController::class, 'thread'])->name('thread');
        Route::post('/organizations/{orgId}/threads/{threadId}/posts', [AlumniForumsController::class, 'storePost'])->name('store-post');
        
        // Public forums
        Route::get('/public', [AlumniForumsController::class, 'publicThreads'])->name('public-threads');
        Route::get('/public/add-thread', [AlumniForumsController::class, 'addNewPublicThread'])->name('add-public-thread');
        Route::post('/public/store-thread', [AlumniForumsController::class, 'storePublicThread'])->name('store-public-thread');
        Route::get('/public/threads/{id}', [AlumniForumsController::class, 'publicThread'])->name('public-thread');
        Route::post('/public/threads/{threadId}/posts', [AlumniForumsController::class, 'storePublicPost'])->name('store-public-post');
    });
});

// API Routes for AJAX calls
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    Route::get('/countries/{country}/cities', function($countryId) {
        return \App\Models\City::where('country_id', $countryId)->get();
    })->name('cities.by-country');
    Route::get('/organizations/search', [OrganizationController::class, 'search'])->name('organizations.search');
    Route::get('/volunteering-categories', function() {
        return \App\Models\VolunteeringCategory::active()->ordered()->get();
    })->name('volunteering-categories');
    
    // Messages and Notifications API
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread-count');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    
    // Volunteer API
    Route::get('/volunteer/stats', [VolunteerController::class, 'getStats'])->name('volunteer.stats');
});

// Newsletter Routes (Public)
Route::prefix('newsletter')->name('newsletter.')->group(function () {
    Route::get('/', [NewsletterController::class, 'index'])->name('index');
    Route::post('/subscribe', [NewsletterController::class, 'subscribe'])->name('subscribe');
    Route::get('/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('unsubscribe');
    Route::post('/unsubscribe', [NewsletterController::class, 'processUnsubscribe'])->name('process-unsubscribe');
    Route::get('/preferences', [NewsletterController::class, 'preferences'])->name('preferences');
    Route::post('/preferences', [NewsletterController::class, 'updatePreferences'])->name('update-preferences');
    Route::post('/status', [NewsletterController::class, 'getStatus'])->name('status');
});

// Organization Invitation Routes (Public)
Route::prefix('organization/invitation')->name('organization.invitation.')->group(function () {
    Route::get('/respond/{token}', [OrganizationInvitationController::class, 'respond'])->name('respond');
    Route::post('/accept/{token}', [OrganizationInvitationController::class, 'accept'])->name('accept');
    Route::post('/reject/{token}', [OrganizationInvitationController::class, 'reject'])->name('reject');
});

// Static Pages Routes
Route::prefix('pages')->name('pages.')->group(function () {
    Route::get('/{slug}/sections', [PageController::class, 'getSections'])->name('sections');
    Route::get('/{slug}/sliders', [PageController::class, 'getSliders'])->name('sliders');
    Route::get('/search', [PageController::class, 'search'])->name('search');
    Route::get('/sitemap', [PageController::class, 'sitemap'])->name('sitemap');
});

// Email availability check route (should be added before the protected routes)
Route::post('/register/check-email', [RegistrationController::class, 'checkEmail'])->name('register.check-email');

// Cities by country API route
Route::get('/api/countries/{country}/cities', [RegistrationController::class, 'getCitiesByCountry'])->name('api.cities.by-country');

// Registration API routes for AJAX calls
Route::prefix('api/registration')->name('api.registration.')->group(function () {
    Route::get('/volunteering-categories', [RegistrationController::class, 'getVolunteeringCategories'])->name('volunteering-categories');
    Route::get('/organization-categories', [RegistrationController::class, 'getOrganizationCategories'])->name('organization-categories');
});


// Registration Routes
Route::prefix('registration')->name('registration.')->group(function () {
    // Registration type selection (public)
    Route::get('/', function () {
        return view('client.registration.select_type');
    })->name('index');
    
    // Individual volunteer registration (existing)
    Route::prefix('volunteer')->name('volunteer.')->group(function () {
        Route::get('/register', [RegistrationController::class, 'showRegistrationForm'])->name('start');
        Route::post('/register', [RegistrationController::class, 'register'])->name('register');
        
        Route::middleware('auth')->group(function () {
            Route::get('/', [RegistrationController::class, 'index'])->name('index');
            Route::get('/{stepName}', [RegistrationController::class, 'step'])->name('step');
            Route::post('/{stepName}', [RegistrationController::class, 'processStep'])->name('process');
            Route::get('/progress/data', [RegistrationController::class, 'progress'])->name('progress');
            Route::post('/auto-save', [RegistrationController::class, 'autoSave'])->name('autosave');
            Route::post('/{stepName}/skip', [RegistrationController::class, 'skipStep'])->name('skip');
        });
    });
    
    // Organization registration (new multi-step)
    Route::prefix('organization')->name('organization.')->group(function () {
        Route::get('/start', [OrganizationRegistrationController::class, 'start'])->name('start');
        Route::get('/step/{stepName}', [OrganizationRegistrationController::class, 'step'])->name('step');
        Route::post('/step/{stepName}', [OrganizationRegistrationController::class, 'processStep'])->name('process');
        Route::get('/success', [OrganizationRegistrationController::class, 'success'])->name('success');
        Route::get('/progress', [OrganizationRegistrationController::class, 'progress'])->name('progress');
        Route::post('/auto-save', [OrganizationRegistrationController::class, 'autoSave'])->name('autosave');
        Route::get('/cities', [OrganizationRegistrationController::class, 'getCities'])->name('cities');
        Route::get('/analytics', [OrganizationRegistrationController::class, 'analytics'])->name('analytics');
    });
});

// Static Page Routes (must be at the end to avoid conflicts)
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [PageController::class, 'terms'])->name('terms');

// Dynamic page route (catch-all for custom pages)
Route::get('/{slug}', [PageController::class, 'show'])->name('page.show')->where('slug', '[a-zA-Z0-9\-_]+');

// Language switching route
Route::get('/choose-language', function() {
    return view('language-selector');
})->name('language.choose');

Route::post('/set-language/{locale}', function($locale) {
    if (in_array($locale, ['en', 'fr', 'ar', 'pt', 'es'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('language.set');