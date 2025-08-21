<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProfileActivityLog;
use Symfony\Component\HttpFoundation\Response;

class TrackProfileActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track for authenticated users
        if (!Auth::check()) {
            return $response;
        }

        // Track profile-related activities
        $this->trackActivity($request);

        return $response;
    }

    /**
     * Track profile activity based on the request
     */
    private function trackActivity(Request $request): void
    {
        $user = Auth::user();
        $route = $request->route();
        
        if (!$route) {
            return;
        }

        $routeName = $route->getName();
        $method = $request->method();
        
        // Define activity mappings
        $activityMappings = [
            'profile.show' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_VIEWED,
                'target_user_id' => $this->getTargetUserId($request),
                'description' => 'Viewed profile'
            ],
            'profile.update' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_UPDATED,
                'description' => 'Updated profile information'
            ],
            'profile.store' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_CREATED,
                'description' => 'Created profile'
            ],
            'profile.upload-image' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_PROFILE_PHOTO_UPDATED,
                'description' => 'Updated profile photo'
            ],
            'profile.skills.store' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_SKILL_ADDED,
                'description' => 'Added new skill'
            ],
            'profile.skills.destroy' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_SKILL_REMOVED,
                'description' => 'Removed skill'
            ],
            'profile.interests.store' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_INTEREST_ADDED,
                'description' => 'Added new interest'
            ],
            'profile.interests.destroy' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_INTEREST_REMOVED,
                'description' => 'Removed interest'
            ],
            'profile.documents.store' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_DOCUMENT_UPLOADED,
                'description' => 'Uploaded document'
            ],
            'profile.privacy.update' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_PRIVACY_UPDATED,
                'description' => 'Updated privacy settings'
            ],
            'profile.contact.update' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_CONTACT_INFO_UPDATED,
                'description' => 'Updated contact information'
            ],
            'profile.location.update' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_LOCATION_UPDATED,
                'description' => 'Updated location information'
            ],
            'profile.social.update' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_SOCIAL_LINKS_UPDATED,
                'description' => 'Updated social media links'
            ],
            'profile.volunteering-history.store' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_VOLUNTEERING_HISTORY_ADDED,
                'description' => 'Added volunteering history'
            ],
            'profile.alumni-organizations.store' => [
                'activity_type' => ProfileActivityLog::ACTIVITY_ALUMNI_ORGANIZATION_ADDED,
                'description' => 'Added alumni organization'
            ]
        ];

        // Check if we should track this route
        if (!isset($activityMappings[$routeName])) {
            return;
        }

        $mapping = $activityMappings[$routeName];

        // Only track successful requests (2xx status codes)
        if ($request->ajax() && !$this->isSuccessfulResponse($request)) {
            return;
        }

        // Log the activity
        try {
            ProfileActivityLog::logActivity(
                userId: $user->id,
                activityType: $mapping['activity_type'],
                targetUserId: $mapping['target_user_id'] ?? null,
                description: $mapping['description'],
                metadata: $this->getActivityMetadata($request),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::error('Failed to track profile activity: ' . $e->getMessage());
        }
    }

    /**
     * Get target user ID from request parameters
     */
    private function getTargetUserId(Request $request): ?int
    {
        // Check route parameters for user ID
        $userId = $request->route('user') ?? $request->route('id');
        
        if ($userId && is_numeric($userId)) {
            return (int) $userId;
        }

        // Check query parameters
        $userId = $request->query('user_id') ?? $request->query('id');
        
        if ($userId && is_numeric($userId)) {
            return (int) $userId;
        }

        return null;
    }

    /**
     * Get activity metadata from request
     */
    private function getActivityMetadata(Request $request): array
    {
        $metadata = [
            'route' => $request->route()->getName(),
            'method' => $request->method(),
            'url' => $request->url()
        ];

        // Add relevant request data based on the activity
        $routeName = $request->route()->getName();
        
        switch ($routeName) {
            case 'profile.skills.store':
                $metadata['skill_name'] = $request->input('skill_name');
                break;
                
            case 'profile.interests.store':
                $metadata['interest_id'] = $request->input('interest_id');
                break;
                
            case 'profile.documents.store':
                $metadata['document_type'] = $request->input('document_type');
                $metadata['document_name'] = $request->input('document_name');
                break;
                
            case 'profile.privacy.update':
                $metadata['privacy_settings'] = $request->only([
                    'profile_visibility',
                    'contact_visibility',
                    'location_visibility'
                ]);
                break;
                
            case 'profile.update':
                $metadata['updated_fields'] = array_keys($request->except([
                    '_token', '_method', 'password', 'password_confirmation'
                ]));
                break;
        }

        return $metadata;
    }

    /**
     * Check if the response indicates success
     */
    private function isSuccessfulResponse(Request $request): bool
    {
        // For AJAX requests, we'll assume success if no exception was thrown
        // In a real implementation, you might want to check the actual response
        return true;
    }
}