<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Auth\Access\HandlesAuthorization;

class VolunteerApplicationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any applications.
     */
    public function viewAny(User $user): bool
    {
        // Users can view their own applications
        return true;
    }

    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, VolunteerApplication $application): bool
    {
        // Applicant can view their own application
        if ($application->user_id === $user->id) {
            return true;
        }

        // Opportunity creator can view applications
        if ($application->opportunity->created_by === $user->id) {
            return true;
        }

        // Organization members with appropriate role can view applications
        $organizationUser = $user->organizations()
            ->where('organizations.id', $application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create applications.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create applications
        return true;
    }

    /**
     * Determine whether the user can update the application.
     */
    public function update(User $user, VolunteerApplication $application): bool
    {
        // Only applicant can update their pending application
        if ($application->user_id === $user->id && $application->status === 'pending') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the application.
     */
    public function delete(User $user, VolunteerApplication $application): bool
    {
        // Applicant can withdraw their pending application
        if ($application->user_id === $user->id && $application->status === 'pending') {
            return true;
        }

        // Organization admin can delete applications
        $organizationUser = $user->organizations()
            ->where('organizations.id', $application->opportunity->organization_id)
            ->first();

        if ($organizationUser && $organizationUser->pivot->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can review the application.
     */
    public function review(User $user, VolunteerApplication $application): bool
    {
        // Cannot review non-pending applications
        if ($application->status !== 'pending') {
            return false;
        }

        // Opportunity creator can review applications
        if ($application->opportunity->created_by === $user->id) {
            return true;
        }

        // Organization members with appropriate role can review applications
        $organizationUser = $user->organizations()
            ->where('organizations.id', $application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can accept the application.
     */
    public function accept(User $user, VolunteerApplication $application): bool
    {
        return $this->review($user, $application);
    }

    /**
     * Determine whether the user can reject the application.
     */
    public function reject(User $user, VolunteerApplication $application): bool
    {
        return $this->review($user, $application);
    }

    /**
     * Determine whether the user can view application statistics.
     */
    public function viewStatistics(User $user, VolunteerApplication $application): bool
    {
        // Opportunity creator can view statistics
        if ($application->opportunity->created_by === $user->id) {
            return true;
        }

        // Organization members with appropriate role can view statistics
        $organizationUser = $user->organizations()
            ->where('organizations.id', $application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can communicate with the applicant.
     */
    public function communicate(User $user, VolunteerApplication $application): bool
    {
        // Applicant can communicate about their application
        if ($application->user_id === $user->id) {
            return true;
        }

        // Organization members can communicate with applicants
        return $this->view($user, $application);
    }

    /**
     * Determine whether the user can export application data.
     */
    public function export(User $user, VolunteerApplication $application): bool
    {
        // Only organization admins can export application data
        $organizationUser = $user->organizations()
            ->where('organizations.id', $application->opportunity->organization_id)
            ->first();

        return $organizationUser && $organizationUser->pivot->role === 'admin';
    }
}