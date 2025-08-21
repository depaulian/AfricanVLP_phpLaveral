<?php

namespace App\Services;

use App\Models\VolunteeringOpportunity;
use App\Models\VolunteerApplication;
use App\Models\VolunteerAssignment;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class VolunteeringService
{
    /**
     * Create a new volunteering opportunity
     */
    public function createOpportunity(array $data, User $creator): VolunteeringOpportunity
    {
        return DB::transaction(function () use ($data, $creator) {
            $opportunity = VolunteeringOpportunity::create(array_merge($data, [
                'created_by' => $creator->id,
                'slug' => $this->generateUniqueSlug($data['title'])
            ]));

            // Clear relevant caches
            $this->clearOpportunityCaches();

            return $opportunity->load(['organization', 'category', 'role', 'city', 'country']);
        });
    }

    /**
     * Update a volunteering opportunity
     */
    public function updateOpportunity(VolunteeringOpportunity $opportunity, array $data): VolunteeringOpportunity
    {
        return DB::transaction(function () use ($opportunity, $data) {
            if (isset($data['title']) && $data['title'] !== $opportunity->title) {
                $data['slug'] = $this->generateUniqueSlug($data['title'], $opportunity->id);
            }

            $opportunity->update($data);

            // Clear relevant caches
            $this->clearOpportunityCaches();

            return $opportunity->fresh(['organization', 'category', 'role', 'city', 'country']);
        });
    }

    /**
     * Get opportunities with advanced filtering
     */
    public function getOpportunities(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = VolunteeringOpportunity::with([
            'organization',
            'category',
            'role',
            'city',
            'country',
            'creator'
        ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (!empty($filters['location_type'])) {
            $query->byLocationType($filters['location_type']);
        }

        if (!empty($filters['experience_level'])) {
            $query->byExperienceLevel($filters['experience_level']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->active();
        }

        if (!empty($filters['featured'])) {
            $query->featured();
        }

        if (!empty($filters['accepting_applications'])) {
            $query->acceptingApplications();
        }

        if (!empty($filters['skills'])) {
            $skills = is_array($filters['skills']) ? $filters['skills'] : [$filters['skills']];
            $query->where(function ($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhereJsonContains('required_skills', $skill);
                }
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'deadline':
                $query->orderByRaw('application_deadline IS NULL, application_deadline ASC');
                break;
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'organization':
                $query->join('organizations', 'volunteering_opportunities.organization_id', '=', 'organizations.id')
                      ->orderBy('organizations.name', $sortOrder)
                      ->select('volunteering_opportunities.*');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get featured opportunities
     */
    public function getFeaturedOpportunities(int $limit = 6): Collection
    {
        return Cache::remember('featured_opportunities', 3600, function () use ($limit) {
            return VolunteeringOpportunity::with([
                'organization',
                'category',
                'city',
                'country'
            ])
            ->featured()
            ->active()
            ->acceptingApplications()
            ->limit($limit)
            ->get();
        });
    }

    /**
     * Get opportunities by organization
     */
    public function getOpportunitiesByOrganization(Organization $organization, array $filters = []): Collection
    {
        $query = $organization->volunteeringOpportunities()
            ->with(['category', 'role', 'city', 'country']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Submit application for opportunity
     */
    public function submitApplication(VolunteeringOpportunity $opportunity, User $user, array $data): VolunteerApplication
    {
        // Check if user already applied
        $existingApplication = VolunteerApplication::where('opportunity_id', $opportunity->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingApplication) {
            throw new \Exception('You have already applied for this opportunity.');
        }

        // Check if opportunity is accepting applications
        if (!$opportunity->is_accepting_applications) {
            throw new \Exception('This opportunity is no longer accepting applications.');
        }

        return DB::transaction(function () use ($opportunity, $user, $data) {
            $application = VolunteerApplication::create(array_merge($data, [
                'opportunity_id' => $opportunity->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'applied_at' => now()
            ]));

            // Send notification to organization
            // This would typically trigger an event or notification

            return $application->load(['opportunity', 'user']);
        });
    }

    /**
     * Review application
     */
    public function reviewApplication(
        VolunteerApplication $application,
        User $reviewer,
        string $decision,
        array $data = []
    ): VolunteerApplication {
        return DB::transaction(function () use ($application, $reviewer, $decision, $data) {
            switch ($decision) {
                case 'accept':
                    $assignment = $application->accept($reviewer, $data['assignment'] ?? []);
                    break;
                case 'reject':
                    $application->reject($reviewer, $data['reason'] ?? null);
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid decision: ' . $decision);
            }

            // Send notification to applicant
            // This would typically trigger an event or notification

            return $application->fresh(['opportunity', 'user', 'reviewer', 'assignment']);
        });
    }

    /**
     * Get applications for opportunity
     */
    public function getApplicationsForOpportunity(
        VolunteeringOpportunity $opportunity,
        array $filters = []
    ): Collection {
        $query = $opportunity->applications()->with(['user', 'reviewer']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('applied_at', 'desc')->get();
    }

    /**
     * Get user's applications
     */
    public function getUserApplications(User $user, array $filters = []): Collection
    {
        $query = $user->volunteerApplications()->with([
            'opportunity.organization',
            'opportunity.category',
            'reviewer',
            'assignment'
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('applied_at', 'desc')->get();
    }

    /**
     * Get user's assignments
     */
    public function getUserAssignments(User $user, array $filters = []): Collection
    {
        $query = VolunteerAssignment::byVolunteer($user->id)
            ->with([
                'application.opportunity.organization',
                'application.opportunity.category',
                'supervisor',
                'timeLogs'
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    /**
     * Complete assignment
     */
    public function completeAssignment(
        VolunteerAssignment $assignment,
        array $data = []
    ): VolunteerAssignment {
        return DB::transaction(function () use ($assignment, $data) {
            $assignment->complete($data);

            // Update opportunity volunteer count if needed
            if (isset($data['decrease_count']) && $data['decrease_count']) {
                $assignment->application->opportunity->decrementVolunteers();
            }

            // Generate certificate if requested
            if (isset($data['issue_certificate']) && $data['issue_certificate']) {
                $assignment->update(['certificate_issued' => true]);
                // This would typically trigger certificate generation
            }

            return $assignment->fresh();
        });
    }

    /**
     * Get opportunity statistics
     */
    public function getOpportunityStatistics(VolunteeringOpportunity $opportunity): array
    {
        return [
            'total_applications' => $opportunity->applications()->count(),
            'pending_applications' => $opportunity->pendingApplications()->count(),
            'accepted_applications' => $opportunity->acceptedApplications()->count(),
            'active_assignments' => $opportunity->assignments()->where('status', 'active')->count(),
            'completed_assignments' => $opportunity->assignments()->where('status', 'completed')->count(),
            'total_hours_logged' => $opportunity->assignments()
                ->join('volunteer_time_logs', 'volunteer_assignments.id', '=', 'volunteer_time_logs.assignment_id')
                ->where('volunteer_time_logs.supervisor_approved', true)
                ->sum('volunteer_time_logs.hours'),
            'spots_remaining' => $opportunity->spots_remaining,
            'days_until_deadline' => $opportunity->days_until_deadline
        ];
    }

    /**
     * Get organization statistics
     */
    public function getOrganizationStatistics(Organization $organization): array
    {
        return [
            'total_opportunities' => $organization->volunteeringOpportunities()->count(),
            'active_opportunities' => $organization->volunteeringOpportunities()->active()->count(),
            'total_applications' => VolunteerApplication::whereHas('opportunity', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })->count(),
            'active_volunteers' => VolunteerAssignment::whereHas('application.opportunity', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })->where('status', 'active')->count(),
            'total_volunteer_hours' => VolunteerAssignment::whereHas('application.opportunity', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })->sum('hours_completed')
        ];
    }

    /**
     * Generate unique slug for opportunity
     */
    private function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = \Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = VolunteeringOpportunity::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Clear opportunity-related caches
     */
    private function clearOpportunityCaches(): void
    {
        Cache::forget('featured_opportunities');
        // Clear other relevant caches
    }

    /**
     * Search opportunities with advanced matching
     */
    public function searchOpportunities(string $query, array $filters = [], int $limit = 20): Collection
    {
        $opportunities = VolunteeringOpportunity::with([
            'organization',
            'category',
            'city',
            'country'
        ])
        ->where(function ($q) use ($query) {
            $q->where('title', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%")
              ->orWhereHas('organization', function ($orgQuery) use ($query) {
                  $orgQuery->where('name', 'LIKE', "%{$query}%");
              })
              ->orWhereHas('category', function ($catQuery) use ($query) {
                  $catQuery->where('name', 'LIKE', "%{$query}%");
              });
        })
        ->active()
        ->acceptingApplications();

        // Apply additional filters
        if (!empty($filters['category_id'])) {
            $opportunities->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['location_type'])) {
            $opportunities->where('location_type', $filters['location_type']);
        }

        return $opportunities->limit($limit)->get();
    }
}