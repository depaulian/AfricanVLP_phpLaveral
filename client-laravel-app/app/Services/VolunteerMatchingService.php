<?php

namespace App\Services;

use App\Models\User;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class VolunteerMatchingService
{
    /**
     * Get recommended opportunities for a user
     */
    public function getRecommendedOpportunities(User $user, int $limit = 10): Collection
    {
        $cacheKey = "user_recommendations_{$user->id}";
        
        return Cache::remember($cacheKey, 1800, function () use ($user, $limit) {
            $opportunities = VolunteeringOpportunity::with([
                'organization',
                'category',
                'city',
                'country'
            ])
            ->active()
            ->acceptingApplications()
            ->get();

            $scoredOpportunities = $opportunities->map(function ($opportunity) use ($user) {
                $score = $this->calculateMatchScore($user, $opportunity);
                $opportunity->match_score = $score;
                return $opportunity;
            })
            ->sortByDesc('match_score')
            ->take($limit);

            return $scoredOpportunities->values();
        });
    }

    /**
     * Calculate match score between user and opportunity
     */
    public function calculateMatchScore(User $user, VolunteeringOpportunity $opportunity): float
    {
        $score = 0;
        $maxScore = 100;

        // Interest-based matching (40% weight)
        $interestScore = $this->calculateInterestScore($user, $opportunity);
        $score += $interestScore * 0.4;

        // Skill-based matching (35% weight)
        $skillScore = $this->calculateSkillScore($user, $opportunity);
        $score += $skillScore * 0.35;

        // Location preference (15% weight)
        $locationScore = $this->calculateLocationScore($user, $opportunity);
        $score += $locationScore * 0.15;

        // Experience level matching (10% weight)
        $experienceScore = $this->calculateExperienceScore($user, $opportunity);
        $score += $experienceScore * 0.1;

        return min($score, $maxScore);
    }

    /**
     * Calculate interest-based score
     */
    private function calculateInterestScore(User $user, VolunteeringOpportunity $opportunity): float
    {
        $userInterests = $user->volunteeringInterests()->with('category')->get();
        
        if ($userInterests->isEmpty()) {
            return 50; // Neutral score if no interests specified
        }

        // Direct category match
        $directMatch = $userInterests->firstWhere('category_id', $opportunity->category_id);
        if ($directMatch) {
            return $this->getInterestLevelScore($directMatch->interest_level);
        }

        // Parent category match
        $category = $opportunity->category;
        if ($category && $category->parent_id) {
            $parentMatch = $userInterests->firstWhere('category_id', $category->parent_id);
            if ($parentMatch) {
                return $this->getInterestLevelScore($parentMatch->interest_level) * 0.7;
            }
        }

        // Child category match
        $childMatches = $userInterests->filter(function ($interest) use ($opportunity) {
            return $interest->category->parent_id === $opportunity->category_id;
        });

        if ($childMatches->isNotEmpty()) {
            $avgScore = $childMatches->avg(function ($interest) {
                return $this->getInterestLevelScore($interest->interest_level);
            });
            return $avgScore * 0.8;
        }

        return 20; // Low score for no category match
    }

    /**
     * Calculate skill-based score
     */
    private function calculateSkillScore(User $user, VolunteeringOpportunity $opportunity): float
    {
        if (!$opportunity->required_skills || empty($opportunity->required_skills)) {
            return 70; // Neutral-positive score if no specific skills required
        }

        $userSkills = $user->skills()->get();
        if ($userSkills->isEmpty()) {
            return 30; // Low score if user has no skills listed
        }

        $requiredSkills = $opportunity->required_skills;
        $matchedSkills = 0;
        $totalSkillScore = 0;

        foreach ($requiredSkills as $requiredSkill) {
            $userSkill = $userSkills->firstWhere('skill_name', $requiredSkill);
            if ($userSkill) {
                $matchedSkills++;
                $totalSkillScore += $this->getProficiencyScore($userSkill->proficiency_level);
                
                // Bonus for verified skills
                if ($userSkill->verified) {
                    $totalSkillScore += 10;
                }
            }
        }

        if ($matchedSkills === 0) {
            return 20; // Low score for no skill matches
        }

        $averageSkillScore = $totalSkillScore / $matchedSkills;
        $coverageBonus = ($matchedSkills / count($requiredSkills)) * 20;
        
        return min($averageSkillScore + $coverageBonus, 100);
    }

    /**
     * Calculate location-based score
     */
    private function calculateLocationScore(User $user, VolunteeringOpportunity $opportunity): float
    {
        // Remote opportunities get high score
        if ($opportunity->location_type === 'remote') {
            return 90;
        }

        // If user has no location preference, give neutral score
        if (!$user->city_id && !$user->country_id) {
            return 50;
        }

        // Same city match
        if ($user->city_id && $user->city_id === $opportunity->city_id) {
            return 100;
        }

        // Same country match
        if ($user->country_id && $user->country_id === $opportunity->country_id) {
            return 70;
        }

        // Hybrid opportunities get medium score
        if ($opportunity->location_type === 'hybrid') {
            return 60;
        }

        return 30; // Different location
    }

    /**
     * Calculate experience level score
     */
    private function calculateExperienceScore(User $user, VolunteeringOpportunity $opportunity): float
    {
        if ($opportunity->experience_level === 'any') {
            return 80;
        }

        // Get user's average skill level as experience indicator
        $userSkills = $user->skills()->get();
        if ($userSkills->isEmpty()) {
            return $opportunity->experience_level === 'beginner' ? 80 : 40;
        }

        $experienceLevels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
        $avgUserLevel = $userSkills->avg(function ($skill) use ($experienceLevels) {
            return $experienceLevels[$skill->proficiency_level] ?? 1;
        });

        $requiredLevel = $experienceLevels[$opportunity->experience_level] ?? 1;
        $userLevelCategory = $this->getLevelCategory($avgUserLevel);

        if ($userLevelCategory === $opportunity->experience_level) {
            return 100;
        }

        // Calculate distance between levels
        $levelDifference = abs($avgUserLevel - $requiredLevel);
        return max(100 - ($levelDifference * 25), 20);
    }

    /**
     * Get score for interest level
     */
    private function getInterestLevelScore(string $level): float
    {
        return match ($level) {
            'high' => 100,
            'medium' => 70,
            'low' => 40,
            default => 50
        };
    }

    /**
     * Get score for proficiency level
     */
    private function getProficiencyScore(string $level): float
    {
        return match ($level) {
            'expert' => 100,
            'advanced' => 85,
            'intermediate' => 70,
            'beginner' => 55,
            default => 50
        };
    }

    /**
     * Convert numeric level to category
     */
    private function getLevelCategory(float $level): string
    {
        if ($level >= 3.5) return 'expert';
        if ($level >= 2.5) return 'advanced';
        if ($level >= 1.5) return 'intermediate';
        return 'beginner';
    }

    /**
     * Find similar volunteers for an opportunity
     */
    public function findSimilarVolunteers(VolunteeringOpportunity $opportunity, int $limit = 10): Collection
    {
        $acceptedApplications = $opportunity->acceptedApplications()->with('user.skills', 'user.volunteeringInterests')->get();
        
        if ($acceptedApplications->isEmpty()) {
            return collect();
        }

        // Get skills and interests from accepted volunteers
        $commonSkills = collect();
        $commonInterests = collect();

        foreach ($acceptedApplications as $application) {
            $commonSkills = $commonSkills->merge($application->user->skills->pluck('skill_name'));
            $commonInterests = $commonInterests->merge($application->user->volunteeringInterests->pluck('category_id'));
        }

        $commonSkills = $commonSkills->unique()->values();
        $commonInterests = $commonInterests->unique()->values();

        // Find users with similar profiles who haven't applied
        $appliedUserIds = $opportunity->applications()->pluck('user_id');
        
        $similarUsers = User::whereNotIn('id', $appliedUserIds)
            ->where(function ($query) use ($commonSkills, $commonInterests) {
                $query->whereHas('skills', function ($skillQuery) use ($commonSkills) {
                    $skillQuery->whereIn('skill_name', $commonSkills);
                })
                ->orWhereHas('volunteeringInterests', function ($interestQuery) use ($commonInterests) {
                    $interestQuery->whereIn('category_id', $commonInterests);
                });
            })
            ->with(['skills', 'volunteeringInterests'])
            ->limit($limit * 2) // Get more to score and filter
            ->get();

        // Score and rank similar users
        $scoredUsers = $similarUsers->map(function ($user) use ($opportunity) {
            $score = $this->calculateMatchScore($user, $opportunity);
            $user->match_score = $score;
            return $user;
        })
        ->sortByDesc('match_score')
        ->take($limit);

        return $scoredUsers->values();
    }

    /**
     * Get trending opportunities based on application activity
     */
    public function getTrendingOpportunities(int $limit = 10): Collection
    {
        return Cache::remember('trending_opportunities', 3600, function () use ($limit) {
            return VolunteeringOpportunity::with([
                'organization',
                'category',
                'city',
                'country'
            ])
            ->active()
            ->acceptingApplications()
            ->withCount([
                'applications as recent_applications_count' => function ($query) {
                    $query->where('applied_at', '>=', now()->subDays(7));
                }
            ])
            ->having('recent_applications_count', '>', 0)
            ->orderByDesc('recent_applications_count')
            ->limit($limit)
            ->get();
        });
    }

    /**
     * Clear user recommendation cache
     */
    public function clearUserRecommendations(User $user): void
    {
        Cache::forget("user_recommendations_{$user->id}");
    }

    /**
     * Clear all matching caches
     */
    public function clearAllCaches(): void
    {
        Cache::forget('trending_opportunities');
        // Clear user-specific caches would require more complex cache tagging
    }

    /**
     * Get match explanation for debugging/transparency
     */
    public function getMatchExplanation(User $user, VolunteeringOpportunity $opportunity): array
    {
        $interestScore = $this->calculateInterestScore($user, $opportunity);
        $skillScore = $this->calculateSkillScore($user, $opportunity);
        $locationScore = $this->calculateLocationScore($user, $opportunity);
        $experienceScore = $this->calculateExperienceScore($user, $opportunity);
        
        $totalScore = ($interestScore * 0.4) + ($skillScore * 0.35) + ($locationScore * 0.15) + ($experienceScore * 0.1);

        return [
            'total_score' => round($totalScore, 2),
            'breakdown' => [
                'interest' => [
                    'score' => round($interestScore, 2),
                    'weight' => 40,
                    'weighted_score' => round($interestScore * 0.4, 2)
                ],
                'skills' => [
                    'score' => round($skillScore, 2),
                    'weight' => 35,
                    'weighted_score' => round($skillScore * 0.35, 2)
                ],
                'location' => [
                    'score' => round($locationScore, 2),
                    'weight' => 15,
                    'weighted_score' => round($locationScore * 0.15, 2)
                ],
                'experience' => [
                    'score' => round($experienceScore, 2),
                    'weight' => 10,
                    'weighted_score' => round($experienceScore * 0.1, 2)
                ]
            ]
        ];
    }
}