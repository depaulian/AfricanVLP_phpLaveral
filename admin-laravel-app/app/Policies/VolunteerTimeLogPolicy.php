<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VolunteerTimeLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class VolunteerTimeLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any time logs.
     */
    public function viewAny(User $user): bool
    {
        // Admin users can view all time logs
        return $user->hasRole(['admin', 'super_admin']) || $user->organizations()->exists();
    }

    /**
     * Determine whether the user can view the time log.
     */
    public function view(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Admin users can view all time logs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can view time logs for assignments they supervise
        if ($timeLog->assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can view time logs
        $organizationUser = $user->organizations()
            ->where('organizations.id', $timeLog->assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the time log.
     */
    public function update(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Super admin can update any time log
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can update unapproved time logs
        if ($user->hasRole('admin') && !$timeLog->supervisor_approved) {
            return true;
        }

        // Organization members with appropriate role can update time logs
        $organizationUser = $user->organizations()
            ->where('organizations.id', $timeLog->assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager']) && !$timeLog->supervisor_approved) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the time log.
     */
    public function delete(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Super admin can delete any time log
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can delete unapproved time logs
        if ($user->hasRole('admin') && !$timeLog->supervisor_approved) {
            return true;
        }

        // Organization admin can delete time logs
        $organizationUser = $user->organizations()
            ->where('organizations.id', $timeLog->assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && $organizationUser->pivot->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve the time log.
     */
    public function approve(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Cannot approve already approved time logs
        if ($timeLog->supervisor_approved) {
            return false;
        }

        // Admin users can approve time logs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can approve time logs
        if ($timeLog->assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can approve time logs
        $organizationUser = $user->organizations()
            ->where('organizations.id', $timeLog->assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can unapprove the time log.
     */
    public function unapprove(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Can only unapprove approved time logs
        if (!$timeLog->supervisor_approved) {
            return false;
        }

        // Admin users can unapprove time logs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor who approved can unapprove
        if ($timeLog->approved_by === $user->id) {
            return true;
        }

        // Organization admin can unapprove
        $organizationUser = $user->organizations()
            ->where('organizations.id', $timeLog->assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && $organizationUser->pivot->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk approve time logs.
     */
    public function bulkApprove(User $user): bool
    {
        // Admin users can bulk approve
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can export time log data.
     */
    public function export(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Admin users can export all data
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization members with appropriate role can export time logs
        $organizationUser = $user->organizations()
            ->where('organizations.id', $timeLog->assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can flag the time log.
     */
    public function flag(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Admin users can flag time logs
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can flag time logs
        if ($timeLog->assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members can flag time logs
        $organizationUser = $user->organizations()
            ->where('organizations.id', $timeLog->assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can force delete the time log.
     */
    public function forceDelete(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Only super admin can force delete
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can restore the time log.
     */
    public function restore(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Only super admin can restore
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can audit time logs.
     */
    public function audit(User $user): bool
    {
        // Only admin users can audit time logs
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can generate time log reports.
     */
    public function generateReports(User $user): bool
    {
        // Admin users can generate reports
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization members with appropriate role can generate reports
        return $user->organizations()->wherePivotIn('role', ['admin', 'manager'])->exists();
    }
}