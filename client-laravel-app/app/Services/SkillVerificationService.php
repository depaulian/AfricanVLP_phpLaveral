<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillEndorsement;
use App\Models\SkillVerificationRequest;
use App\Notifications\SkillEndorsementRequest;
use App\Notifications\SkillEndorsementReceived;
use App\Notifications\SkillVerificationApproved;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SkillVerificationService
{
    /**
     * Request skill endorsement from another user
     */
    public function requestEndorsement(UserSkill $skill, User $endorser, string $message = null): SkillEndorsement
    {
        // Check if endorsement already exists
        $existingEndorsement = SkillEndorsement::where('skill_id', $skill->id)
            ->where('endorser_id', $endorser->id)
            ->first();
        
        if ($existingEndorsement) {
            throw new \Exception('Endorsement already exists from this user');
        }
        
        // Create endorsement request
        $endorsement = SkillEndorsement::create([
            'skill_id' => $skill->id,
            'endorser_id' => $endorser->id,
            'status' => 'pending',
            'request_message' => $message,
            'requested_at' => now()
        ]);
        
        // Send notification to endorser
        $endorser->notify(new SkillEndorsementRequest($skill, $skill->user, $message));
        
        return $endorsement;
    }
    
    /**
     * Approve skill endorsement
     */
    public function approveEndorsement(SkillEndorsement $endorsement, string $comment = null): SkillEndorsement
    {
        $endorsement->update([
            'status' => 'approved',
            'endorsement_comment' => $comment,
            'endorsed_at' => now()
        ]);
        
        // Update skill verification status if enough endorsements
        $this->updateSkillVerificationStatus($endorsement->skill);
        
        // Notify skill owner
        $endorsement->skill->user->notify(new SkillEndorsementReceived($endorsement));
        
        return $endorsement;
    }
    
    /**
     * Reject skill endorsement
     */
    public function rejectEndorsement(SkillEndorsement $endorsement, string $reason = null): SkillEndorsement
    {
        $endorsement->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'responded_at' => now()
        ]);
        
        return $endorsement;
    }
    
    /**
     * Submit skill for official verification
     */
    public function submitForVerification(UserSkill $skill, array $evidence = []): SkillVerificationRequest
    {
        // Check if verification request already exists
        $existingRequest = SkillVerificationRequest::where('skill_id', $skill->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->first();
        
        if ($existingRequest) {
            throw new \Exception('Verification request already pending for this skill');
        }
        
        return SkillVerificationRequest::create([
            'skill_id' => $skill->id,
            'evidence' => $evidence,
            'status' => 'pending',
            'submitted_at' => now()
        ]);
    }
    
    /**
     * Process official verification request
     */
    public function processVerificationRequest(SkillVerificationRequest $request, User $reviewer, bool $approved, string $notes = null): SkillVerificationRequest
    {
        $request->update([
            'status' => $approved ? 'approved' : 'rejected',
            'reviewer_id' => $reviewer->id,
            'reviewer_notes' => $notes,
            'reviewed_at' => now()
        ]);
        
        if ($approved) {
            // Mark skill as officially verified
            $request->skill->update([
                'verified' => true,
                'verified_by' => $reviewer->id,
                'verified_at' => now(),
                'verification_type' => 'official'
            ]);
            
            // Notify user
            $request->skill->user->notify(new SkillVerificationApproved($request->skill));
        }
        
        return $request;
    }
    
    /**
     * Get endorsement statistics for a skill
     */
    public function getEndorsementStats(UserSkill $skill): array
    {
        $endorsements = $skill->endorsements()->approved()->get();
        
        return [
            'total_endorsements' => $endorsements->count(),
            'endorser_types' => $this->analyzeEndorserTypes($endorsements),
            'endorsement_strength' => $this->calculateEndorsementStrength($endorsements),
            'recent_endorsements' => $endorsements->where('endorsed_at', '>=', now()->subDays(30))->count(),
            'verification_score' => $this->calculateVerificationScore($skill, $endorsements)
        ];
    }
    
    /**
     * Get users who can endorse a skill
     */
    public function getPotentialEndorsers(UserSkill $skill, int $limit = 20): Collection
    {
        $user = $skill->user;
        
        // Find users who:
        // 1. Have the same skill with higher or equal proficiency
        // 2. Have worked with the user (same organization/opportunities)
        // 3. Are in the user's network
        
        $potentialEndorsers = User::where('id', '!=', $user->id)
            ->where(function ($query) use ($skill, $user) {
                // Users with same skill and higher/equal proficiency
                $query->whereHas('skills', function ($skillQuery) use ($skill) {
                    $skillQuery->where('skill_name', $skill->skill_name)
                            ->where(function ($profQuery) use ($skill) {
                                $proficiencyOrder = ['beginner', 'intermediate', 'advanced', 'expert'];
                                $userLevelIndex = array_search($skill->proficiency_level, $proficiencyOrder);
                                $higherLevels = array_slice($proficiencyOrder, $userLevelIndex);
                                $profQuery->whereIn('proficiency_level', $higherLevels);
                            });
                })
                // Users who worked in same organizations
                ->orWhereHas('volunteerApplications.opportunity.organization', function ($orgQuery) use ($user) {
                    $orgQuery->whereHas('opportunities.applications', function ($appQuery) use ($user) {
                        $appQuery->where('user_id', $user->id);
                    });
                });
            })
            ->with(['skills' => function ($query) use ($skill) {
                $query->where('skill_name', $skill->skill_name);
            }])
            ->limit($limit * 2) // Get more to score and filter
            ->get();
        
        // Score potential endorsers
        $scoredEndorsers = $potentialEndorsers->map(function ($endorser) use ($skill, $user) {
            $score = $this->calculateEndorserScore($endorser, $skill, $user);
            $endorser->endorser_score = $score;
            return $endorser;
        })
        ->sortByDesc('endorser_score')
        ->take($limit);
        
        return $scoredEndorsers->values();
    }
    
    /**
     * Calculate endorser score
     */
    private function calculateEndorserScore(User $endorser, UserSkill $skill, User $skillOwner): float
    {
        $score = 0;
        
        // Skill proficiency match
        $endorserSkill = $endorser->skills->firstWhere('skill_name', $skill->skill_name);
        if ($endorserSkill) {
            $proficiencyLevels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
            $endorserLevel = $proficiencyLevels[$endorserSkill->proficiency_level] ?? 1;
            $skillLevel = $proficiencyLevels[$skill->proficiency_level] ?? 1;
            
            if ($endorserLevel >= $skillLevel) {
                $score += 40;
                if ($endorserSkill->verified) {
                    $score += 20;
                }
            }
        }
        
        // Work relationship
        $sharedOpportunities = $this->getSharedOpportunities($endorser, $skillOwner);
        $score += min($sharedOpportunities * 10, 30);
        
        // Network connection strength
        $connectionStrength = $this->calculateConnectionStrength($endorser, $skillOwner);
        $score += $connectionStrength * 10;
        
        return min($score, 100);
    }
    
    /**
     * Get shared opportunities between users
     */
    private function getSharedOpportunities(User $user1, User $user2): int
    {
        $user1Opportunities = $user1->volunteerApplications()
            ->where('status', 'accepted')
            ->pluck('opportunity_id');
        
        $user2Opportunities = $user2->volunteerApplications()
            ->where('status', 'accepted')
            ->pluck('opportunity_id');
        
        return $user1Opportunities->intersect($user2Opportunities)->count();
    }
    
    /**
     * Calculate connection strength between users
     */
    private function calculateConnectionStrength(User $user1, User $user2): float
    {
        // This would integrate with a connections/networking system
        // For now, return a placeholder based on shared interests
        
        $user1Interests = $user1->volunteeringInterests()->pluck('category_id');
        $user2Interests = $user2->volunteeringInterests()->pluck('category_id');
        
        $sharedInterests = $user1Interests->intersect($user2Interests)->count();
        $totalInterests = $user1Interests->merge($user2Interests)->unique()->count();
        
        return $totalInterests > 0 ? $sharedInterests / $totalInterests : 0;
    }
    
    /**
     * Update skill verification status based on endorsements
     */
    private function updateSkillVerificationStatus(UserSkill $skill): void
    {
        $endorsements = $skill->endorsements()->approved()->get();
        $endorsementCount = $endorsements->count();
        
        // Auto-verify if enough quality endorsements
        $verificationThreshold = $this->getVerificationThreshold($skill->proficiency_level);
        
        if ($endorsementCount >= $verificationThreshold && !$skill->verified) {
            $endorsementStrength = $this->calculateEndorsementStrength($endorsements);
            
            if ($endorsementStrength >= 70) { // Minimum strength threshold
                $skill->update([
                    'verified' => true,
                    'verified_at' => now(),
                    'verification_type' => 'peer_endorsed'
                ]);
            }
        }
    }
    
    /**
     * Get verification threshold based on proficiency level
     */
    private function getVerificationThreshold(string $proficiencyLevel): int
    {
        return match ($proficiencyLevel) {
            'expert' => 5,
            'advanced' => 4,
            'intermediate' => 3,
            'beginner' => 2,
            default => 3
        };
    }
    
    /**
     * Analyze endorser types
     */
    private function analyzeEndorserTypes(Collection $endorsements): array
    {
        $types = [
            'peers' => 0,
            'seniors' => 0,
            'verified_users' => 0,
            'organization_members' => 0
        ];
        
        foreach ($endorsements as $endorsement) {
            $endorser = $endorsement->endorser;
            $endorserSkill = $endorser->skills()->where('skill_name', $endorsement->skill->skill_name)->first();
            
            if ($endorserSkill) {
                $proficiencyLevels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
                $endorserLevel = $proficiencyLevels[$endorserSkill->proficiency_level] ?? 1;
                $skillLevel = $proficiencyLevels[$endorsement->skill->proficiency_level] ?? 1;
                
                if ($endorserLevel > $skillLevel) {
                    $types['seniors']++;
                } else {
                    $types['peers']++;
                }
                
                if ($endorserSkill->verified) {
                    $types['verified_users']++;
                }
            }
        }
        
        return $types;
    }
    
    /**
     * Calculate endorsement strength
     */
    private function calculateEndorsementStrength(Collection $endorsements): float
    {
        if ($endorsements->isEmpty()) {
            return 0;
        }
        
        $totalStrength = 0;
        
        foreach ($endorsements as $endorsement) {
            $strength = 20; // Base strength
            
            $endorser = $endorsement->endorser;
            $endorserSkill = $endorser->skills()->where('skill_name', $endorsement->skill->skill_name)->first();
            
            if ($endorserSkill) {
                // Proficiency bonus
                $proficiencyBonus = match ($endorserSkill->proficiency_level) {
                    'expert' => 30,
                    'advanced' => 25,
                    'intermediate' => 20,
                    'beginner' => 15,
                    default => 15
                };
                $strength += $proficiencyBonus;
                
                // Verification bonus
                if ($endorserSkill->verified) {
                    $strength += 20;
                }
            }
            
            // Comment quality bonus
            if (!empty($endorsement->endorsement_comment) && strlen($endorsement->endorsement_comment) > 50) {
                $strength += 10;
            }
            
            $totalStrength += min($strength, 100);
        }
        
        return $totalStrength / $endorsements->count();
    }
    
    /**
     * Calculate verification score
     */
    private function calculateVerificationScore(UserSkill $skill, Collection $endorsements): float
    {
        $score = 0;
        
        // Base score from proficiency and experience
        $score += match ($skill->proficiency_level) {
            'expert' => 40,
            'advanced' => 30,
            'intermediate' => 20,
            'beginner' => 10,
            default => 10
        };
        
        if ($skill->years_experience) {
            $score += min($skill->years_experience * 2, 20);
        }
        
        // Endorsement score
        $endorsementStrength = $this->calculateEndorsementStrength($endorsements);
        $score += $endorsementStrength * 0.4;
        
        // Official verification bonus
        if ($skill->verified && $skill->verification_type === 'official') {
            $score += 20;
        }
        
        return min($score, 100);
    }
}