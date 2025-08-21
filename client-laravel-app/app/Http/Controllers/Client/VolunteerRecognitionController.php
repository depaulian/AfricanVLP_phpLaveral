<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VolunteerAchievement;
use App\Models\UserAchievement;
use App\Models\VolunteerCertificate;
use App\Services\VolunteerRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolunteerRecognitionController extends Controller
{
    public function __construct(
        private VolunteerRecognitionService $recognitionService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display user's achievements dashboard
     */
    public function achievements()
    {
        $user = Auth::user();
        
        // Get user's achievements
        $userAchievements = $user->achievements()
            ->with('achievement')
            ->orderBy('earned_at', 'desc')
            ->get();

        // Get available achievements with progress
        $availableAchievements = VolunteerAchievement::active()
            ->ordered()
            ->get()
            ->map(function ($achievement) use ($user) {
                $progress = $achievement->getProgressForUser($user);
                $hasEarned = $user->achievements()->where('achievement_id', $achievement->id)->exists();
                
                return [
                    'achievement' => $achievement,
                    'progress' => $progress,
                    'has_earned' => $hasEarned,
                    'user_achievement' => $hasEarned ? $user->achievements()->where('achievement_id', $achievement->id)->first() : null,
                ];
            });

        // Get achievement statistics
        $stats = $this->recognitionService->getUserAchievementStats($user);

        return view('client.volunteering.achievements.index', compact(
            'userAchievements',
            'availableAchievements',
            'stats'
        ));
    }

    /**
     * Display user's certificates
     */
    public function certificates()
    {
        $user = Auth::user();
        
        $certificates = $user->certificates()
            ->with(['organization', 'assignment.opportunity'])
            ->orderBy('issued_at', 'desc')
            ->paginate(12);

        $stats = $this->recognitionService->getUserCertificateStats($user);

        return view('client.volunteering.certificates.index', compact('certificates', 'stats'));
    }

    /**
     * Show specific certificate
     */
    public function showCertificate(VolunteerCertificate $certificate)
    {
        // Ensure user owns this certificate
        if ($certificate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to certificate.');
        }

        $certificate->load(['organization', 'assignment.opportunity']);
        
        return view('client.volunteering.certificates.show', compact('certificate'));
    }

    /**
     * Download certificate PDF
     */
    public function downloadCertificate(VolunteerCertificate $certificate)
    {
        // Ensure user owns this certificate
        if ($certificate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to certificate.');
        }

        // Generate PDF if it doesn't exist
        if (!$certificate->hasPdf()) {
            $this->recognitionService->generateCertificatePdf($certificate);
        }

        return response()->download(
            storage_path('app/' . $certificate->pdf_path),
            $certificate->certificate_number . '.pdf'
        );
    }

    /**
     * Display volunteer portfolio
     */
    public function portfolio()
    {
        $user = Auth::user();
        
        $user->load([
            'achievements.achievement',
            'certificates.organization',
            'volunteerApplications.opportunity.organization',
            'volunteerApplications.assignments' => function ($q) {
                $q->where('status', 'completed');
            }
        ]);

        $achievementStats = $this->recognitionService->getUserAchievementStats($user);
        $certificateStats = $this->recognitionService->getUserCertificateStats($user);
        
        // Calculate total volunteer hours
        $totalHours = $user->volunteerApplications->sum(function ($application) {
            return $application->assignments->sum('hours_completed');
        });

        // Get organizations served
        $organizationsServed = $user->volunteerApplications
            ->pluck('opportunity.organization')
            ->unique('id')
            ->values();

        return view('client.volunteering.portfolio.index', compact(
            'user',
            'achievementStats',
            'certificateStats',
            'totalHours',
            'organizationsServed'
        ));
    }

    /**
     * Export volunteer portfolio as PDF
     */
    public function exportPortfolio()
    {
        $user = Auth::user();
        
        return $this->recognitionService->exportVolunteerPortfolio($user);
    }

    /**
     * Display recognition wall
     */
    public function recognitionWall(Request $request)
    {
        $filters = $request->only(['organization_id', 'achievement_type', 'date_range']);
        
        $recognitionData = $this->recognitionService->getRecognitionWall($filters);
        
        return view('client.volunteering.recognition.wall', compact('recognitionData', 'filters'));
    }

    /**
     * Display leaderboards
     */
    public function leaderboards(Request $request)
    {
        $filters = $request->only(['organization_id', 'date_from', 'date_to']);
        
        $leaderboard = $this->recognitionService->getLeaderboard($filters);
        
        return view('client.volunteering.recognition.leaderboards', compact('leaderboard', 'filters'));
    }

    /**
     * Toggle achievement featured status
     */
    public function toggleFeatured(UserAchievement $userAchievement)
    {
        // Ensure user owns this achievement
        if ($userAchievement->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to achievement.');
        }

        $userAchievement->update([
            'is_featured' => !$userAchievement->is_featured
        ]);

        return response()->json([
            'success' => true,
            'is_featured' => $userAchievement->is_featured,
            'message' => $userAchievement->is_featured ? 'Achievement featured on profile' : 'Achievement removed from featured'
        ]);
    }

    /**
     * Toggle achievement public status
     */
    public function togglePublic(UserAchievement $userAchievement)
    {
        // Ensure user owns this achievement
        if ($userAchievement->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to achievement.');
        }

        $userAchievement->update([
            'is_public' => !$userAchievement->is_public
        ]);

        return response()->json([
            'success' => true,
            'is_public' => $userAchievement->is_public,
            'message' => $userAchievement->is_public ? 'Achievement is now public' : 'Achievement is now private'
        ]);
    }

    /**
     * Toggle certificate public status
     */
    public function toggleCertificatePublic(VolunteerCertificate $certificate)
    {
        // Ensure user owns this certificate
        if ($certificate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to certificate.');
        }

        $certificate->update([
            'is_public' => !$certificate->is_public
        ]);

        return response()->json([
            'success' => true,
            'is_public' => $certificate->is_public,
            'message' => $certificate->is_public ? 'Certificate is now public' : 'Certificate is now private'
        ]);
    }

    /**
     * Share achievement on social media
     */
    public function shareAchievement(UserAchievement $userAchievement)
    {
        // Ensure user owns this achievement
        if ($userAchievement->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to achievement.');
        }

        $sharingData = $this->recognitionService->getSharingData($userAchievement);
        
        return response()->json([
            'success' => true,
            'sharing_data' => $sharingData
        ]);
    }

    /**
     * Get achievement progress for user
     */
    public function getAchievementProgress()
    {
        $user = Auth::user();
        
        $achievements = VolunteerAchievement::active()
            ->ordered()
            ->get()
            ->map(function ($achievement) use ($user) {
                return [
                    'achievement' => $achievement,
                    'progress' => $achievement->getProgressForUser($user),
                    'has_earned' => $user->achievements()->where('achievement_id', $achievement->id)->exists(),
                ];
            });

        return response()->json([
            'success' => true,
            'achievements' => $achievements
        ]);
    }
}