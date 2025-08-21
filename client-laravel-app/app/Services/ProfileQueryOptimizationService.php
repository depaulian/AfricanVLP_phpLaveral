<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\UserAlumniOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileQueryOptimizationService
{
    /**
     * Get optimized user profile with eager loading
     */
    public function getOptimizedProfile(int $userId): ?User
    {
        return User::with([
            'profile' => function ($query) {
                $query->select([
                    'id', 'user_id', 'bio', 'phone_number', 'address',
                    'city_id', 'country_id', 'date_of_birth', 'linkedin_url',
                    'twitter_url', 'website_url', 'is_public', 'profile_image_url',
                    'profile_completion_percentage', 'settings', 'created_at', 'updated_at'
                ]);
            },
            'profile.city:id,name,country_id',
            'profile.country:id,name',
            'skills' => function ($query) {
                $query->select([
                    'id', 'user_id', 'skill_name', 'proficiency_level',
                    'years_experience', 'verified', 'verified_by', 'verified_at'
                ])
                ->orderBy('verified', 'desc')
                ->orderBy('proficiency_level', 'desc')
                ->limit(10);
            },
            'skills.verifier:id,name',
            'volunteeringInterests' => function ($query) {
                $query->select(['id', 'user_id', 'category_id', 'interest_level'])
                      ->with('category:id,name,description');
            },
            'volunteeringHistory' => function ($query) {
                $query->select([
                    'id', 'user_id', 'organization_id', 'organization_name',
                    'role_title', 'start_date', 'end_date', 'is_current',
                    'hours_contributed', 'description', 'skills_gained'
                ])
                ->orderBy('start_date', 'desc')
                ->limit(5);
            },
            'documents' => function ($query) {
                $query->select([
                    'id', 'user_id', 'document_type', 'file_name',
                    'verification_status', 'verified_at', 'created_at'
                ])
                ->where('verification_status', 'verified')
                ->orderBy('created_at', 'desc')
                ->limit(5);
            },
            'alumniOrganizations' => function ($query) {
                $query->select([
                    'id', 'user_id', 'organization_id', 'organization_name',
                    'degree', 'field_of_study', 'graduation_year', 'status', 'is_verified'
                ])
                ->where('is_verified', true)
                ->limit(3);
            }
        ])
        ->select(['id', 'name', 'email', 'created_at', 'updated_at'])
        ->find($userId);
    }

    /**
     * Get multiple profiles with optimized queries
     */
    public function getMultipleOptimizedProfiles(array $userIds): Collection
    {
        return User::with([
            'profile:id,user_id,bio,profile_image_url,is_public,profile_completion_percentage',
            'profile.city:id,name',
            'profile.country:id,name',
            'skills' => function ($query) {
                $query->select(['id', 'user_id', 'skill_name', 'proficiency_level', 'verified'])
                      ->where('verified', true)
                      ->limit(5);
            }
        ])
        ->select(['id', 'name', 'email'])
        ->whereIn('id', $userIds)
        ->get();
    }

    /**
     * Search profiles with optimized query
     */
    public function searchProfilesOptimized(
        string $query = '',
        array $filters = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        $builder = User::query()
            ->select(['users.id', 'users.name', 'users.email'])
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('user_profiles.is_public', true);

        // Add search conditions
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('users.name', 'LIKE', "%{$query}%")
                  ->orWhere('user_profiles.bio', 'LIKE', "%{$query}%");
            });
        }

        // Add filters
        $this->applySearchFilters($builder, $filters);

        // Get total count for pagination
        $total = $builder->count();

        // Get results with eager loading
        $results = $builder
            ->with([
                'profile:id,user_id,bio,profile_image_url,profile_completion_percentage',
                'profile.city:id,name',
                'profile.country:id,name'
            ])
            ->orderBy('user_profiles.profile_completion_percentage', 'desc')
            ->orderBy('users.name')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return [
            'data' => $results,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Apply search filters to query builder
     */
    protected function applySearchFilters(Builder $builder, array $filters): void
    {
        if (isset($filters['city_id'])) {
            $builder->where('user_profiles.city_id', $filters['city_id']);
        }

        if (isset($filters['country_id'])) {
            $builder->where('user_profiles.country_id', $filters['country_id']);
        }

        if (isset($filters['skills'])) {
            $builder->whereHas('skills', function ($query) use ($filters) {
                $query->whereIn('skill_name', $filters['skills']);
            });
        }

        if (isset($filters['interests'])) {
            $builder->whereHas('volunteeringInterests', function ($query) use ($filters) {
                $query->whereIn('category_id', $filters['interests']);
            });
        }

        if (isset($filters['min_completion'])) {
            $builder->where('user_profiles.profile_completion_percentage', '>=', $filters['min_completion']);
        }

        if (isset($filters['verified_skills_only']) && $filters['verified_skills_only']) {
            $builder->whereHas('skills', function ($query) {
                $query->where('verified', true);
            });
        }
    }

    /**
     * Get profile statistics with optimized queries
     */
    public function getOptimizedProfileStats(int $userId): array
    {
        // Use raw queries for better performance
        $stats = DB::select("
            SELECT 
                (SELECT profile_completion_percentage FROM user_profiles WHERE user_id = ?) as profile_completion,
                (SELECT COUNT(*) FROM user_skills WHERE user_id = ?) as skills_count,
                (SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND verified = 1) as verified_skills_count,
                (SELECT COUNT(*) FROM user_volunteering_interests WHERE user_id = ?) as interests_count,
                (SELECT COUNT(*) FROM user_volunteering_history WHERE user_id = ?) as volunteering_history_count,
                (SELECT COALESCE(SUM(hours_contributed), 0) FROM user_volunteering_history WHERE user_id = ?) as total_volunteering_hours,
                (SELECT COUNT(*) FROM user_documents WHERE user_id = ?) as documents_count,
                (SELECT COUNT(*) FROM user_documents WHERE user_id = ? AND verification_status = 'verified') as verified_documents_count,
                (SELECT COUNT(*) FROM user_alumni_organizations WHERE user_id = ?) as alumni_organizations_count,
                (SELECT COUNT(*) FROM user_alumni_organizations WHERE user_id = ? AND is_verified = 1) as verified_alumni_count
        ", array_fill(0, 10, $userId));

        return $stats ? (array) $stats[0] : [];
    }

    /**
     * Get users with high profile completion efficiently
     */
    public function getHighCompletionProfiles(int $minCompletion = 80, int $limit = 50): Collection
    {
        return User::select(['users.id', 'users.name', 'users.email'])
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('user_profiles.profile_completion_percentage', '>=', $minCompletion)
            ->where('user_profiles.is_public', true)
            ->with([
                'profile:id,user_id,bio,profile_image_url,profile_completion_percentage',
                'profile.city:id,name',
                'profile.country:id,name'
            ])
            ->orderBy('user_profiles.profile_completion_percentage', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recently active users with optimized query
     */
    public function getRecentlyActiveProfiles(int $days = 7, int $limit = 100): Collection
    {
        return User::select(['users.id', 'users.name', 'users.email', 'users.last_login_at'])
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('users.last_login_at', '>=', now()->subDays($days))
            ->where('user_profiles.is_public', true)
            ->with([
                'profile:id,user_id,bio,profile_image_url,profile_completion_percentage'
            ])
            ->orderBy('users.last_login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get skill-based recommendations efficiently
     */
    public function getSkillBasedRecommendations(int $userId, int $limit = 10): Collection
    {
        // Get user's skills first
        $userSkills = UserSkill::where('user_id', $userId)
            ->pluck('skill_name')
            ->toArray();

        if (empty($userSkills)) {
            return collect();
        }

        // Find users with similar skills
        return User::select(['users.id', 'users.name', 'users.email'])
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('user_skills', 'users.id', '=', 'user_skills.user_id')
            ->where('users.id', '!=', $userId)
            ->where('user_profiles.is_public', true)
            ->whereIn('user_skills.skill_name', $userSkills)
            ->with([
                'profile:id,user_id,bio,profile_image_url,profile_completion_percentage',
                'skills' => function ($query) use ($userSkills) {
                    $query->select(['id', 'user_id', 'skill_name', 'proficiency_level'])
                          ->whereIn('skill_name', $userSkills)
                          ->limit(5);
                }
            ])
            ->groupBy('users.id')
            ->orderByRaw('COUNT(user_skills.id) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get aggregated profile statistics
     */
    public function getAggregatedStats(): array
    {
        $stats = DB::select("
            SELECT 
                COUNT(DISTINCT up.user_id) as total_profiles,
                AVG(up.profile_completion_percentage) as avg_completion,
                COUNT(DISTINCT CASE WHEN up.profile_completion_percentage >= 80 THEN up.user_id END) as high_completion_profiles,
                COUNT(DISTINCT us.user_id) as users_with_skills,
                COUNT(DISTINCT uvi.user_id) as users_with_interests,
                COUNT(DISTINCT uvh.user_id) as users_with_history,
                COUNT(DISTINCT ud.user_id) as users_with_documents
            FROM user_profiles up
            LEFT JOIN user_skills us ON up.user_id = us.user_id
            LEFT JOIN user_volunteering_interests uvi ON up.user_id = uvi.user_id
            LEFT JOIN user_volunteering_history uvh ON up.user_id = uvh.user_id
            LEFT JOIN user_documents ud ON up.user_id = ud.user_id
        ");

        return $stats ? (array) $stats[0] : [];
    }

    /**
     * Optimize database indexes for profile queries
     */
    public function suggestIndexOptimizations(): array
    {
        return [
            'user_profiles' => [
                'user_id',
                'is_public',
                'profile_completion_percentage',
                'city_id',
                'country_id',
                ['user_id', 'is_public'],
                ['is_public', 'profile_completion_percentage']
            ],
            'user_skills' => [
                'user_id',
                'skill_name',
                'verified',
                ['user_id', 'verified'],
                ['skill_name', 'verified']
            ],
            'user_volunteering_interests' => [
                'user_id',
                'category_id',
                ['user_id', 'category_id']
            ],
            'user_volunteering_history' => [
                'user_id',
                'start_date',
                ['user_id', 'start_date']
            ],
            'user_documents' => [
                'user_id',
                'verification_status',
                ['user_id', 'verification_status']
            ],
            'users' => [
                'last_login_at',
                'created_at'
            ]
        ];
    }

    /**
     * Get query performance metrics
     */
    public function getQueryPerformanceMetrics(): array
    {
        // Enable query logging
        DB::enableQueryLog();
        
        // Run sample queries
        $this->getOptimizedProfile(1);
        $this->searchProfilesOptimized('test', [], 10, 0);
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        return [
            'total_queries' => count($queries),
            'queries' => $queries,
            'avg_time' => count($queries) > 0 ? array_sum(array_column($queries, 'time')) / count($queries) : 0,
            'slowest_query' => count($queries) > 0 ? max(array_column($queries, 'time')) : 0
        ];
    }
}