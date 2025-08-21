<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserVolunteeringInterest;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InterestsMatchingService
{
    /**
     * Get content recommendations based on user interests
     */
    public function getContentRecommendations(User $user, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;
        $contentTypes = $options['types'] ?? ['opportunities', 'events', 'resources', 'news'];
        
        $userInterests = $user->volunteeringInterests()->with('category')->get();
        
        if ($userInterests->isEmpty()) {
            return $this->getPopularContent($contentTypes, $limit);
        }
        
        $recommendations = [];
        
        foreach ($contentTypes as $type) {
            $recommendations[$type] = $this->getContentByType($type, $userInterests, $limit / count($contentTypes));
        }
        
        return $recommendations;
    }
    
    /**
     * Find opportunities based on user interests
     */
    public function findOpportunitiesByInterests(User $user, array $options = []): Collection
    {
        $limit = $options['limit'] ?? 20;
        $includeRelated = $options['include_related'] ?? true;
        $minInterestLevel = $options['min_interest_level'] ?? 'low';
        
        $userInterests = $user->volunteeringInterests()
            ->where('interest_level', '>=', $minInterestLevel)
            ->with('category')
            ->get();
        
        if ($userInterests->isEmpty()) {
            return collect();
        }
        
        $categoryIds = $userInterests->pluck('category_id')->toArray();
        $relatedCategoryIds = [];
        
        if ($includeRelated) {
            $relatedCategoryIds = $this->getRelatedCategories($categoryIds);
        }
        
        $allCategoryIds = array_unique(array_merge($categoryIds, $relatedCategoryIds));
        
        $opportunities = VolunteeringOpportunity::with([
            'organization',
            'category',
            'city',
            'country'
        ])
        ->active()
        ->acceptingApplications()
        ->whereIn('category_id', $allCategoryIds)
        ->get();
        
        // Score opportunities based on interest levels and category relationships
        $scoredOpportunities = $opportunities->map(function ($opportunity) use ($userInterests, $categoryIds) {
            $score = $this->calculateInterestScore($opportunity, $userInterests, $categoryIds);
            $opportunity->interest_match_score = $score;
            $opportunity->interest_reason = $this->getInterestReason($opportunity, $userInterests, $categoryIds);
            return $opportunity;
        })
        ->sortByDesc('interest_match_score')
        ->take($limit);
        
        return $scoredOpportunities->values();
    }
    
    /**
     * Calculate interest-based score for an opportunity
     */
    private function calculateInterestScore(VolunteeringOpportunity $opportunity, Collection $userInterests, array $primaryCategoryIds): float
    {
        // Direct interest match
        $directInterest = $userInterests->firstWhere('category_id', $opportunity->category_id);
        if ($directInterest) {
            return $this->getInterestLevelScore($directInterest->interest_level);
        }
        
        // Parent category match
        $category = $opportunity->category;
        if ($category && $category->parent_id && in_array($category->parent_id, $primaryCategoryIds)) {
            $parentInterest = $userInterests->firstWhere('category_id', $category->parent_id);
            if ($parentInterest) {
                return $this->getInterestLevelScore($parentInterest->interest_level) * 0.8;
            }
        }
        
        // Child category match
        $childInterests = $userInterests->filter(function ($interest) use ($opportunity) {
            return $interest->category->parent_id === $opportunity->category_id;
        });
        
        if ($childInterests->isNotEmpty()) {
            $avgScore = $childInterests->avg(function ($interest) {
                return $this->getInterestLevelScore($interest->interest_level);
            });
            return $avgScore * 0.7;
        }
        
        // Related category match (lower score)
        return 40;
    }
    
    /**
     * Get reason for interest match
     */
    private function getInterestReason(VolunteeringOpportunity $opportunity, Collection $userInterests, array $primaryCategoryIds): string
    {
        $directInterest = $userInterests->firstWhere('category_id', $opportunity->category_id);
        if ($directInterest) {
            return "Matches your {$directInterest->interest_level} interest in {$opportunity->category->name}";
        }
        
        $category = $opportunity->category;
        if ($category && $category->parent_id && in_array($category->parent_id, $primaryCategoryIds)) {
            $parentInterest = $userInterests->firstWhere('category_id', $category->parent_id);
            if ($parentInterest) {
                return "Related to your interest in {$parentInterest->category->name}";
            }
        }
        
        return "Related to your volunteering interests";
    }
    
    /**
     * Get related categories based on user's interests
     */
    private function getRelatedCategories(array $categoryIds): array
    {
        $related = [];
        
        // Get parent categories
        $parentIds = VolunteeringCategory::whereIn('id', $categoryIds)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique()
            ->toArray();
        
        // Get sibling categories (same parent)
        $siblingIds = VolunteeringCategory::whereIn('parent_id', $parentIds)
            ->whereNotIn('id', $categoryIds)
            ->pluck('id')
            ->toArray();
        
        // Get child categories
        $childIds = VolunteeringCategory::whereIn('parent_id', $categoryIds)
            ->pluck('id')
            ->toArray();
        
        return array_unique(array_merge($parentIds, $siblingIds, $childIds));
    }
    
    /**
     * Find users with similar interests
     */
    public function findUsersWithSimilarInterests(User $user, array $options = []): Collection
    {
        $limit = $options['limit'] ?? 20;
        $minSimilarity = $options['min_similarity'] ?? 0.3;
        
        $userInterests = $user->volunteeringInterests()->pluck('category_id')->toArray();
        
        if (empty($userInterests)) {
            return collect();
        }
        
        $similarUsers = User::where('id', '!=', $user->id)
            ->whereHas('volunteeringInterests', function ($query) use ($userInterests) {
                $query->whereIn('category_id', $userInterests);
            })
            ->with(['volunteeringInterests.category'])
            ->get();
        
        // Calculate similarity scores
        $scoredUsers = $similarUsers->map(function ($otherUser) use ($userInterests) {
            $otherInterests = $otherUser->volunteeringInterests->pluck('category_id')->toArray();
            $similarity = $this->calculateInterestSimilarity($userInterests, $otherInterests);
            $otherUser->interest_similarity = $similarity;
            $otherUser->common_interests = array_intersect($userInterests, $otherInterests);
            return $otherUser;
        })
        ->filter(function ($otherUser) use ($minSimilarity) {
            return $otherUser->interest_similarity >= $minSimilarity;
        })
        ->sortByDesc('interest_similarity')
        ->take($limit);
        
        return $scoredUsers->values();
    }
    
    /**
     * Calculate interest similarity between two users
     */
    private function calculateInterestSimilarity(array $interests1, array $interests2): float
    {
        $intersection = array_intersect($interests1, $interests2);
        $union = array_unique(array_merge($interests1, $interests2));
        
        if (empty($union)) {
            return 0;
        }
        
        // Jaccard similarity coefficient
        return count($intersection) / count($union);
    }
    
    /**
     * Get trending interests based on recent activity
     */
    public function getTrendingInterests(int $limit = 10): array
    {
        return Cache::remember('trending_interests', 1800, function () use ($limit) {
            // Get categories with most recent applications
            $trendingCategories = DB::table('volunteer_applications')
                ->join('volunteering_opportunities', 'volunteer_applications.opportunity_id', '=', 'volunteering_opportunities.id')
                ->join('volunteering_categories', 'volunteering_opportunities.category_id', '=', 'volunteering_categories.id')
                ->where('volunteer_applications.applied_at', '>=', now()->subDays(30))
                ->select('volunteering_categories.id', 'volunteering_categories.name', 
                        DB::raw('COUNT(*) as application_count'))
                ->groupBy('volunteering_categories.id', 'volunteering_categories.name')
                ->orderByDesc('application_count')
                ->limit($limit)
                ->get();
            
            return $trendingCategories->map(function ($category) {
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'activity_count' => $category->application_count,
                    'trend_score' => $this->calculateTrendScore($category->application_count)
                ];
            })->toArray();
        });
    }
    
    /**
     * Analyze interest patterns and suggest new interests
     */
    public function suggestNewInterests(User $user): array
    {
        $userInterests = $user->volunteeringInterests()->pluck('category_id')->toArray();
        
        if (empty($userInterests)) {
            return $this->getPopularInterests();
        }
        
        // Find users with similar interests
        $similarUsers = $this->findUsersWithSimilarInterests($user, ['limit' => 50, 'min_similarity' => 0.4]);
        
        // Collect interests from similar users
        $suggestedInterests = collect();
        foreach ($similarUsers as $similarUser) {
            $otherInterests = $similarUser->volunteeringInterests->pluck('category_id')->toArray();
            $newInterests = array_diff($otherInterests, $userInterests);
            
            foreach ($newInterests as $categoryId) {
                $suggestedInterests->push($categoryId);
            }
        }
        
        // Count and rank suggestions
        $interestCounts = $suggestedInterests->countBy();
        $topSuggestions = $interestCounts->sortDesc()->take(10);
        
        $suggestions = [];
        foreach ($topSuggestions as $categoryId => $count) {
            $category = VolunteeringCategory::find($categoryId);
            if ($category) {
                $suggestions[] = [
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                    'suggestion_strength' => $count,
                    'reason' => "Popular among users with similar interests ({$count} similar users)",
                    'related_opportunities_count' => $this->getOpportunityCountForCategory($categoryId)
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Get content by type based on interests
     */
    private function getContentByType(string $type, Collection $userInterests, int $limit): array
    {
        $categoryIds = $userInterests->pluck('category_id')->toArray();
        
        switch ($type) {
            case 'opportunities':
                return $this->getOpportunitiesForCategories($categoryIds, $limit);
            case 'events':
                return $this->getEventsForCategories($categoryIds, $limit);
            case 'resources':
                return $this->getResourcesForCategories($categoryIds, $limit);
            case 'news':
                return $this->getNewsForCategories($categoryIds, $limit);
            default:
                return [];
        }
    }
    
    /**
     * Get opportunities for specific categories
     */
    private function getOpportunitiesForCategories(array $categoryIds, int $limit): array
    {
        return VolunteeringOpportunity::whereIn('category_id', $categoryIds)
            ->active()
            ->acceptingApplications()
            ->with(['organization', 'category'])
            ->orderByDesc('featured')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($opportunity) {
                return [
                    'id' => $opportunity->id,
                    'title' => $opportunity->title,
                    'organization' => $opportunity->organization->name,
                    'category' => $opportunity->category->name,
                    'location' => $opportunity->formatted_location,
                    'type' => 'opportunity'
                ];
            })
            ->toArray();
    }
    
    /**
     * Get events for specific categories (placeholder - would need events system)
     */
    private function getEventsForCategories(array $categoryIds, int $limit): array
    {
        // Placeholder - would integrate with events system
        return [];
    }
    
    /**
     * Get resources for specific categories (placeholder - would need resources system)
     */
    private function getResourcesForCategories(array $categoryIds, int $limit): array
    {
        // Placeholder - would integrate with resources system
        return [];
    }
    
    /**
     * Get news for specific categories (placeholder - would need news system)
     */
    private function getNewsForCategories(array $categoryIds, int $limit): array
    {
        // Placeholder - would integrate with news/blog system
        return [];
    }
    
    /**
     * Get popular content when user has no interests
     */
    private function getPopularContent(array $contentTypes, int $limit): array
    {
        $recommendations = [];
        
        foreach ($contentTypes as $type) {
            switch ($type) {
                case 'opportunities':
                    $recommendations[$type] = VolunteeringOpportunity::active()
                        ->acceptingApplications()
                        ->featured()
                        ->with(['organization', 'category'])
                        ->limit($limit / count($contentTypes))
                        ->get()
                        ->map(function ($opportunity) {
                            return [
                                'id' => $opportunity->id,
                                'title' => $opportunity->title,
                                'organization' => $opportunity->organization->name,
                                'category' => $opportunity->category->name,
                                'type' => 'opportunity'
                            ];
                        })
                        ->toArray();
                    break;
                default:
                    $recommendations[$type] = [];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get popular interests for new users
     */
    private function getPopularInterests(): array
    {
        return Cache::remember('popular_interests', 3600, function () {
            return DB::table('user_volunteering_interests')
                ->join('volunteering_categories', 'user_volunteering_interests.category_id', '=', 'volunteering_categories.id')
                ->select('volunteering_categories.id', 'volunteering_categories.name', 
                        DB::raw('COUNT(*) as user_count'))
                ->groupBy('volunteering_categories.id', 'volunteering_categories.name')
                ->orderByDesc('user_count')
                ->limit(10)
                ->get()
                ->map(function ($category) {
                    return [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'user_count' => $category->user_count,
                        'reason' => "Popular choice among volunteers ({$category->user_count} users)"
                    ];
                })
                ->toArray();
        });
    }
    
    /**
     * Get opportunity count for a category
     */
    private function getOpportunityCountForCategory(int $categoryId): int
    {
        return VolunteeringOpportunity::where('category_id', $categoryId)
            ->active()
            ->acceptingApplications()
            ->count();
    }
    
    /**
     * Get interest level score
     */
    private function getInterestLevelScore(string $level): float
    {
        return match ($level) {
            'high' => 100,
            'medium' => 75,
            'low' => 50,
            default => 60
        };
    }
    
    /**
     * Calculate trend score
     */
    private function calculateTrendScore(int $activityCount): float
    {
        return min($activityCount * 5, 100);
    }
}