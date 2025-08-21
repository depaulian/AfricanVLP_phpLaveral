<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VolunteeringOpportunity;
use Illuminate\Auth\Access\HandlesAuthorization;

class VolunteeringOpportunityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any opportunities.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view opportunities list
        return true;
    }

    /**
     * Determine whether the user can view the opportunity.
     */
    public function view(?User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Anyone can view active opportunities
        if ($opportunity->status === 'active') {
            return true;
        }

        // Only authenticated users can view draft/paused opportunities if they're the creator
        if ($user && $opportunity->created_by === $user->id) {
            return true;
        }

        // Organization members can view their organization's opportunities
        if ($user && $user->organizations()->where('organizations.id', $opportunity->organization_id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create opportunities.
     */
    public function create(User $user): bool
    {
        // Only authenticated users who belong to an organization can create opportunities
        return $user->organizations()->exists();
    }

    /**
     * Determine whether the user can update the opportunity.
     */
    public function update(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Creator can update their opportunity
        if ($opportunity->created_by === $user->id) {
            return true;
        }

        // Organization admins can update opportunities
        $organizationUser = $user->organizations()
            ->where('organizations.id', $opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the opportunity.
     */
    public function delete(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Only creator or organization admin can delete
        if ($opportunity->created_by === $user->id) {
            return true;
        }

        $organizationUser = $user->organizations()
            ->where('organizations.id', $opportunity->organization_id)
            ->first();

        if ($organizationUser && $organizationUser->pivot->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply to the opportunity.
     */
    public function apply(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Cannot apply to own opportunity
        if ($opportunity->created_by === $user->id) {
            return false;
        }

        // Cannot apply to inactive opportunities
        if ($opportunity->status !== 'active') {
            return false;
        }

        // Cannot apply if not accepting applications
        if (!$opportunity->is_accepting_applications) {
            return false;
        }

        // Cannot apply if already applied
        if ($opportunity->applications()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Cannot apply if opportunity is full
        if ($opportunity->spots_remaining <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage applications for the opportunity.
     */
    public function manageApplications(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Creator can manage applications
        if ($opportunity->created_by === $user->id) {
            return true;
        }

        // Organization members with appropriate role can manage applications
        $organizationUser = $user->organizations()
            ->where('organizations.id', $opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view statistics for the opportunity.
     */
    public function viewStatistics(User $user, VolunteeringOpportunity $opportunity): bool
    {
        return $this->manageApplications($user, $opportunity);
    }

    /**
     * Determine whether the user can feature the opportunity.
     */
    public function feature(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Only organization admins can feature opportunities
        $organizationUser = $user->organizations()
            ->where('organizations.id', $opportunity->organization_id)
            ->first();

        return $organizationUser && $organizationUser->pivot->role === 'admin';
    }

    /**
     * Determine whether the user can duplicate the opportunity.
     */
    public function duplicate(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Can duplicate if can view and create
        return $this->view($user, $opportunity) && $this->create($user);
    }
}