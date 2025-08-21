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
        // Admin users can view all applications
        return $user->hasRole(['admin', 'super_admin']) || $user->organizations()->exists();
    }

    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, VolunteerApplication $application): bool
    {
        // Admin users can view all applications
        if ($user->hasRole(['admin', 'super_admin'])) {
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
     * Determine whether the user can update the application.
     */
    public function update(User $user, VolunteerApplication $application): bool
    {
        // Super admin can update any application
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can update applications with restrictions
        if ($user->hasRole('admin') && $application->status === 'pending') {
            return true;
        }

        // Organization members with appropriate role can update applications
        $organizationUser = $user->organizations()
            ->where('organizations.id', $application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the application.
     */
    public function delete(User $user, VolunteerApplication $application): bool
    {
        // Super admin can delete any application
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can delete applications with restrictions
        if ($user->hasRole('admin') && !$application->assignment) {
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

        // Admin users can review applications
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
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
     * Determine whether the user can bulk process applications.
     */
    public function bulkProcess(User $user): bool
    {
        // Admin users can bulk process applications
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view application statistics.
     */
    public function viewStatistics(User $user, VolunteerApplication $application): bool
    {
        // Admin users can view all statistics
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

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
     * Determine whether the user can export application data.
     */
    public function export(User $user, VolunteerApplication $application): bool
    {
        // Admin users can export data
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization admins can export their application data
        $organizationUser = $user->organizations()
            ->where('organizations.id', $application->opportunity->organization_id)
            ->first();

        return $organizationUser && $organizationUser->pivot->role === 'admin';
    }

    /**
     * Determine whether the user can communicate with the applicant.
     */
    public function communicate(User $user, VolunteerApplication $application): bool
    {
        return $this->view($user, $application);
    }

    /**
     * Determine whether the user can flag the application.
     */
    public function flag(User $user, VolunteerApplication $application): bool
    {
        // Admin users can flag applications
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization members can flag applications
        return $this->view($user, $application);
    }

    /**
     * Determine whether the user can archive the application.
     */
    public function archive(User $user, VolunteerApplication $application): bool
    {
        // Admin users can archive applications
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization admins can archive applications
        $organizationUser = $user->organizations()
            ->where('organizations.id', $application->opportunity->organization_id)
            ->first();

        return $organizationUser && $organizationUser->pivot->role === 'admin';
    }
}