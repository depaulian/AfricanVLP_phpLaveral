<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSkill;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SkillsMatchingService
{
    /**
     * Find opportunities that match user's skills
     */
    public function findMatchingOpportunities(User $user, array $options = []): Collection
    {
        $limit = $options['limit'] ?? 20;
        $minMatchScore = $options['min_score'] ?? 60;
        $includePartialMatches = $options['include_partial'] ?? true;
        
        $userSkills = $user->skills()->get();
        
        if ($userSkills->isEmpty()) {
            return collect();
        }
        
        $skillNames = $userSkills->pluck('skill_name')->toArray();
        
        $opportunities = VolunteeringOpportunity::with([
            'organization',
            'category',
            'city',
            'country'
        ])
        ->active()
        ->acceptingApplications()
        ->where(function ($query) use ($skillNames, $includePartialMatches) {
            if ($includePartialMatches) {
                // Include opportunities that require any of the user's skills
                foreach ($skillNames as $skill) {
                    $query->orWhereJsonContains('required_skills', $skill);
                }
            } else {
                // Only include opportunities where user has all required skills
                $query->where(function ($subQuery) use ($skillNames) {
                    $subQuery->whereNull('required_skills')
                            ->orWhere('required_skills', '[]')
                            ->orWhere(function ($skillQuery) use ($skillNames) {
                                foreach ($skillNames as $skill) {
                                    $skillQuery->whereJsonContains('required_skills', $skill);
                                }
                            });
                });
            }
        })
        ->get();
        
        // Calculate match scores and filter
        $scoredOpportunities = $opportunities->map(function ($opportunity) use ($user, $userSkills) {
            $score = $this->calculateSkillMatchScore($userSkills, $opportunity);
            $opportunity->skill_match_score = $score;
            $opportunity->matched_skills = $this->getMatchedSkills($userSkills, $opportunity);
            $opportunity->missing_skills = $this->getMissingSkills($userSkills, $opportunity);
            return $opportunity;
        })
        ->filter(function ($opportunity) use ($minMatchScore) {
            return $opportunity->skill_match_score >= $minMatchScore;
        })
        ->sortByDesc('skill_match_score')
        ->take($limit);
        
        return $scoredOpportunities->values();
    }
    
    /**
     * Calculate skill match score between user skills and opportunity requirements
     */
    public function calculateSkillMatchScore(Collection $userSkills, VolunteeringOpportunity $opportunity): float
    {
        $requiredSkills = $opportunity->required_skills ?? [];
        
        if (empty($requiredSkills)) {
            return 75; // Neutral score for opportunities with no specific requirements
        }
        
        $totalScore = 0;
        $matchedSkills = 0;
        $skillWeights = [];
        
        foreach ($requiredSkills as $requiredSkill) {
            $userSkill = $userSkills->firstWhere('skill_name', $requiredSkill);
            
            if ($userSkill) {
                $matchedSkills++;
                $proficiencyScore = $this->getProficiencyScore($userSkill->proficiency_level);
                $experienceBonus = $this->getExperienceBonus($userSkill->years_experience);
                $verificationBonus = $userSkill->verified ? 15 : 0;
                
                $skillScore = $proficiencyScore + $experienceBonus + $verificationBonus;
                $totalScore += min($skillScore, 100);
                $skillWeights[$requiredSkill] = $skillScore;
            }
        }
        
        if ($matchedSkills === 0) {
            return 0;
        }
        
        // Calculate base score
        $averageScore = $totalScore / $matchedSkills;
        
        // Apply coverage bonus/penalty
        $coverageRatio = $matchedSkills / count($requiredSkills);
        $coverageMultiplier = 0.5 + ($coverageRatio * 0.5); // 0.5 to 1.0
        
        $finalScore = $averageScore * $coverageMultiplier;
        
        // Bonus for having all required skills
        if ($coverageRatio === 1.0) {
            $finalScore += 10;
        }
        
        return min($finalScore, 100);
    }
    
    /**
     * Get matched skills between user and opportunity
     */
    public function getMatchedSkills(Collection $userSkills, VolunteeringOpportunity $opportunity): array
    {
        $requiredSkills = $opportunity->required_skills ?? [];
        $matched = [];
        
        foreach ($requiredSkills as $requiredSkill) {
            $userSkill = $userSkills->firstWhere('skill_name', $requiredSkill);
            if ($userSkill) {
                $matched[] = [
                    'skill' => $requiredSkill,
                    'proficiency' => $userSkill->proficiency_level,
                    'experience' => $userSkill->years_experience,
                    'verified' => $userSkill->verified
                ];
            }
        }
        
        return $matched;
    }
    
    /**
     * Get missing skills for an opportunity
     */
    public function getMissingSkills(Collection $userSkills, VolunteeringOpportunity $opportunity): array
    {
        $requiredSkills = $opportunity->required_skills ?? [];
        $userSkillNames = $userSkills->pluck('skill_name')->toArray();
        
        return array_diff($requiredSkills, $userSkillNames);
    }
    
    /**
     * Find users with specific skills for opportunity matching
     */
    public function findUsersWithSkills(array $skillNames, array $options = []): Collection
    {
        $minProficiency = $options['min_proficiency'] ?? 'beginner';
        $requireVerified = $options['require_verified'] ?? false;
        $limit = $options['limit'] ?? 50;
        
        $query = User::whereHas('skills', function ($skillQuery) use ($skillNames, $minProficiency, $requireVerified) {
            $skillQuery->whereIn('skill_name', $skillNames);
            
            if ($minProficiency !== 'beginner') {
                $proficiencyLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
                $minIndex = array_search($minProficiency, $proficiencyLevels);
                $allowedLevels = array_slice($proficiencyLevels, $minIndex);
                $skillQuery->whereIn('proficiency_level', $allowedLevels);
            }
            
            if ($requireVerified) {
                $skillQuery->where('verified', true);
            }
        })
        ->with(['skills' => function ($query) use ($skillNames) {
            $query->whereIn('skill_name', $skillNames);
        }])
        ->limit($limit)
        ->get();
        
        // Calculate skill match scores for each user
        return $query->map(function ($user) use ($skillNames) {
            $matchScore = $this->calculateUserSkillScore($user->skills, $skillNames);
            $user->skill_match_score = $matchScore;
            return $user;
        })
        ->sortByDesc('skill_match_score')
        ->values();
    }
    
    /**
     * Calculate user's skill score for given skill requirements
     */
    private function calculateUserSkillScore(Collection $userSkills, array $requiredSkills): float
    {
        $totalScore = 0;
        $matchedSkills = 0;
        
        foreach ($requiredSkills as $requiredSkill) {
            $userSkill = $userSkills->firstWhere('skill_name', $requiredSkill);
            if ($userSkill) {
                $matchedSkills++;
                $score = $this->getProficiencyScore($userSkill->proficiency_level);
                $score += $this->getExperienceBonus($userSkill->years_experience);
                $score += $userSkill->verified ? 15 : 0;
                $totalScore += min($score, 100);
            }
        }
        
        if ($matchedSkills === 0) {
            return 0;
        }
        
        $averageScore = $totalScore / $matchedSkills;
        $coverageBonus = ($matchedSkills / count($requiredSkills)) * 20;
        
        return min($averageScore + $coverageBonus, 100);
    }
    
    /**
     * Get trending skills based on opportunity requirements
     */
    public function getTrendingSkills(int $limit = 20): array
    {
        return Cache::remember('trending_skills', 3600, function () use ($limit) {
            $skillCounts = DB::table('volunteering_opportunities')
                ->where('status', 'active')
                ->whereNotNull('required_skills')
                ->where('required_skills', '!=', '[]')
                ->get()
                ->flatMap(function ($opportunity) {
                    return json_decode($opportunity->required_skills, true) ?? [];
                })
                ->countBy()
                ->sortDesc()
                ->take($limit);
            
            return $skillCounts->map(function ($count, $skill) {
                return [
                    'skill' => $skill,
                    'demand_count' => $count,
                    'trend_score' => $this->calculateTrendScore($skill, $count)
                ];
            })->values()->toArray();
        });
    }
    
    /**
     * Analyze skill gaps for a user
     */
    public function analyzeSkillGaps(User $user): array
    {
        $userSkills = $user->skills()->pluck('skill_name')->toArray();
        $userInterests = $user->volunteeringInterests()->pluck('category_id')->toArray();
        
        // Get skills commonly required in user's interest areas
        $recommendedSkills = DB::table('volunteering_opportunities')
            ->whereIn('category_id', $userInterests)
            ->where('status', 'active')
            ->whereNotNull('required_skills')
            ->where('required_skills', '!=', '[]')
            ->get()
            ->flatMap(function ($opportunity) {
                return json_decode($opportunity->required_skills, true) ?? [];
            })
            ->countBy()
            ->sortDesc();
        
        $gaps = [];
        $recommendations = [];
        
        foreach ($recommendedSkills as $skill => $demandCount) {
            if (!in_array($skill, $userSkills)) {
                $gaps[] = [
                    'skill' => $skill,
                    'demand_count' => $demandCount,
                    'priority' => $this->calculateSkillPriority($skill, $demandCount, $userInterests)
                ];
            }
        }
        
        // Sort by priority
        usort($gaps, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        return [
            'skill_gaps' => array_slice($gaps, 0, 10),
            'recommendations' => $this->generateSkillRecommendations($gaps, $user)
        ];
    }
    
    /**
     * Generate skill development recommendations
     */
    private function generateSkillRecommendations(array $gaps, User $user): array
    {
        $recommendations = [];
        
        foreach (array_slice($gaps, 0, 5) as $gap) {
            $recommendations[] = [
                'skill' => $gap['skill'],
                'reason' => "High demand in your interest areas ({$gap['demand_count']} opportunities)",
                'suggested_level' => 'intermediate',
                'learning_resources' => $this->getSuggestedLearningResources($gap['skill']),
                'related_opportunities' => $this->getOpportunitiesForSkill($gap['skill'], 3)
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Get suggested learning resources for a skill
     */
    private function getSuggestedLearningResources(string $skill): array
    {
        // This could be enhanced to integrate with actual learning platforms
        return [
            'online_courses' => "Search for '{$skill}' courses on Coursera, Udemy, or edX",
            'certifications' => "Look for professional certifications in {$skill}",
            'practice_projects' => "Find volunteer projects that use {$skill} to gain experience",
            'mentorship' => "Connect with experienced volunteers who have {$skill} expertise"
        ];
    }
    
    /**
     * Get opportunities that require a specific skill
     */
    private function getOpportunitiesForSkill(string $skill, int $limit = 5): array
    {
        return VolunteeringOpportunity::whereJsonContains('required_skills', $skill)
            ->active()
            ->acceptingApplications()
            ->with(['organization', 'category'])
            ->limit($limit)
            ->get()
            ->map(function ($opportunity) {
                return [
                    'id' => $opportunity->id,
                    'title' => $opportunity->title,
                    'organization' => $opportunity->organization->name,
                    'category' => $opportunity->category->name
                ];
            })
            ->toArray();
    }
    
    /**
     * Get proficiency score for matching
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
     * Get experience bonus score
     */
    private function getExperienceBonus(int $years = null): float
    {
        if (!$years) return 0;
        
        return min($years * 2, 20); // Max 20 points for experience
    }
    
    /**
     * Calculate trend score for a skill
     */
    private function calculateTrendScore(string $skill, int $demandCount): float
    {
        // Get historical data (simplified - could be enhanced with actual historical tracking)
        $baseScore = min($demandCount * 10, 100);
        
        // Add growth factor (placeholder - would need historical data)
        $growthFactor = 1.0;
        
        return $baseScore * $growthFactor;
    }
    
    /**
     * Calculate skill priority for gap analysis
     */
    private function calculateSkillPriority(string $skill, int $demandCount, array $userInterests): float
    {
        $basePriority = $demandCount * 10;
        
        // Boost priority if skill is common in user's interest areas
        $interestBoost = count($userInterests) > 0 ? 20 : 0;
        
        return $basePriority + $interestBoost;
    }
}