<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserVolunteeringHistory;
use App\Models\VolunteerApplication;
use App\Models\VolunteerAssignment;
use App\Models\VolunteerTimeLog;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class VolunteeringHistoryService
{
    /**
     * Create a new volunteering history entry
     */
    public function createHistoryEntry(User $user, array $data): UserVolunteeringHistory
    {
        $data['user_id'] = $user->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        // Calculate total hours if not provided
        if (!isset($data['total_hours']) && isset($data['start_date']) && isset($data['end_date'])) {
            $data['total_hours'] = $this->calculateEstimatedHours($data);
        }

        // Set verification status
        $data['is_verified'] = $data['is_verified'] ?? false;
        $data['verification_status'] = $data['verification_status'] ?? 'pending';

        return UserVolunteeringHistory::create($data);
    }

    /**
     * Update volunteering history entry
     */
    public function updateHistoryEntry(UserVolunteeringHistory $history, array $data): UserVolunteeringHistory
    {
        // Recalculate hours if dates changed
        if ((isset($data['start_date']) || isset($data['end_date'])) && !isset($data['total_hours'])) {
            $data['total_hours'] = $this->calculateEstimatedHours(array_merge($history->toArray(), $data));
        }

        $history->update($data);
        return $history->fresh();
    }

    /**
     * Get comprehensive volunteering history for a user
     */
    public function getUserVolunteeringHistory(User $user, array $options = []): Collection
    {
        $query = UserVolunteeringHistory::where('user_id', $user->id)
            ->with(['organization', 'verifier', 'references']);

        // Apply filters
        if (isset($options['verified_only']) && $options['verified_only']) {
            $query->where('is_verified', true);
        }

        if (isset($options['organization_id'])) {
            $query->where('organization_id', $options['organization_id']);
        }

        if (isset($options['date_from'])) {
            $query->where('start_date', '>=', $options['date_from']);
        }

        if (isset($options['date_to'])) {
            $query->where('end_date', '<=', $options['date_to']);
        }

        // Apply sorting
        $sortBy = $options['sort_by'] ?? 'start_date';
        $sortOrder = $options['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    /**
     * Get volunteering timeline for visual representation
     */
    public function getVolunteeringTimeline(User $user): array
    {
        $history = $this->getUserVolunteeringHistory($user, ['sort_by' => 'start_date', 'sort_order' => 'asc']);
        
        $timeline = [];
        $currentYear = null;

        foreach ($history as $entry) {
            $year = Carbon::parse($entry->start_date)->year;
            
            if ($currentYear !== $year) {
                $currentYear = $year;
                $timeline[$year] = [
                    'year' => $year,
                    'entries' => [],
                    'total_hours' => 0,
                    'total_entries' => 0,
                ];
            }

            $timeline[$year]['entries'][] = [
                'id' => $entry->id,
                'organization' => $entry->organization->name ?? $entry->organization_name,
                'role' => $entry->role_title,
                'start_date' => $entry->start_date,
                'end_date' => $entry->end_date,
                'total_hours' => $entry->total_hours,
                'is_verified' => $entry->is_verified,
                'description' => $entry->description,
                'skills_gained' => $entry->skills_gained,
                'achievements' => $entry->achievements,
            ];

            $timeline[$year]['total_hours'] += $entry->total_hours ?? 0;
            $timeline[$year]['total_entries']++;
        }

        return array_values($timeline);
    }

    /**
     * Calculate comprehensive volunteering impact metrics
     */
    public function calculateVolunteeringImpact(User $user): array
    {
        $history = $this->getUserVolunteeringHistory($user);
        
        $totalHours = $history->sum('hours_contributed') ?? 0;
        $totalOrganizations = $history->pluck('organization_id')->unique()->count();
        $verifiedHours = $history->where('reference_verified', true)->sum('hours_contributed') ?? 0;
        $longestCommitment = $this->getLongestCommitment($history);
        $skillsGained = $this->extractSkillsGained($history);
        $impactAreas = $this->getImpactAreas($history);

        // Calculate direct impact metrics
        $totalPeopleHelped = $history->sum('people_helped') ?? 0;
        $totalFundsRaised = $history->sum('funds_raised') ?? 0;
        $totalEventsOrganized = $history->sum('events_organized') ?? 0;
        $totalCertificates = $history->sum(function($entry) {
            return count($entry->certificates ?? []);
        });
        $totalRecognitions = $history->sum(function($entry) {
            return count($entry->recognitions ?? []);
        });

        // Calculate estimated economic value (using average volunteer hour value)
        $averageHourlyValue = 25; // This could be configurable
        $economicValue = $totalHours * $averageHourlyValue;

        // Calculate impact score
        $impactScore = $this->calculateOverallImpactScore($history);

        // Get recent achievements
        $recentAchievements = $this->getRecentAchievements($history);

        // Calculate consistency metrics
        $consistencyMetrics = $this->calculateConsistencyMetrics($history);

        return [
            'total_hours' => $totalHours,
            'verified_hours' => $verifiedHours,
            'total_organizations' => $totalOrganizations,
            'total_experiences' => $history->count(),
            'longest_commitment_months' => $longestCommitment,
            'skills_gained' => $skillsGained,
            'impact_areas' => $impactAreas,
            'estimated_economic_value' => $economicValue,
            'verification_rate' => $history->count() > 0 ? ($history->where('reference_verified', true)->count() / $history->count()) * 100 : 0,
            'average_hours_per_role' => $history->count() > 0 ? $totalHours / $history->count() : 0,
            
            // Enhanced impact metrics
            'direct_impact' => [
                'people_helped' => $totalPeopleHelped,
                'funds_raised' => $totalFundsRaised,
                'events_organized' => $totalEventsOrganized,
            ],
            'recognition' => [
                'certificates' => $totalCertificates,
                'recognitions' => $totalRecognitions,
            ],
            'impact_score' => $impactScore,
            'recent_achievements' => $recentAchievements,
            'consistency' => $consistencyMetrics,
            'portfolio_ready_experiences' => $history->filter(function($entry) {
                return $entry->isSuitableForPortfolio();
            })->count(),
        ];
    }

    /**
     * Add reference contact for volunteering history
     */
    public function addReference(UserVolunteeringHistory $history, array $referenceData): array
    {
        $references = $history->references ?? [];
        
        $reference = [
            'id' => uniqid(),
            'name' => $referenceData['name'],
            'title' => $referenceData['title'] ?? null,
            'email' => $referenceData['email'] ?? null,
            'phone' => $referenceData['phone'] ?? null,
            'relationship' => $referenceData['relationship'] ?? 'supervisor',
            'can_contact' => $referenceData['can_contact'] ?? true,
            'added_at' => now()->toISOString(),
        ];

        $references[] = $reference;
        
        $history->update(['references' => $references]);
        
        return $reference;
    }

    /**
     * Remove reference contact
     */
    public function removeReference(UserVolunteeringHistory $history, string $referenceId): bool
    {
        $references = $history->references ?? [];
        
        $references = array_filter($references, function ($ref) use ($referenceId) {
            return $ref['id'] !== $referenceId;
        });

        $history->update(['references' => array_values($references)]);
        
        return true;
    }

    /**
     * Upload and attach certificate or document
     */
    public function uploadCertificate(UserVolunteeringHistory $history, UploadedFile $file): string
    {
        $filename = 'volunteering_certificates/' . $history->user_id . '/' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        $path = Storage::disk('public')->putFileAs(
            dirname($filename),
            $file,
            basename($filename)
        );

        // Update history with certificate path
        $certificates = $history->certificates ?? [];
        $certificates[] = [
            'id' => uniqid(),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'uploaded_at' => now()->toISOString(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];

        $history->update(['certificates' => $certificates]);

        return $path;
    }

    /**
     * Generate comprehensive volunteering portfolio export
     */
    public function generatePortfolioExport(User $user, string $format = 'pdf'): array
    {
        $history = $this->getUserVolunteeringHistory($user, ['verified_only' => false]);
        $portfolioHistory = $history->filter(function($entry) {
            return $entry->isSuitableForPortfolio();
        });
        
        $impact = $this->calculateVolunteeringImpact($user);
        $timeline = $this->getVolunteeringTimeline($user);
        $impactReport = $this->generateImpactReport($user);

        $portfolioData = [
            'portfolio_info' => [
                'title' => 'Volunteering Portfolio',
                'subtitle' => 'A comprehensive record of volunteer service and community impact',
                'generated_at' => now()->toISOString(),
                'format' => $format,
                'verification_level' => $this->getVerificationLevel($impact['verification_rate']),
            ],
            'volunteer_profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'profile_completion' => $user->profile ? $user->profile->completion_percentage : 0,
                'member_since' => $user->created_at->format('F Y'),
                'total_experiences' => $history->count(),
                'portfolio_ready_experiences' => $portfolioHistory->count(),
            ],
            'executive_summary' => [
                'total_volunteer_hours' => $impact['total_hours'],
                'verified_hours' => $impact['verified_hours'],
                'organizations_served' => $impact['total_organizations'],
                'impact_score' => $impact['impact_score'],
                'consistency_score' => $impact['consistency']['consistency_score'],
                'economic_value_contributed' => $impact['estimated_economic_value'],
                'people_directly_helped' => $impact['direct_impact']['people_helped'],
                'funds_raised' => $impact['direct_impact']['funds_raised'],
                'events_organized' => $impact['direct_impact']['events_organized'],
            ],
            'skills_and_competencies' => [
                'core_skills' => array_slice($impact['skills_gained'], 0, 15),
                'skill_development_timeline' => $impactReport['detailed_breakdown']['skills_development'],
                'transferable_skills' => $this->identifyTransferableSkills($impact['skills_gained']),
            ],
            'volunteer_experiences' => $portfolioHistory->map(function ($entry) {
                return [
                    'organization' => $entry->organization->name ?? $entry->organization_name,
                    'role_title' => $entry->role_title,
                    'duration' => $entry->duration,
                    'period' => $entry->start_date->format('M Y') . ' - ' . ($entry->end_date ? $entry->end_date->format('M Y') : 'Present'),
                    'hours_contributed' => $entry->hours_contributed,
                    'description' => $entry->description,
                    'key_achievements' => $entry->impact_description,
                    'impact_metrics' => [
                        'people_helped' => $entry->people_helped,
                        'funds_raised' => $entry->funds_raised,
                        'events_organized' => $entry->events_organized,
                    ],
                    'skills_gained' => $entry->skills_gained,
                    'impact_score' => $entry->impact_score,
                    'verification_status' => $entry->reference_verified ? 'Verified' : 'Unverified',
                    'certificates' => $entry->formatted_certificates,
                    'recognitions' => $entry->formatted_recognitions,
                    'reference_available' => $entry->hasReferenceContact(),
                ];
            })->values(),
            'impact_analysis' => [
                'by_organization' => $impactReport['detailed_breakdown']['by_organization'],
                'by_impact_area' => $impactReport['detailed_breakdown']['by_impact_area'],
                'yearly_progression' => $timeline,
                'consistency_metrics' => $impact['consistency'],
            ],
            'achievements_and_recognition' => [
                'certificates_earned' => $impact['recognition']['certificates'],
                'recognitions_received' => $impact['recognition']['recognitions'],
                'recent_achievements' => $impact['recent_achievements'],
                'milestone_achievements' => $this->getMilestoneAchievements($impact),
            ],
            'references_and_verification' => [
                'verification_rate' => $impact['verification_rate'],
                'verified_experiences' => $history->where('reference_verified', true)->count(),
                'available_references' => $history->filter(function($entry) {
                    return $entry->hasReferenceContact() && $entry->reference_verified;
                })->map(function($entry) {
                    return [
                        'organization' => $entry->organization_name,
                        'role' => $entry->role_title,
                        'reference_name' => $entry->reference_contact,
                        'reference_title' => $entry->reference_position,
                        'relationship' => 'Supervisor', // Default, could be enhanced
                        'verified_date' => $entry->reference_verified_at ? $entry->reference_verified_at->format('M Y') : null,
                    ];
                })->values(),
            ],
            'recommendations' => $impactReport['recommendations'],
            'appendices' => [
                'detailed_timeline' => $timeline,
                'complete_skills_list' => $impact['skills_gained'],
                'impact_areas_served' => $impact['impact_areas'],
                'portfolio_statistics' => [
                    'total_pages' => $this->calculatePortfolioPages($portfolioHistory),
                    'data_sources' => $history->count(),
                    'verification_sources' => $history->where('reference_verified', true)->count(),
                    'last_updated' => $history->max('updated_at'),
                ],
            ],
        ];

        // Store the export for download
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "volunteering_portfolio_{$user->id}_{$timestamp}.{$format}";
        
        if ($format === 'json') {
            Storage::disk('temp')->put($filename, json_encode($portfolioData, JSON_PRETTY_PRINT));
        } elseif ($format === 'pdf') {
            // This would integrate with a PDF generation service
            $filename = $this->generatePdfPortfolio($portfolioData, $filename);
        }

        return [
            'filename' => $filename,
            'data' => $portfolioData,
            'download_url' => route('profile.volunteering.download-portfolio', ['filename' => $filename]),
            'preview_url' => route('profile.volunteering.preview-portfolio', ['user' => $user->id]),
            'share_url' => route('profile.volunteering.public-portfolio', ['user' => $user->id, 'token' => $this->generateShareToken($user)]),
            'statistics' => [
                'total_experiences' => $history->count(),
                'portfolio_experiences' => $portfolioHistory->count(),
                'total_pages_estimated' => $this->calculatePortfolioPages($portfolioHistory),
                'file_size_estimated' => $this->estimateFileSize($portfolioData, $format),
            ],
        ];
    }

    /**
     * Identify transferable skills from volunteering experience
     */
    private function identifyTransferableSkills(array $skills): array
    {
        $transferableSkillsMap = [
            'leadership' => ['Team Management', 'Project Leadership', 'Strategic Planning'],
            'communication' => ['Public Speaking', 'Written Communication', 'Interpersonal Skills'],
            'project management' => ['Planning', 'Coordination', 'Resource Management'],
            'fundraising' => ['Sales', 'Relationship Building', 'Financial Planning'],
            'event planning' => ['Logistics', 'Vendor Management', 'Timeline Management'],
            'teaching' => ['Training', 'Mentoring', 'Curriculum Development'],
            'counseling' => ['Active Listening', 'Problem Solving', 'Emotional Intelligence'],
        ];

        $transferableSkills = [];
        foreach ($skills as $skill) {
            $skillLower = strtolower($skill);
            foreach ($transferableSkillsMap as $category => $relatedSkills) {
                if (strpos($skillLower, $category) !== false) {
                    $transferableSkills = array_merge($transferableSkills, $relatedSkills);
                }
            }
        }

        return array_unique($transferableSkills);
    }

    /**
     * Get milestone achievements based on impact metrics
     */
    private function getMilestoneAchievements(array $impact): array
    {
        $milestones = [];

        if ($impact['total_hours'] >= 100) {
            $milestones[] = [
                'title' => '100+ Hours Volunteer',
                'description' => 'Contributed over 100 hours of volunteer service',
                'achieved_at' => 'Based on total hours',
                'category' => 'Time Commitment',
            ];
        }

        if ($impact['total_hours'] >= 500) {
            $milestones[] = [
                'title' => '500+ Hours Champion',
                'description' => 'Exceptional commitment with over 500 hours of service',
                'achieved_at' => 'Based on total hours',
                'category' => 'Time Commitment',
            ];
        }

        if ($impact['total_organizations'] >= 5) {
            $milestones[] = [
                'title' => 'Multi-Organization Volunteer',
                'description' => 'Served with 5 or more different organizations',
                'achieved_at' => 'Based on organization diversity',
                'category' => 'Diversity',
            ];
        }

        if ($impact['verification_rate'] >= 80) {
            $milestones[] = [
                'title' => 'Verified Volunteer',
                'description' => 'High verification rate demonstrates credible service',
                'achieved_at' => 'Based on verification rate',
                'category' => 'Credibility',
            ];
        }

        if ($impact['consistency']['consistency_score'] >= 80) {
            $milestones[] = [
                'title' => 'Consistent Contributor',
                'description' => 'Demonstrated consistent volunteer engagement over time',
                'achieved_at' => 'Based on consistency metrics',
                'category' => 'Reliability',
            ];
        }

        return $milestones;
    }

    /**
     * Calculate estimated portfolio pages
     */
    private function calculatePortfolioPages(Collection $portfolioHistory): int
    {
        $basePage = 1; // Cover page
        $summaryPages = 2; // Executive summary and skills
        $experiencePages = ceil($portfolioHistory->count() / 2); // 2 experiences per page
        $appendixPages = 2; // Timeline and references
        
        return $basePage + $summaryPages + $experiencePages + $appendixPages;
    }

    /**
     * Estimate file size based on content and format
     */
    private function estimateFileSize(array $data, string $format): string
    {
        $jsonSize = strlen(json_encode($data));
        
        if ($format === 'json') {
            return $this->formatFileSize($jsonSize);
        } elseif ($format === 'pdf') {
            // PDF is typically 3-5x larger than JSON
            return $this->formatFileSize($jsonSize * 4);
        }
        
        return $this->formatFileSize($jsonSize);
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Generate share token for public portfolio
     */
    private function generateShareToken(User $user): string
    {
        return hash('sha256', $user->id . $user->email . config('app.key') . 'portfolio');
    }

    /**
     * Generate PDF portfolio (placeholder for PDF service integration)
     */
    private function generatePdfPortfolio(array $data, string $filename): string
    {
        // This would integrate with a PDF generation service like DomPDF, wkhtmltopdf, etc.
        // For now, we'll store as JSON and return the filename
        Storage::disk('temp')->put($filename, json_encode($data, JSON_PRETTY_PRINT));
        return $filename;
    }

    /**
     * Verify volunteering history entry
     */
    public function verifyHistoryEntry(UserVolunteeringHistory $history, User $verifier, array $verificationData = []): UserVolunteeringHistory
    {
        $history->update([
            'is_verified' => true,
            'verification_status' => 'verified',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $verificationData['notes'] ?? null,
            'verified_hours' => $verificationData['verified_hours'] ?? $history->total_hours,
        ]);

        return $history->fresh();
    }

    /**
     * Get volunteering statistics for dashboard
     */
    public function getVolunteeringStats(User $user): array
    {
        $history = $this->getUserVolunteeringHistory($user);
        $currentYear = Carbon::now()->year;
        
        $thisYearHistory = $history->filter(function ($entry) use ($currentYear) {
            return Carbon::parse($entry->start_date)->year === $currentYear ||
                   ($entry->end_date && Carbon::parse($entry->end_date)->year === $currentYear) ||
                   (!$entry->end_date && Carbon::parse($entry->start_date)->year <= $currentYear);
        });

        return [
            'total_experiences' => $history->count(),
            'total_hours' => $history->sum('total_hours') ?? 0,
            'verified_experiences' => $history->where('is_verified', true)->count(),
            'this_year_hours' => $thisYearHistory->sum('total_hours') ?? 0,
            'organizations_count' => $history->pluck('organization_id')->unique()->count(),
            'longest_commitment' => $this->getLongestCommitment($history),
            'recent_activity' => $history->sortByDesc('updated_at')->take(3)->values(),
        ];
    }

    /**
     * Search volunteering history
     */
    public function searchHistory(User $user, string $query): Collection
    {
        return UserVolunteeringHistory::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('role_title', 'LIKE', "%{$query}%")
                  ->orWhere('organization_name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('skills_gained', 'LIKE', "%{$query}%")
                  ->orWhere('achievements', 'LIKE', "%{$query}%");
            })
            ->with(['organization', 'verifier'])
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * Get volunteering recommendations based on history
     */
    public function getVolunteeringRecommendations(User $user): array
    {
        $history = $this->getUserVolunteeringHistory($user);
        $skills = $this->extractSkillsGained($history);
        $impactAreas = $this->getImpactAreas($history);
        
        // This would integrate with the volunteering opportunities system
        // For now, return structured recommendations
        return [
            'based_on_skills' => $skills,
            'based_on_interests' => $impactAreas,
            'suggested_organizations' => $this->getSuggestedOrganizations($history),
            'skill_development_opportunities' => $this->getSkillDevelopmentOpportunities($skills),
        ];
    }

    /**
     * Calculate estimated hours based on dates and frequency
     */
    private function calculateEstimatedHours(array $data): int
    {
        if (!isset($data['start_date'])) {
            return 0;
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = isset($data['end_date']) ? Carbon::parse($data['end_date']) : Carbon::now();
        
        $weeks = $startDate->diffInWeeks($endDate);
        $hoursPerWeek = $data['hours_per_week'] ?? 4; // Default assumption
        
        return (int) ($weeks * $hoursPerWeek);
    }

    /**
     * Get longest commitment in months
     */
    private function getLongestCommitment(Collection $history): int
    {
        $longest = 0;
        
        foreach ($history as $entry) {
            $start = Carbon::parse($entry->start_date);
            $end = $entry->end_date ? Carbon::parse($entry->end_date) : Carbon::now();
            $months = $start->diffInMonths($end);
            
            if ($months > $longest) {
                $longest = $months;
            }
        }
        
        return $longest;
    }

    /**
     * Extract skills gained from history
     */
    private function extractSkillsGained(Collection $history): array
    {
        $allSkills = [];
        
        foreach ($history as $entry) {
            if ($entry->skills_gained) {
                $skills = is_array($entry->skills_gained) 
                    ? $entry->skills_gained 
                    : explode(',', $entry->skills_gained);
                
                foreach ($skills as $skill) {
                    $skill = trim($skill);
                    if ($skill) {
                        $allSkills[$skill] = ($allSkills[$skill] ?? 0) + 1;
                    }
                }
            }
        }
        
        // Sort by frequency
        arsort($allSkills);
        
        return array_keys(array_slice($allSkills, 0, 20)); // Top 20 skills
    }

    /**
     * Get impact areas from history
     */
    private function getImpactAreas(Collection $history): array
    {
        $areas = [];
        
        foreach ($history as $entry) {
            if ($entry->impact_area) {
                $areas[$entry->impact_area] = ($areas[$entry->impact_area] ?? 0) + 1;
            }
        }
        
        arsort($areas);
        
        return array_keys($areas);
    }

    /**
     * Get suggested organizations based on history
     */
    private function getSuggestedOrganizations(Collection $history): array
    {
        // This would integrate with the organization system
        // For now, return placeholder data
        return [
            'similar_organizations' => [],
            'partner_organizations' => [],
            'recommended_based_on_skills' => [],
        ];
    }

    /**
     * Get skill development opportunities
     */
    private function getSkillDevelopmentOpportunities(array $currentSkills): array
    {
        // This would integrate with learning/training systems
        // For now, return placeholder structure
        return [
            'complementary_skills' => [],
            'advanced_skills' => [],
            'certification_opportunities' => [],
        ];
    }

    /**
     * Bulk import volunteering history from external sources
     */
    public function bulkImportHistory(User $user, array $historyData, string $source = 'manual'): array
    {
        $imported = [];
        $errors = [];
        
        foreach ($historyData as $index => $data) {
            try {
                $data['import_source'] = $source;
                $data['import_date'] = now();
                
                $history = $this->createHistoryEntry($user, $data);
                $imported[] = $history;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'data' => $data,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return [
            'imported_count' => count($imported),
            'error_count' => count($errors),
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Generate volunteering certificate
     */
    public function generateCertificate(UserVolunteeringHistory $history): array
    {
        // This would integrate with a certificate generation system
        // For now, return certificate data structure
        return [
            'certificate_id' => 'CERT-' . $history->id . '-' . now()->format('Ymd'),
            'volunteer_name' => $history->user->name,
            'organization' => $history->organization->name ?? $history->organization_name,
            'role' => $history->role_title,
            'period' => $history->start_date . ' to ' . ($history->end_date ?? 'Present'),
            'total_hours' => $history->total_hours,
            'issued_date' => now()->toDateString(),
            'verification_status' => $history->is_verified ? 'Verified' : 'Unverified',
        ];
    }

    /**
     * Calculate overall impact score based on multiple factors
     */
    private function calculateOverallImpactScore(Collection $history): int
    {
        $totalScore = 0;
        $maxScore = 100;

        foreach ($history as $entry) {
            $totalScore += $entry->impact_score;
        }

        if ($history->count() > 0) {
            $averageScore = $totalScore / $history->count();
            
            // Bonus for consistency and variety
            $organizationBonus = min($history->pluck('organization_id')->unique()->count() * 2, 10);
            $longevityBonus = min($this->getLongestCommitment($history) / 12 * 5, 15);
            $verificationBonus = ($history->where('reference_verified', true)->count() / max($history->count(), 1)) * 10;
            
            return min(round($averageScore + $organizationBonus + $longevityBonus + $verificationBonus), $maxScore);
        }

        return 0;
    }

    /**
     * Get recent achievements from volunteering history
     */
    private function getRecentAchievements(Collection $history): array
    {
        $achievements = [];
        
        foreach ($history->sortByDesc('updated_at')->take(10) as $entry) {
            if ($entry->recognitions) {
                foreach ($entry->recognitions as $recognition) {
                    $achievements[] = [
                        'type' => 'recognition',
                        'title' => $recognition['title'] ?? 'Recognition',
                        'organization' => $entry->organization_name,
                        'date' => $recognition['date_received'] ?? $entry->updated_at->toDateString(),
                        'description' => $recognition['description'] ?? '',
                    ];
                }
            }
            
            if ($entry->certificates) {
                foreach ($entry->certificates as $certificate) {
                    $achievements[] = [
                        'type' => 'certificate',
                        'title' => $certificate['name'] ?? 'Certificate',
                        'organization' => $entry->organization_name,
                        'date' => $certificate['date_issued'] ?? $entry->updated_at->toDateString(),
                        'issuer' => $certificate['issuer'] ?? $entry->organization_name,
                    ];
                }
            }
        }

        // Sort by date and return most recent
        usort($achievements, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($achievements, 0, 5);
    }

    /**
     * Calculate consistency metrics for volunteering
     */
    private function calculateConsistencyMetrics(Collection $history): array
    {
        if ($history->isEmpty()) {
            return [
                'consistency_score' => 0,
                'average_commitment_months' => 0,
                'gaps_between_roles' => 0,
                'current_streak_months' => 0,
            ];
        }

        $sortedHistory = $history->sortBy('start_date');
        $totalCommitmentMonths = 0;
        $gaps = 0;
        $gapMonths = 0;

        $previousEndDate = null;
        foreach ($sortedHistory as $entry) {
            $startDate = Carbon::parse($entry->start_date);
            $endDate = $entry->end_date ? Carbon::parse($entry->end_date) : Carbon::now();
            
            $commitmentMonths = $startDate->diffInMonths($endDate);
            $totalCommitmentMonths += $commitmentMonths;

            if ($previousEndDate && $startDate->diffInMonths($previousEndDate) > 1) {
                $gaps++;
                $gapMonths += $startDate->diffInMonths($previousEndDate);
            }

            $previousEndDate = $endDate;
        }

        // Calculate current streak
        $currentStreak = 0;
        $currentRoles = $history->where('is_current', true);
        if ($currentRoles->isNotEmpty()) {
            $earliestCurrent = $currentRoles->min('start_date');
            $currentStreak = Carbon::parse($earliestCurrent)->diffInMonths(Carbon::now());
        }

        $averageCommitment = $history->count() > 0 ? $totalCommitmentMonths / $history->count() : 0;
        $consistencyScore = max(0, 100 - ($gaps * 10) - min($gapMonths * 2, 50));

        return [
            'consistency_score' => round($consistencyScore),
            'average_commitment_months' => round($averageCommitment, 1),
            'gaps_between_roles' => $gaps,
            'current_streak_months' => $currentStreak,
            'total_active_months' => $totalCommitmentMonths,
        ];
    }

    /**
     * Generate comprehensive volunteering certificate
     */
    public function generateComprehensiveCertificate(User $user): array
    {
        $impact = $this->calculateVolunteeringImpact($user);
        $history = $this->getUserVolunteeringHistory($user, ['verified_only' => true]);

        return [
            'certificate_id' => 'VOLUNTEER-CERT-' . $user->id . '-' . now()->format('Ymd'),
            'volunteer_name' => $user->name,
            'total_hours' => $impact['total_hours'],
            'verified_hours' => $impact['verified_hours'],
            'organizations_count' => $impact['total_organizations'],
            'impact_score' => $impact['impact_score'],
            'skills_gained' => array_slice($impact['skills_gained'], 0, 10),
            'major_achievements' => $impact['recent_achievements'],
            'consistency_score' => $impact['consistency']['consistency_score'],
            'economic_value' => $impact['estimated_economic_value'],
            'issued_date' => now()->toDateString(),
            'verification_level' => $this->getVerificationLevel($impact['verification_rate']),
            'certificate_url' => route('profile.volunteering.certificate', ['user' => $user->id]),
        ];
    }

    /**
     * Get verification level based on verification rate
     */
    private function getVerificationLevel(float $verificationRate): string
    {
        if ($verificationRate >= 80) return 'Gold';
        if ($verificationRate >= 60) return 'Silver';
        if ($verificationRate >= 40) return 'Bronze';
        return 'Basic';
    }

    /**
     * Generate detailed impact report
     */
    public function generateImpactReport(User $user): array
    {
        $impact = $this->calculateVolunteeringImpact($user);
        $history = $this->getUserVolunteeringHistory($user);
        $timeline = $this->getVolunteeringTimeline($user);

        return [
            'report_id' => 'IMPACT-' . $user->id . '-' . now()->format('Ymd-His'),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'profile_completion' => $user->profile ? $user->profile->completion_percentage : 0,
            ],
            'summary' => $impact,
            'detailed_breakdown' => [
                'by_year' => $timeline,
                'by_organization' => $this->getImpactByOrganization($history),
                'by_impact_area' => $this->getImpactByArea($history),
                'skills_development' => $this->getSkillsDevelopmentTimeline($history),
            ],
            'recommendations' => $this->getVolunteeringRecommendations($user),
            'generated_at' => now()->toISOString(),
            'report_url' => route('profile.volunteering.impact-report', ['user' => $user->id]),
        ];
    }

    /**
     * Get impact breakdown by organization
     */
    private function getImpactByOrganization(Collection $history): array
    {
        $byOrganization = [];
        
        foreach ($history as $entry) {
            $orgName = $entry->organization_name;
            if (!isset($byOrganization[$orgName])) {
                $byOrganization[$orgName] = [
                    'organization' => $orgName,
                    'total_hours' => 0,
                    'roles_count' => 0,
                    'people_helped' => 0,
                    'funds_raised' => 0,
                    'events_organized' => 0,
                    'impact_score' => 0,
                ];
            }
            
            $byOrganization[$orgName]['total_hours'] += $entry->hours_contributed ?? 0;
            $byOrganization[$orgName]['roles_count']++;
            $byOrganization[$orgName]['people_helped'] += $entry->people_helped ?? 0;
            $byOrganization[$orgName]['funds_raised'] += $entry->funds_raised ?? 0;
            $byOrganization[$orgName]['events_organized'] += $entry->events_organized ?? 0;
            $byOrganization[$orgName]['impact_score'] += $entry->impact_score;
        }

        // Sort by total hours descending
        uasort($byOrganization, function($a, $b) {
            return $b['total_hours'] - $a['total_hours'];
        });

        return array_values($byOrganization);
    }

    /**
     * Get impact breakdown by impact area
     */
    private function getImpactByArea(Collection $history): array
    {
        $byArea = [];
        
        foreach ($history as $entry) {
            $area = $entry->impact_area ?? 'Other';
            if (!isset($byArea[$area])) {
                $byArea[$area] = [
                    'area' => $area,
                    'total_hours' => 0,
                    'experiences_count' => 0,
                    'organizations' => [],
                    'impact_score' => 0,
                ];
            }
            
            $byArea[$area]['total_hours'] += $entry->hours_contributed ?? 0;
            $byArea[$area]['experiences_count']++;
            $byArea[$area]['organizations'][] = $entry->organization_name;
            $byArea[$area]['impact_score'] += $entry->impact_score;
        }

        // Remove duplicates from organizations and sort by total hours
        foreach ($byArea as &$area) {
            $area['organizations'] = array_unique($area['organizations']);
            $area['organizations_count'] = count($area['organizations']);
        }

        uasort($byArea, function($a, $b) {
            return $b['total_hours'] - $a['total_hours'];
        });

        return array_values($byArea);
    }

    /**
     * Get skills development timeline
     */
    private function getSkillsDevelopmentTimeline(Collection $history): array
    {
        $skillsTimeline = [];
        
        foreach ($history->sortBy('start_date') as $entry) {
            if ($entry->skills_gained) {
                $skills = is_array($entry->skills_gained) ? $entry->skills_gained : explode(',', $entry->skills_gained);
                
                foreach ($skills as $skill) {
                    $skill = trim($skill);
                    if ($skill) {
                        $skillsTimeline[] = [
                            'skill' => $skill,
                            'acquired_date' => $entry->start_date,
                            'organization' => $entry->organization_name,
                            'role' => $entry->role_title,
                            'verified' => $entry->reference_verified,
                        ];
                    }
                }
            }
        }

        return $skillsTimeline;
    }

    /**
     * Get volunteering history for API export
     */
    public function getHistoryForApi(User $user, array $filters = []): array
    {
        $history = $this->getUserVolunteeringHistory($user, $filters);
        
        return [
            'user_id' => $user->id,
            'total_count' => $history->count(),
            'total_hours' => $history->sum('hours_contributed'),
            'verified_count' => $history->where('reference_verified', true)->count(),
            'history' => $history->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'organization' => $entry->organization->name ?? $entry->organization_name,
                    'role' => $entry->role_title,
                    'start_date' => $entry->start_date,
                    'end_date' => $entry->end_date,
                    'total_hours' => $entry->hours_contributed,
                    'is_verified' => $entry->reference_verified,
                    'skills_gained' => $entry->skills_gained,
                    'impact_area' => $entry->impact_area,
                    'impact_score' => $entry->impact_score,
                    'people_helped' => $entry->people_helped,
                    'funds_raised' => $entry->funds_raised,
                    'events_organized' => $entry->events_organized,
                ];
            })->values(),
        ];
    }
}