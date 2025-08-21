<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UserVolunteeringHistory;
use App\Services\UserProfileService;
use App\Services\VolunteeringHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VolunteeringHistoryController extends Controller
{
    protected $userProfileService;
    protected $volunteeringHistoryService;

    public function __construct(UserProfileService $userProfileService, VolunteeringHistoryService $volunteeringHistoryService)
    {
        $this->middleware('auth');
        $this->userProfileService = $userProfileService;
        $this->volunteeringHistoryService = $volunteeringHistoryService;
    }

    /**
     * Display the user's volunteering timeline
     */
    public function timeline()
    {
        $user = Auth::user();
        $timeline = $this->volunteeringHistoryService->getVolunteeringTimeline($user);
        $impact = $this->volunteeringHistoryService->calculateVolunteeringImpact($user);

        return view('client.profile.volunteering-timeline', compact('timeline', 'impact'));
    }

    /**
     * Display the user's volunteering history (legacy route)
     */
    public function index()
    {
        return redirect()->route('profile.volunteering.timeline');
    }

    /**
     * Show the form for creating a new volunteering history entry
     */
    public function create()
    {
        return view('client.profile.volunteering-form');
    }

    /**
     * Store a newly created volunteering history entry
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'organization_name' => 'required|string|max:255',
            'role_title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'total_hours' => 'nullable|integer|min:0',
            'hours_per_week' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'skills_gained' => 'nullable|string',
            'achievements' => 'nullable|string',
            'impact_area' => 'nullable|string',
            'is_current' => 'boolean',
            'references' => 'nullable|array',
            'references.*.name' => 'required_with:references|string|max:255',
            'references.*.title' => 'nullable|string|max:255',
            'references.*.email' => 'nullable|email|max:255',
            'references.*.phone' => 'nullable|string|max:20',
            'references.*.relationship' => 'nullable|string|in:supervisor,colleague,coordinator,other',
            'references.*.can_contact' => 'boolean',
            'certificates' => 'nullable|array',
            'certificates.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120', // 5MB max
        ]);

        // Handle current position
        if ($request->boolean('is_current')) {
            $validated['end_date'] = null;
        }

        // Convert skills to array if provided
        if ($validated['skills_gained']) {
            $validated['skills_gained'] = array_map('trim', explode(',', $validated['skills_gained']));
        }

        // Clean up references array
        if (isset($validated['references'])) {
            $validated['references'] = array_filter($validated['references'], function($ref) {
                return !empty($ref['name']);
            });
            $validated['references'] = array_values($validated['references']); // Re-index
        }

        $user = Auth::user();
        $history = $this->volunteeringHistoryService->createHistoryEntry($user, $validated);

        // Handle certificate uploads
        if ($request->hasFile('certificates')) {
            foreach ($request->file('certificates') as $file) {
                $this->volunteeringHistoryService->uploadCertificate($history, $file);
            }
        }

        return redirect()->route('profile.volunteering.timeline')
            ->with('success', 'Volunteering experience added successfully!');
    }

    /**
     * Display the specified volunteering history entry
     */
    public function show(UserVolunteeringHistory $history)
    {
        $this->authorize('view', $history);
        
        $data = [
            'id' => $history->id,
            'role_title' => $history->role_title,
            'organization' => $history->organization->name ?? $history->organization_name,
            'period' => $history->start_date . ' to ' . ($history->end_date ?? 'Present'),
            'total_hours' => $history->total_hours,
            'description' => $history->description,
            'achievements' => $history->achievements,
            'skills_gained' => is_array($history->skills_gained) ? $history->skills_gained : explode(',', $history->skills_gained ?? ''),
            'references' => $history->references ?? [],
            'is_verified' => $history->is_verified,
        ];

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('client.profile.volunteering.show', compact('history'));
    }

    /**
     * Show the form for editing the specified volunteering history entry
     */
    public function edit(UserVolunteeringHistory $history)
    {
        $this->authorize('update', $history);
        
        return view('client.profile.volunteering-form', compact('history'));
    }

    /**
     * Update the specified volunteering history entry
     */
    public function update(Request $request, UserVolunteeringHistory $history)
    {
        $this->authorize('update', $history);

        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'organization_name' => 'required|string|max:255',
            'role_title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'total_hours' => 'nullable|integer|min:0',
            'hours_per_week' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'skills_gained' => 'nullable|string',
            'achievements' => 'nullable|string',
            'impact_area' => 'nullable|string',
            'is_current' => 'boolean',
            'references' => 'nullable|array',
            'references.*.name' => 'required_with:references|string|max:255',
            'references.*.title' => 'nullable|string|max:255',
            'references.*.email' => 'nullable|email|max:255',
            'references.*.phone' => 'nullable|string|max:20',
            'references.*.relationship' => 'nullable|string|in:supervisor,colleague,coordinator,other',
            'references.*.can_contact' => 'boolean',
            'certificates' => 'nullable|array',
            'certificates.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        // Handle current position
        if ($request->boolean('is_current')) {
            $validated['end_date'] = null;
        }

        // Convert skills to array if provided
        if ($validated['skills_gained']) {
            $validated['skills_gained'] = array_map('trim', explode(',', $validated['skills_gained']));
        }

        // Clean up references array
        if (isset($validated['references'])) {
            $validated['references'] = array_filter($validated['references'], function($ref) {
                return !empty($ref['name']);
            });
            $validated['references'] = array_values($validated['references']);
        }

        $history = $this->volunteeringHistoryService->updateHistoryEntry($history, $validated);

        // Handle new certificate uploads
        if ($request->hasFile('certificates')) {
            foreach ($request->file('certificates') as $file) {
                $this->volunteeringHistoryService->uploadCertificate($history, $file);
            }
        }

        return redirect()->route('profile.volunteering.timeline')
            ->with('success', 'Volunteering experience updated successfully!');
    }

    /**
     * Remove the specified volunteering history entry
     */
    public function destroy(UserVolunteeringHistory $history)
    {
        $this->authorize('delete', $history);
        
        $history->delete();

        return redirect()->route('profile.volunteering.timeline')
            ->with('success', 'Volunteering experience deleted successfully!');
    }

    /**
     * Get volunteering statistics for dashboard
     */
    public function stats()
    {
        $user = Auth::user();
        $stats = $this->volunteeringHistoryService->getVolunteeringStats($user);

        return response()->json($stats);
    }

    /**
     * Search volunteering history
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $user = Auth::user();
        $results = $this->volunteeringHistoryService->searchHistory($user, $request->q);

        return response()->json($results);
    }

    /**
     * Export volunteering portfolio
     */
    public function exportPortfolio(Request $request)
    {
        $user = Auth::user();
        $format = $request->get('format', 'pdf');
        
        $export = $this->volunteeringHistoryService->generatePortfolioExport($user, $format);

        if ($format === 'json') {
            return response()->json($export['data']);
        }

        // For PDF, we would use a PDF generation library
        // For now, return the JSON data with download headers
        return response()->json($export['data'])
            ->header('Content-Disposition', 'attachment; filename="volunteering_portfolio.json"');
    }

    /**
     * Download portfolio file
     */
    public function downloadPortfolio(Request $request, $filename)
    {
        $user = Auth::user();
        
        // Verify the file belongs to this user
        if (!str_contains($filename, "portfolio_{$user->id}_")) {
            abort(403);
        }

        if (!Storage::disk('temp')->exists($filename)) {
            abort(404);
        }

        return Storage::disk('temp')->download($filename);
    }

    /**
     * Get volunteering recommendations
     */
    public function recommendations()
    {
        $user = Auth::user();
        $recommendations = $this->volunteeringHistoryService->getVolunteeringRecommendations($user);

        return response()->json($recommendations);
    }

    /**
     * Bulk import volunteering history
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'source' => 'required|string|in:linkedin,manual,csv',
            'data' => 'required|array',
            'data.*.organization_name' => 'required|string',
            'data.*.role_title' => 'required|string',
            'data.*.start_date' => 'required|date',
            'data.*.end_date' => 'nullable|date',
        ]);

        $user = Auth::user();
        $result = $this->volunteeringHistoryService->bulkImportHistory(
            $user, 
            $request->data, 
            $request->source
        );

        return response()->json([
            'success' => true,
            'message' => "Imported {$result['imported_count']} experiences with {$result['error_count']} errors",
            'result' => $result,
        ]);
    }

    /**
     * Generate certificate for volunteering experience
     */
    public function generateCertificate(UserVolunteeringHistory $history)
    {
        $this->authorize('view', $history);
        
        $certificate = $this->volunteeringHistoryService->generateCertificate($history);

        return response()->json($certificate);
    }

    /**
     * Add reference to volunteering history
     */
    public function addReference(Request $request, UserVolunteeringHistory $history)
    {
        $this->authorize('update', $history);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'relationship' => 'required|string|in:supervisor,colleague,coordinator,other',
            'can_contact' => 'boolean',
        ]);

        $reference = $this->volunteeringHistoryService->addReference($history, $validated);

        return response()->json([
            'success' => true,
            'reference' => $reference,
        ]);
    }

    /**
     * Remove reference from volunteering history
     */
    public function removeReference(UserVolunteeringHistory $history, $referenceId)
    {
        $this->authorize('update', $history);

        $success = $this->volunteeringHistoryService->removeReference($history, $referenceId);

        return response()->json([
            'success' => $success,
        ]);
    }

    /**
     * Upload certificate for volunteering history
     */
    public function uploadCertificate(Request $request, UserVolunteeringHistory $history)
    {
        $this->authorize('update', $history);

        $request->validate([
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        $path = $this->volunteeringHistoryService->uploadCertificate($history, $request->file('certificate'));

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::url($path),
        ]);
    }

    /**
     * Get API data for external integrations
     */
    public function apiData(Request $request)
    {
        $user = Auth::user();
        
        $filters = $request->only(['verified_only', 'organization_id', 'date_from', 'date_to']);
        $data = $this->volunteeringHistoryService->getHistoryForApi($user, $filters);

        return response()->json($data);
    }

    /**
     * Generate comprehensive impact report
     */
    public function generateImpactReport()
    {
        $user = Auth::user();
        $report = $this->volunteeringHistoryService->generateImpactReport($user);
        
        return response()->json($report);
    }

    /**
     * Generate comprehensive volunteering certificate
     */
    public function generateComprehensiveCertificate()
    {
        $user = Auth::user();
        $certificate = $this->volunteeringHistoryService->generateComprehensiveCertificate($user);
        
        return response()->json($certificate);
    }

    /**
     * Preview portfolio before export
     */
    public function previewPortfolio()
    {
        $user = Auth::user();
        $portfolioData = $this->volunteeringHistoryService->generatePortfolioExport($user, 'json');
        
        return view('client.profile.volunteering-portfolio-preview', [
            'portfolio' => $portfolioData['data'],
            'statistics' => $portfolioData['statistics'] ?? [],
        ]);
    }

    /**
     * Display public portfolio (with share token)
     */
    public function publicPortfolio($userId, $token)
    {
        $user = \App\Models\User::findOrFail($userId);
        
        // Verify share token
        $expectedToken = hash('sha256', $user->id . $user->email . config('app.key') . 'portfolio');
        
        if (!hash_equals($expectedToken, $token)) {
            abort(403, 'Invalid portfolio access token');
        }

        $portfolioData = $this->volunteeringHistoryService->generatePortfolioExport($user, 'json');
        
        return view('client.profile.volunteering-public-portfolio', [
            'portfolio' => $portfolioData['data'],
            'user' => $user,
        ]);
    }

    /**
     * Get enhanced volunteering statistics for dashboard widgets
     */
    public function getEnhancedStatistics()
    {
        $user = Auth::user();
        $impact = $this->volunteeringHistoryService->calculateVolunteeringImpact($user);
        
        return response()->json([
            'summary' => $impact,
            'quick_stats' => [
                'total_hours' => $impact['total_hours'],
                'organizations' => $impact['total_organizations'],
                'impact_score' => $impact['impact_score'],
                'verification_rate' => round($impact['verification_rate'], 1),
            ],
            'recent_achievements' => $impact['recent_achievements'],
            'consistency' => $impact['consistency'],
        ]);
    }

    /**
     * Get volunteering insights and recommendations
     */
    public function getInsights()
    {
        $user = Auth::user();
        $impact = $this->volunteeringHistoryService->calculateVolunteeringImpact($user);
        $recommendations = $this->volunteeringHistoryService->getVolunteeringRecommendations($user);
        
        $insights = [
            'profile_strength' => [
                'score' => $impact['impact_score'],
                'level' => $this->getProfileStrengthLevel($impact['impact_score']),
                'suggestions' => $this->getProfileImprovementSuggestions($impact),
            ],
            'skills_analysis' => [
                'top_skills' => array_slice($impact['skills_gained'], 0, 10),
                'transferable_skills' => $this->identifyTransferableSkills($impact['skills_gained']),
                'skill_gaps' => $this->identifySkillGaps($impact['skills_gained']),
            ],
            'impact_analysis' => [
                'direct_impact' => $impact['direct_impact'],
                'economic_value' => $impact['estimated_economic_value'],
                'consistency_score' => $impact['consistency']['consistency_score'],
            ],
            'recommendations' => $recommendations,
            'next_steps' => $this->getNextStepRecommendations($impact),
        ];
        
        return response()->json($insights);
    }

    /**
     * Get profile strength level based on impact score
     */
    private function getProfileStrengthLevel(int $score): string
    {
        if ($score >= 80) return 'Exceptional';
        if ($score >= 60) return 'Strong';
        if ($score >= 40) return 'Developing';
        return 'Getting Started';
    }

    /**
     * Get profile improvement suggestions
     */
    private function getProfileImprovementSuggestions(array $impact): array
    {
        $suggestions = [];
        
        if ($impact['verification_rate'] < 50) {
            $suggestions[] = [
                'type' => 'verification',
                'title' => 'Verify Your Experiences',
                'description' => 'Add reference contacts to verify your volunteering experiences and increase credibility.',
                'action' => 'Add references to your experiences',
                'priority' => 'high',
            ];
        }
        
        if ($impact['total_hours'] < 50) {
            $suggestions[] = [
                'type' => 'commitment',
                'title' => 'Increase Your Impact',
                'description' => 'Consider taking on more volunteering opportunities to build your experience.',
                'action' => 'Find new volunteering opportunities',
                'priority' => 'medium',
            ];
        }
        
        if (count($impact['skills_gained']) < 5) {
            $suggestions[] = [
                'type' => 'skills',
                'title' => 'Document Your Skills',
                'description' => 'Add more details about skills gained from your volunteering experiences.',
                'action' => 'Update your experience descriptions',
                'priority' => 'medium',
            ];
        }
        
        if ($impact['portfolio_ready_experiences'] < 3) {
            $suggestions[] = [
                'type' => 'portfolio',
                'title' => 'Enhance Your Portfolio',
                'description' => 'Add more detailed descriptions and achievements to make your experiences portfolio-ready.',
                'action' => 'Edit your experiences with more details',
                'priority' => 'low',
            ];
        }
        
        return $suggestions;
    }

    /**
     * Identify transferable skills (simplified version for controller)
     */
    private function identifyTransferableSkills(array $skills): array
    {
        $transferableSkills = [];
        $skillKeywords = ['leadership', 'communication', 'management', 'organization', 'teamwork'];
        
        foreach ($skills as $skill) {
            foreach ($skillKeywords as $keyword) {
                if (stripos($skill, $keyword) !== false) {
                    $transferableSkills[] = $skill;
                    break;
                }
            }
        }
        
        return array_unique($transferableSkills);
    }

    /**
     * Identify potential skill gaps
     */
    private function identifySkillGaps(array $currentSkills): array
    {
        $commonVolunteerSkills = [
            'Leadership', 'Communication', 'Project Management', 'Fundraising',
            'Event Planning', 'Teaching', 'Mentoring', 'Public Speaking',
            'Social Media', 'Data Analysis', 'Customer Service', 'Teamwork'
        ];
        
        $currentSkillsLower = array_map('strtolower', $currentSkills);
        $gaps = [];
        
        foreach ($commonVolunteerSkills as $skill) {
            $found = false;
            foreach ($currentSkillsLower as $currentSkill) {
                if (stripos($currentSkill, strtolower($skill)) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $gaps[] = $skill;
            }
        }
        
        return array_slice($gaps, 0, 5); // Return top 5 gaps
    }

    /**
     * Get next step recommendations
     */
    private function getNextStepRecommendations(array $impact): array
    {
        $recommendations = [];
        
        if ($impact['consistency']['current_streak_months'] == 0) {
            $recommendations[] = [
                'title' => 'Start a New Volunteering Role',
                'description' => 'Begin a new volunteering commitment to restart your impact journey.',
                'action_url' => route('volunteering.opportunities'),
                'priority' => 'high',
            ];
        }
        
        if ($impact['total_organizations'] < 3) {
            $recommendations[] = [
                'title' => 'Diversify Your Experience',
                'description' => 'Try volunteering with different types of organizations to broaden your impact.',
                'action_url' => route('volunteering.opportunities'),
                'priority' => 'medium',
            ];
        }
        
        if ($impact['recognition']['certificates'] == 0) {
            $recommendations[] = [
                'title' => 'Pursue Volunteer Certifications',
                'description' => 'Look for volunteering opportunities that offer certificates or training.',
                'action_url' => route('volunteering.opportunities'),
                'priority' => 'low',
            ];
        }
        
        return $recommendations;
    }
}