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
    public function viewAny(User $user): bool
    {
        // Admin users can view all opportunities
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the opportunity.
     */
    public function view(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Admin users can view all opportunities
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization members can view their organization's opportunities
        if ($user->organizations()->where('organizations.id', $opportunity->organization_id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create opportunities.
     */
    public function create(User $user): bool
    {
        // Admin users and organization members can create opportunities
        return $user->hasRole(['admin', 'super_admin']) || $user->organizations()->exists();
    }

    /**
     * Determine whether the user can update the opportunity.
     */
    public function update(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Super admin can update any opportunity
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can update opportunities
        if ($user->hasRole('admin')) {
            return true;
        }

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
        // Super admin can delete any opportunity
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can delete opportunities with restrictions
        if ($user->hasRole('admin') && $opportunity->applications()->count() === 0) {
            return true;
        }

        // Creator can delete their opportunity if no applications
        if ($opportunity->created_by === $user->id && $opportunity->applications()->count() === 0) {
            return true;
        }

        // Organization admin can delete opportunities
        $organizationUser = $user->organizations()
            ->where('organizations.id', $opportunity->organization_id)
            ->first();

        if ($organizationUser && $organizationUser->pivot->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage applications for the opportunity.
     */
    public function manageApplications(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Admin users can manage all applications
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

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
        return $this->view($user, $opportunity);
    }

    /**
     * Determine whether the user can feature the opportunity.
     */
    public function feature(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Admin users can feature any opportunity
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization admins can feature their opportunities
        $organizationUser = $user->organizations()
            ->where('organizations.id', $opportunity->organization_id)
            ->first();

        return $organizationUser && $organizationUser->pivot->role === 'admin';
    }

    /**
     * Determine whether the user can approve the opportunity.
     */
    public function approve(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Only admin users can approve opportunities
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can suspend the opportunity.
     */
    public function suspend(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Only admin users can suspend opportunities
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can export opportunity data.
     */
    public function export(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Admin users can export data
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization admins can export their data
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
        return $this->view($user, $opportunity) && $this->create($user);
    }

    /**
     * Determine whether the user can force delete the opportunity.
     */
    public function forceDelete(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Only super admin can force delete
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can restore the opportunity.
     */
    public function restore(User $user, VolunteeringOpportunity $opportunity): bool
    {
        // Only super admin can restore
        return $user->hasRole('super_admin');
    }
}