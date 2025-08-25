<?php

use App\Http\Controllers\Admin\EnhancedFeaturesDashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Http\Controllers\Admin\TranslationController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\OrganizationInvitationController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ForumController;
use App\Http\Controllers\Admin\ForumManagementController;
use App\Http\Controllers\Admin\ForumNotificationController;
use App\Http\Controllers\Admin\ForumAnalyticsController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\DashboardReportsController;
use App\Http\Controllers\Admin\UserFeedbackController;
use App\Http\Controllers\Admin\OrganizationAdminController;
use App\Http\Controllers\Admin\ProfileImageController;
use App\Http\Controllers\Admin\WidgetController;
use App\Http\Controllers\Admin\AuMessageController;
use App\Http\Controllers\Admin\TranslationManagementController;
use App\Http\Controllers\Admin\ContentTagController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\OpportunityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthController;
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

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Email Verification Routes
Route::get('/email/verify', [AuthController::class, 'showVerifyEmailForm'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->name('verification.send');

// Admin Routes (Protected)
Route::middleware(['auth', 'admin'])->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('admin.dashboard.stats');
    
    // User Management
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/export/csv', [UserController::class, 'exportCsv'])->name('export.csv');
    });
    
    // Admin Management (Super Admin & Admin Only)
    Route::prefix('admin-management')->name('admin.admin-management.')->group(function () {
        Route::get('/', [AdminManagementController::class, 'index'])->name('index');
        Route::get('/create', [AdminManagementController::class, 'create'])->name('create');
        Route::post('/', [AdminManagementController::class, 'store'])->name('store');
        Route::get('/{admin}', [AdminManagementController::class, 'show'])->name('show');
        Route::get('/{admin}/edit', [AdminManagementController::class, 'edit'])->name('edit');
        Route::put('/{admin}', [AdminManagementController::class, 'update'])->name('update');
        Route::delete('/{admin}', [AdminManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{admin}/toggle-status', [AdminManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/bulk-action', [AdminManagementController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export/csv', [AdminManagementController::class, 'exportCsv'])->name('export.csv');
    });
    
    // Organization Management
    Route::prefix('organizations')->name('admin.organizations.')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('index');
        Route::get('/create', [OrganizationController::class, 'create'])->name('create');
        Route::post('/', [OrganizationController::class, 'store'])->name('store');
        Route::get('/{organization}', [OrganizationController::class, 'show'])->name('show');
        Route::get('/{organization}/edit', [OrganizationController::class, 'edit'])->name('edit');
        Route::put('/{organization}', [OrganizationController::class, 'update'])->name('update');
        Route::delete('/{organization}', [OrganizationController::class, 'destroy'])->name('destroy');
        Route::post('/{organization}/toggle-status', [OrganizationController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/export/csv', [OrganizationController::class, 'exportCsv'])->name('export.csv');
    });
    
    // Event Management
    Route::prefix('events')->name('admin.events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('/{event}', [EventController::class, 'show'])->name('show');
        Route::put('/{event}', [EventController::class, 'update'])->name('update');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [EventController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/{event}/duplicate', [EventController::class, 'duplicate'])->name('duplicate');
        Route::get('/stats', [EventController::class, 'stats'])->name('stats');
        Route::get('/organizations/{organization}', [EventController::class, 'byOrganization'])->name('by-organization');
        
        // UI routes rendering Blade views with real data (under /ui)
        Route::get('/ui', [EventController::class, 'uiIndex'])->name('ui.index');
        Route::get('/ui/create', [EventController::class, 'uiCreate'])->name('ui.create');
        Route::get('/ui/{event}', [EventController::class, 'uiShow'])->name('ui.show');
        Route::get('/ui/{event}/edit', [EventController::class, 'uiEdit'])->name('ui.edit');
        Route::post('/ui', [EventController::class, 'uiStore'])->name('ui.store');
        Route::put('/ui/{event}', [EventController::class, 'uiUpdate'])->name('ui.update');
    });
    
    // News Management
    Route::prefix('news')->name('admin.news.')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::post('/', [NewsController::class, 'store'])->name('store');
        Route::get('/{news}', [NewsController::class, 'show'])->name('show');
        Route::put('/{news}', [NewsController::class, 'update'])->name('update');
        Route::delete('/{news}', [NewsController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [NewsController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/{news}/duplicate', [NewsController::class, 'duplicate'])->name('duplicate');
        Route::get('/stats', [NewsController::class, 'stats'])->name('stats');
        Route::get('/organizations/{organization}', [NewsController::class, 'byOrganization'])->name('by-organization');
        Route::get('/regions/{region}', [NewsController::class, 'byRegion'])->name('by-region');
    });
    
    // Resource Management
    Route::prefix('resources')->name('admin.resources.')->group(function () {
        Route::get('/', [ResourceController::class, 'index'])->name('index');
        Route::post('/', [ResourceController::class, 'store'])->name('store');
        Route::get('/{resource}', [ResourceController::class, 'show'])->name('show');
        Route::put('/{resource}', [ResourceController::class, 'update'])->name('update');
        Route::delete('/{resource}', [ResourceController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [ResourceController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/{resource}/duplicate', [ResourceController::class, 'duplicate'])->name('duplicate');
        Route::get('/stats', [ResourceController::class, 'stats'])->name('stats');
        Route::get('/types', [ResourceController::class, 'getResourceTypes'])->name('types');
        Route::get('/categories', [ResourceController::class, 'getCategories'])->name('categories');
        Route::get('/files', [ResourceController::class, 'files'])->name('files');
        Route::get('/files/{file}/download', [ResourceController::class, 'downloadFile'])->name('files.download');
    });
    
    // Blog Management
    Route::prefix('blogs')->name('admin.blogs.')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::post('/', [BlogController::class, 'store'])->name('store');
        Route::get('/{blog}', [BlogController::class, 'show'])->name('show');
        Route::put('/{blog}', [BlogController::class, 'update'])->name('update');
        Route::delete('/{blog}', [BlogController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [BlogController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/{blog}/duplicate', [BlogController::class, 'duplicate'])->name('duplicate');
        Route::get('/stats', [BlogController::class, 'stats'])->name('stats');
        
        // UI routes rendering Blade views with real data (under /ui)
        Route::get('/ui', [BlogController::class, 'uiIndex'])->name('ui.index');
        Route::get('/ui/create', [BlogController::class, 'uiCreate'])->name('ui.create');
        Route::get('/ui/{blog}', [BlogController::class, 'uiShow'])->name('ui.show');
        Route::get('/ui/{blog}/edit', [BlogController::class, 'uiEdit'])->name('ui.edit');
        Route::post('/ui', [BlogController::class, 'uiStore'])->name('ui.store');
        Route::put('/ui/{blog}', [BlogController::class, 'uiUpdate'])->name('ui.update');
    });
    
    // Opportunity Management
    Route::prefix('opportunities')->name('admin.opportunities.')->group(function () {
        Route::get('/', [OpportunityController::class, 'index'])->name('index');
        Route::post('/', [OpportunityController::class, 'store'])->name('store');
        Route::get('/{opportunity}', [OpportunityController::class, 'show'])->name('show');
        Route::put('/{opportunity}', [OpportunityController::class, 'update'])->name('update');
        Route::delete('/{opportunity}', [OpportunityController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [OpportunityController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/{opportunity}/duplicate', [OpportunityController::class, 'duplicate'])->name('duplicate');
        Route::get('/stats', [OpportunityController::class, 'stats'])->name('stats');
        Route::get('/{opportunity}/applications', [OpportunityController::class, 'applications'])->name('applications');
        Route::put('/{opportunity}/applications/{application}/status', [OpportunityController::class, 'updateApplicationStatus'])->name('applications.update-status');
        
        // UI routes rendering Blade views with real data (under /ui)
        Route::get('/ui', [OpportunityController::class, 'uiIndex'])->name('ui.index');
        Route::get('/ui/create', [OpportunityController::class, 'uiCreate'])->name('ui.create');
        Route::get('/ui/{opportunity}', [OpportunityController::class, 'uiShow'])->name('ui.show');
        Route::get('/ui/{opportunity}/edit', [OpportunityController::class, 'uiEdit'])->name('ui.edit');
        Route::post('/ui', [OpportunityController::class, 'uiStore'])->name('ui.store');
        Route::put('/ui/{opportunity}', [OpportunityController::class, 'uiUpdate'])->name('ui.update');
    });
    
    // File Upload Management
    Route::prefix('files')->name('admin.files.')->group(function () {
        Route::post('/upload', [FileUploadController::class, 'uploadSingle'])->name('upload.single');
        Route::post('/upload-multiple', [FileUploadController::class, 'uploadMultiple'])->name('upload.multiple');
        Route::delete('/delete', [FileUploadController::class, 'deleteFile'])->name('delete');
        Route::get('/transformations', [FileUploadController::class, 'getTransformations'])->name('transformations');
        Route::get('/{file}', [FileUploadController::class, 'show'])->name('show');
        Route::put('/{file}', [FileUploadController::class, 'update'])->name('update');
        Route::delete('/{file}', [FileUploadController::class, 'destroy'])->name('destroy');
    });
    
    // Resource File Management
    Route::get('/resources/files', [ResourceController::class, 'files'])->name('admin.resources.files');
    
    // Page Management
    Route::prefix('pages')->name('admin.pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{page}', [PageController::class, 'show'])->name('show');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [PageController::class, 'update'])->name('update');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
        Route::post('/{page}/toggle-status', [PageController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Slider Management
    Route::prefix('sliders')->name('admin.sliders.')->group(function () {
        Route::get('/', [SliderController::class, 'index'])->name('index');
        Route::get('/create', [SliderController::class, 'create'])->name('create');
        Route::post('/', [SliderController::class, 'store'])->name('store');
        Route::get('/{slider}', [SliderController::class, 'show'])->name('show');
        Route::get('/{slider}/edit', [SliderController::class, 'edit'])->name('edit');
        Route::put('/{slider}', [SliderController::class, 'update'])->name('update');
        Route::delete('/{slider}', [SliderController::class, 'destroy'])->name('destroy');
        Route::post('/{slider}/toggle-status', [SliderController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{slider}/duplicate', [SliderController::class, 'duplicate'])->name('duplicate');
        Route::post('/update-positions', [SliderController::class, 'updatePositions'])->name('update-positions');
        Route::get('/by-page', [SliderController::class, 'getByPage'])->name('by-page');
    });
    
    // Geographic Management
    Route::prefix('countries')->name('admin.countries.')->group(function () {
        Route::get('/', [CountryController::class, 'index'])->name('index');
        Route::get('/create', [CountryController::class, 'create'])->name('create');
        Route::post('/', [CountryController::class, 'store'])->name('store');
        Route::get('/{country}', [CountryController::class, 'show'])->name('show');
        Route::get('/{country}/edit', [CountryController::class, 'edit'])->name('edit');
        Route::put('/{country}', [CountryController::class, 'update'])->name('update');
        Route::delete('/{country}', [CountryController::class, 'destroy'])->name('destroy');
        Route::post('/{country}/toggle-status', [CountryController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{country}/cities', [CountryController::class, 'getCities'])->name('cities');
        Route::get('/export/csv', [CountryController::class, 'exportCsv'])->name('export.csv');
    });
    
    Route::prefix('cities')->name('admin.cities.')->group(function () {
        Route::get('/', [CityController::class, 'index'])->name('index');
        Route::get('/create', [CityController::class, 'create'])->name('create');
        Route::post('/', [CityController::class, 'store'])->name('store');
        Route::get('/{city}', [CityController::class, 'show'])->name('show');
        Route::get('/{city}/edit', [CityController::class, 'edit'])->name('edit');
        Route::put('/{city}', [CityController::class, 'update'])->name('update');
        Route::delete('/{city}', [CityController::class, 'destroy'])->name('destroy');
        Route::post('/{city}/toggle-status', [CityController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/export/csv', [CityController::class, 'exportCsv'])->name('export.csv');
    });
    
    // Translation Management
    Route::prefix('translations')->name('admin.translations.')->group(function () {
        Route::get('/', [TranslationController::class, 'index'])->name('index');
        Route::post('/translate', [TranslationController::class, 'translate'])->name('translate');
        Route::post('/translate-multiple', [TranslationController::class, 'translateMultiple'])->name('translate-multiple');
        Route::post('/detect-language', [TranslationController::class, 'detectLanguage'])->name('detect-language');
        Route::post('/translate-html', [TranslationController::class, 'translateHtml'])->name('translate-html');
        Route::get('/supported-languages', [TranslationController::class, 'getSupportedLanguages'])->name('supported-languages');
        Route::get('/stats', [TranslationController::class, 'getStats'])->name('stats');
        Route::delete('/cache', [TranslationController::class, 'clearCache'])->name('clear-cache');
    });
    
    // Newsletter Management
    Route::prefix('newsletter')->name('admin.newsletter.')->group(function () {
        Route::get('/', [NewsletterController::class, 'index'])->name('index');
        Route::get('/subscribers', [NewsletterController::class, 'subscribers'])->name('subscribers');
        Route::get('/content', [NewsletterController::class, 'content'])->name('content');
        Route::get('/create', [NewsletterController::class, 'create'])->name('create');
        Route::post('/content', [NewsletterController::class, 'store'])->name('store');
        Route::post('/send/{newsletter}', [NewsletterController::class, 'send'])->name('send');
        Route::post('/subscribe', [NewsletterController::class, 'subscribe'])->name('subscribe');
        Route::post('/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('unsubscribe');
        Route::get('/stats', [NewsletterController::class, 'getStats'])->name('stats');
        Route::get('/export-subscribers', [NewsletterController::class, 'exportSubscribers'])->name('export-subscribers');
        Route::post('/bulk-action', [NewsletterController::class, 'bulkAction'])->name('bulk-action');
    });
    
    // Organization Invitation Management
    Route::prefix('organizations/{organization}/invitations')->name('admin.organizations.invitations.')->group(function () {
        Route::get('/', [OrganizationInvitationController::class, 'index'])->name('index');
        Route::get('/create', [OrganizationInvitationController::class, 'create'])->name('create');
        Route::post('/', [OrganizationInvitationController::class, 'store'])->name('store');
        Route::post('/bulk-send', [OrganizationInvitationController::class, 'bulkSend'])->name('bulk-send');
        Route::get('/export', [OrganizationInvitationController::class, 'export'])->name('export');
        Route::get('/stats', [OrganizationInvitationController::class, 'stats'])->name('stats');
    });
    
    Route::prefix('invitations')->name('admin.invitations.')->group(function () {
        Route::post('/{invitation}/resend', [OrganizationInvitationController::class, 'resend'])->name('resend');
        Route::post('/{invitation}/cancel', [OrganizationInvitationController::class, 'cancel'])->name('cancel');
        Route::post('/cleanup', [OrganizationInvitationController::class, 'cleanup'])->name('cleanup');
    });
    
    // Activity Log Routes
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/user/{user}', [ActivityLogController::class, 'userActivity'])->name('user');
        Route::get('/export', [ActivityLogController::class, 'export'])->name('export');
        Route::delete('/cleanup', [ActivityLogController::class, 'cleanup'])->name('cleanup');
        Route::get('/statistics', [ActivityLogController::class, 'statistics'])->name('statistics');
        Route::get('/users/{user}/timeline', [ActivityLogController::class, 'userTimeline'])->name('user-timeline');
    });

    // Enhanced Features Dashboard Routes
    Route::prefix('enhanced-features')->name('enhanced-features.')->group(function () {
        Route::get('/dashboard', [EnhancedFeaturesDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [EnhancedFeaturesDashboardController::class, 'analytics'])->name('analytics');
        Route::get('/verification', [EnhancedFeaturesDashboardController::class, 'verification'])->name('verification');
        Route::get('/content-stats', [EnhancedFeaturesDashboardController::class, 'contentStats'])->name('content-stats');
        Route::get('/user-engagement', [EnhancedFeaturesDashboardController::class, 'userEngagement'])->name('user-engagement');
        Route::get('/system-health', [EnhancedFeaturesDashboardController::class, 'systemHealth'])->name('system-health');
        Route::get('/feature-usage', [EnhancedFeaturesDashboardController::class, 'featureUsage'])->name('feature-usage');
        Route::get('/performance', [EnhancedFeaturesDashboardController::class, 'performance'])->name('performance');
        Route::get('/system-status', [EnhancedFeaturesDashboardController::class, 'systemStatus'])->name('system-status');
        Route::get('/export/analytics', [EnhancedFeaturesDashboardController::class, 'exportAnalytics'])->name('export.analytics');
        Route::get('/export/verification', [EnhancedFeaturesDashboardController::class, 'exportVerification'])->name('export.verification');
        Route::post('/clear-cache', [EnhancedFeaturesDashboardController::class, 'clearCache'])->name('clear-cache');
    });
    
    // Support System Management
    Route::prefix('support')->name('admin.support.')->group(function () {
        Route::get('/', [SupportController::class, 'index'])->name('index');
        Route::get('/create', [SupportController::class, 'create'])->name('create');
        Route::post('/', [SupportController::class, 'store'])->name('store');
        Route::get('/{ticket}', [SupportController::class, 'show'])->name('show');
        Route::put('/{ticket}', [SupportController::class, 'update'])->name('update');
        Route::post('/{ticket}/responses', [SupportController::class, 'addResponse'])->name('responses.store');
        Route::post('/{ticket}/assign', [SupportController::class, 'assign'])->name('assign');
        Route::post('/bulk-action', [SupportController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export/csv', [SupportController::class, 'export'])->name('export');
        Route::get('/api/stats', [SupportController::class, 'stats'])->name('stats');
    });
    
    // Dashboard Reports Management
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        Route::get('/', [DashboardReportsController::class, 'index'])->name('index');
        Route::get('/volunteers', [DashboardReportsController::class, 'volunteersReport'])->name('volunteers');
        Route::get('/organizations', [DashboardReportsController::class, 'organizationsReport'])->name('organizations');
        Route::get('/events', [DashboardReportsController::class, 'eventsReport'])->name('events');
        Route::get('/system-activity', [DashboardReportsController::class, 'systemActivityReport'])->name('system-activity');
        Route::get('/support', [DashboardReportsController::class, 'supportReport'])->name('support');
        Route::get('/export/{reportType}', [DashboardReportsController::class, 'exportReport'])->name('export');
    });
    
    // User Feedback Management
    Route::prefix('feedback')->name('admin.feedback.')->group(function () {
        Route::get('/', [UserFeedbackController::class, 'index'])->name('index');
        Route::get('/{feedback}', [UserFeedbackController::class, 'show'])->name('show');
        Route::put('/{feedback}', [UserFeedbackController::class, 'update'])->name('update');
        Route::post('/{feedback}/response', [UserFeedbackController::class, 'addResponse'])->name('response.store');
        Route::post('/bulk-update', [UserFeedbackController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/api/stats', [UserFeedbackController::class, 'stats'])->name('stats');
        Route::get('/api/analytics', [UserFeedbackController::class, 'analytics'])->name('analytics');
        Route::get('/export/csv', [UserFeedbackController::class, 'export'])->name('export');
        Route::get('/attachment/{attachment}/download', [UserFeedbackController::class, 'downloadAttachment'])->name('attachment.download');
        Route::delete('/attachment/{attachment}', [UserFeedbackController::class, 'deleteAttachment'])->name('attachment.delete');
    });
    
    // Organization Admin Management
    Route::prefix('organization-admins')->name('admin.organization-admins.')->group(function () {
        Route::get('/', [OrganizationAdminController::class, 'index'])->name('index');
        Route::get('/create', [OrganizationAdminController::class, 'create'])->name('create');
        Route::post('/', [OrganizationAdminController::class, 'store'])->name('store');
        Route::get('/{organizationAdmin}', [OrganizationAdminController::class, 'show'])->name('show');
        Route::get('/{organizationAdmin}/edit', [OrganizationAdminController::class, 'edit'])->name('edit');
        Route::put('/{organizationAdmin}', [OrganizationAdminController::class, 'update'])->name('update');
        Route::delete('/{organizationAdmin}', [OrganizationAdminController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-update', [OrganizationAdminController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/api/stats', [OrganizationAdminController::class, 'stats'])->name('stats');
        Route::get('/api/analytics', [OrganizationAdminController::class, 'analytics'])->name('analytics');
        Route::post('/api/check-permissions', [OrganizationAdminController::class, 'checkPermissions'])->name('check-permissions');
        Route::get('/export/csv', [OrganizationAdminController::class, 'export'])->name('export');
    });
    
    // Profile Image Management
    Route::prefix('profile-images')->name('admin.profile-images.')->group(function () {
        Route::get('/', [ProfileImageController::class, 'index'])->name('index');
        Route::get('/{user}', [ProfileImageController::class, 'show'])->name('show');
        Route::post('/{user}/upload', [ProfileImageController::class, 'upload'])->name('upload');
        Route::post('/{user}/crop', [ProfileImageController::class, 'crop'])->name('crop');
        Route::delete('/{user}', [ProfileImageController::class, 'delete'])->name('delete');
        Route::post('/bulk-delete', [ProfileImageController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/api/stats', [ProfileImageController::class, 'stats'])->name('stats');
        Route::post('/cleanup', [ProfileImageController::class, 'cleanup'])->name('cleanup');
        Route::get('/{user}/download/{size?}', [ProfileImageController::class, 'download'])->name('download');
        Route::get('/export/csv', [ProfileImageController::class, 'export'])->name('export');
    });
    
    // Widget Management
    Route::prefix('widgets')->name('admin.widgets.')->group(function () {
        Route::get('/', [WidgetController::class, 'index'])->name('index');
        Route::get('/create', [WidgetController::class, 'create'])->name('create');
        Route::post('/', [WidgetController::class, 'store'])->name('store');
        Route::get('/{widget}', [WidgetController::class, 'show'])->name('show');
        Route::get('/{widget}/edit', [WidgetController::class, 'edit'])->name('edit');
        Route::put('/{widget}', [WidgetController::class, 'update'])->name('update');
        Route::delete('/{widget}', [WidgetController::class, 'destroy'])->name('destroy');
        Route::post('/{widget}/duplicate', [WidgetController::class, 'duplicate'])->name('duplicate');
        Route::post('/reorder', [WidgetController::class, 'reorder'])->name('reorder');
        Route::post('/bulk-update', [WidgetController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/api/stats', [WidgetController::class, 'stats'])->name('stats');
        Route::get('/{widget}/preview', [WidgetController::class, 'preview'])->name('preview');
        Route::post('/clear-cache', [WidgetController::class, 'clearCache'])->name('clear-cache');
        Route::get('/export/csv', [WidgetController::class, 'export'])->name('export');
    });
    
    // AU Messages Management
    Route::prefix('au-messages')->name('admin.au-messages.')->group(function () {
        Route::get('/', [AuMessageController::class, 'index'])->name('index');
        Route::get('/create', [AuMessageController::class, 'create'])->name('create');
        Route::post('/', [AuMessageController::class, 'store'])->name('store');
        Route::get('/{auMessage}', [AuMessageController::class, 'show'])->name('show');
        Route::post('/{auMessage}/reply', [AuMessageController::class, 'reply'])->name('reply');
        Route::put('/{auMessage}/mark-read', [AuMessageController::class, 'markRead'])->name('mark-read');
        Route::put('/{auMessage}/archive', [AuMessageController::class, 'archive'])->name('archive');
        Route::delete('/{auMessage}', [AuMessageController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [AuMessageController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/api/stats', [AuMessageController::class, 'stats'])->name('stats');
        Route::get('/attachment/{attachment}/download', [AuMessageController::class, 'downloadAttachment'])->name('attachment.download');
        Route::get('/export/csv', [AuMessageController::class, 'export'])->name('export');
    });
    
    // Forum Management
    Route::prefix('forums')->name('admin.forums.')->group(function () {
        Route::get('/', [ForumController::class, 'index'])->name('index');
        Route::get('/create', [ForumController::class, 'create'])->name('create');
        Route::post('/', [ForumController::class, 'store'])->name('store');
        Route::get('/{forum}', [ForumController::class, 'show'])->name('show');
        Route::get('/{forum}/edit', [ForumController::class, 'edit'])->name('edit');
        Route::put('/{forum}', [ForumController::class, 'update'])->name('update');
        Route::delete('/{forum}', [ForumController::class, 'destroy'])->name('destroy');
        
        // Forum management
        Route::prefix('management')->name('management.')->group(function () {
            Route::get('/', [ForumManagementController::class, 'index'])->name('index');
            Route::get('/forums', [ForumManagementController::class, 'forums'])->name('forums');
            Route::get('/threads', [ForumManagementController::class, 'threads'])->name('threads');
            Route::get('/users', [ForumManagementController::class, 'users'])->name('users');
            Route::get('/reports', [ForumManagementController::class, 'reports'])->name('reports');
            Route::get('/badges', [ForumManagementController::class, 'badges'])->name('badges');
            
            // AJAX actions
            Route::post('/forums/{forum}/toggle-status', [ForumManagementController::class, 'toggleForumStatus'])->name('forums.toggle-status');
            Route::post('/threads/{thread}/toggle-pin', [ForumManagementController::class, 'toggleThreadPin'])->name('threads.toggle-pin');
            Route::post('/threads/{thread}/toggle-lock', [ForumManagementController::class, 'toggleThreadLock'])->name('threads.toggle-lock');
            Route::delete('/threads/{thread}', [ForumManagementController::class, 'deleteThread'])->name('threads.delete');
            Route::delete('/posts/{post}', [ForumManagementController::class, 'deletePost'])->name('posts.delete');
            Route::post('/reports/{report}/handle', [ForumManagementController::class, 'handleReport'])->name('reports.handle');
            Route::post('/users/award-badge', [ForumManagementController::class, 'awardBadge'])->name('users.award-badge');
            Route::post('/users/adjust-reputation', [ForumManagementController::class, 'adjustReputation'])->name('users.adjust-reputation');
            Route::post('/bulk-action', [ForumManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/analytics/data', [ForumManagementController::class, 'analyticsData'])->name('analytics.data');
            Route::post('/export', [ForumManagementController::class, 'exportData'])->name('export');
        });
        
        // Forum moderation
        Route::get('/moderation/dashboard', [ForumController::class, 'moderation'])->name('moderation');
        Route::post('/moderation/threads/bulk', [ForumController::class, 'bulkModerateThreads'])->name('moderation.threads.bulk');
        Route::post('/moderation/posts/bulk', [ForumController::class, 'bulkModeratePosts'])->name('moderation.posts.bulk');
        
        // Moderator management
        Route::post('/{forum}/moderators', [ForumController::class, 'addModerator'])->name('moderators.add');
        Route::delete('/{forum}/moderators/{user}', [ForumController::class, 'removeModerator'])->name('moderators.remove');
        
        // Analytics
        Route::get('/{forum}/analytics', [ForumController::class, 'analytics'])->name('analytics');
        
        // Forum Notification Management
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [ForumNotificationController::class, 'index'])->name('index');
            Route::get('/{notification}', [ForumNotificationController::class, 'show'])->name('show');
            Route::get('/subscriptions/manage', [ForumNotificationController::class, 'subscriptions'])->name('subscriptions');
            Route::get('/preferences/manage', [ForumNotificationController::class, 'preferences'])->name('preferences');
            Route::post('/bulk-mark-read', [ForumNotificationController::class, 'bulkMarkAsRead'])->name('bulk-mark-read');
            Route::post('/bulk-delete', [ForumNotificationController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/cleanup', [ForumNotificationController::class, 'cleanup'])->name('cleanup');
            Route::post('/send-test', [ForumNotificationController::class, 'sendTest'])->name('send-test');
            Route::get('/statistics', [ForumNotificationController::class, 'statistics'])->name('statistics');
            Route::post('/export', [ForumNotificationController::class, 'export'])->name('export');
            Route::post('/subscriptions/{subscription}/manage', [ForumNotificationController::class, 'manageSubscription'])->name('subscriptions.manage');
            Route::post('/preferences/update-user', [ForumNotificationController::class, 'updateUserPreferences'])->name('preferences.update-user');
        });
        
        // Forum Analytics
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [ForumAnalyticsController::class, 'index'])->name('index');
            Route::get('/overview', [ForumAnalyticsController::class, 'getOverviewStats'])->name('overview');
            Route::get('/engagement', [ForumAnalyticsController::class, 'getUserEngagement'])->name('engagement');
            Route::get('/content', [ForumAnalyticsController::class, 'getContentPerformance'])->name('content');
            Route::get('/trends', [ForumAnalyticsController::class, 'getActivityTrends'])->name('trends');
            Route::get('/hourly', [ForumAnalyticsController::class, 'getHourlyPattern'])->name('hourly');
            Route::get('/health', [ForumAnalyticsController::class, 'getHealthDashboard'])->name('health');
            Route::get('/users', [ForumAnalyticsController::class, 'userAnalytics'])->name('users');
            Route::get('/content-analytics', [ForumAnalyticsController::class, 'contentAnalytics'])->name('content-analytics');
            Route::get('/moderation', [ForumAnalyticsController::class, 'moderationAnalytics'])->name('moderation');
            Route::get('/report', [ForumAnalyticsController::class, 'generateReport'])->name('report');
            Route::get('/realtime', [ForumAnalyticsController::class, 'getRealTimeData'])->name('realtime');
            Route::get('/comparison', [ForumAnalyticsController::class, 'getComparison'])->name('comparison');
            Route::get('/top-performers', [ForumAnalyticsController::class, 'getTopPerformers'])->name('top-performers');
            Route::post('/custom-query', [ForumAnalyticsController::class, 'customQuery'])->name('custom-query');
        });
    });
    
    // Translation Management System
    Route::prefix('translation-management')->name('admin.translation-management.')->group(function () {
        Route::get('/', [TranslationManagementController::class, 'index'])->name('index');
        Route::get('/create', [TranslationManagementController::class, 'create'])->name('create');
        Route::post('/', [TranslationManagementController::class, 'store'])->name('store');
        Route::get('/{translation}', [TranslationManagementController::class, 'show'])->name('show');
        Route::get('/{translation}/edit', [TranslationManagementController::class, 'edit'])->name('edit');
        Route::put('/{translation}', [TranslationManagementController::class, 'update'])->name('update');
        Route::delete('/{translation}', [TranslationManagementController::class, 'destroy'])->name('destroy');
        Route::post('/import', [TranslationManagementController::class, 'import'])->name('import');
        Route::get('/export', [TranslationManagementController::class, 'export'])->name('export');
        Route::post('/sync', [TranslationManagementController::class, 'sync'])->name('sync');
        Route::get('/api/stats', [TranslationManagementController::class, 'stats'])->name('stats');
        Route::get('/api/locale-progress', [TranslationManagementController::class, 'localeProgress'])->name('locale-progress');
        Route::delete('/cache/clear', [TranslationManagementController::class, 'clearCache'])->name('clear-cache');
    });
    
    // Tagged Content Management System
    Route::prefix('content-tags')->name('admin.content-tags.')->group(function () {
        Route::get('/', [ContentTagController::class, 'index'])->name('index');
        Route::get('/create', [ContentTagController::class, 'create'])->name('create');
        Route::post('/', [ContentTagController::class, 'store'])->name('store');
        Route::get('/{contentTag}', [ContentTagController::class, 'show'])->name('show');
        Route::get('/{contentTag}/edit', [ContentTagController::class, 'edit'])->name('edit');
        Route::put('/{contentTag}', [ContentTagController::class, 'update'])->name('update');
        Route::delete('/{contentTag}', [ContentTagController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [ContentTagController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/reorder', [ContentTagController::class, 'reorder'])->name('reorder');
        Route::get('/api/search', [ContentTagController::class, 'search'])->name('search');
        Route::get('/api/popular', [ContentTagController::class, 'popular'])->name('popular');
        Route::get('/api/trending', [ContentTagController::class, 'trending'])->name('trending');
        Route::get('/api/stats', [ContentTagController::class, 'stats'])->name('stats');
        Route::get('/export/csv', [ContentTagController::class, 'export'])->name('export');
    });
    
});

// API Routes for AJAX calls
Route::middleware(['auth', 'admin'])->prefix('api')->name('api.')->group(function () {
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/organizations/search', [OrganizationController::class, 'search'])->name('organizations.search');
    Route::get('/countries/{country}/cities', function($countryId) {
        return \App\Models\City::where('country_id', $countryId)->get();
    })->name('cities.by-country');
});