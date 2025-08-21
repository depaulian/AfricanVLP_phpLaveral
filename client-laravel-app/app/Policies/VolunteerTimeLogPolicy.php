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
        // Users can view their own time logs
        return true;
    }

    /**
     * Determine whether the user can view the time log.
     */
    public function view(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Volunteer can view their own time logs
        if ($timeLog->assignment->application->user_id === $user->id) {
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
     * Determine whether the user can create time logs.
     */
    public function create(User $user): bool
    {
        // Any authenticated user with active assignments can create time logs
        return $user->volunteerAssignments()->where('status', 'active')->exists();
    }

    /**
     * Determine whether the user can update the time log.
     */
    public function update(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Cannot update approved time logs
        if ($timeLog->supervisor_approved) {
            return false;
        }

        // Only the volunteer can update their own time logs
        if ($timeLog->assignment->application->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the time log.
     */
    public function delete(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Cannot delete approved time logs
        if ($timeLog->supervisor_approved) {
            return false;
        }

        // Volunteer can delete their own time logs
        if ($timeLog->assignment->application->user_id === $user->id) {
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

        // Cannot approve own time logs
        if ($timeLog->assignment->application->user_id === $user->id) {
            return false;
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
        // Users with supervisor or organization management roles can bulk approve
        return $user->supervisedAssignments()->exists() || 
               $user->organizations()->wherePivotIn('role', ['admin', 'manager', 'coordinator'])->exists();
    }

    /**
     * Determine whether the user can export time log data.
     */
    public function export(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Volunteer can export their own time logs
        if ($timeLog->assignment->application->user_id === $user->id) {
            return true;
        }

        // Supervisor can export time logs for assignments they supervise
        if ($timeLog->assignment->supervisor_id === $user->id) {
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
     * Determine whether the user can add notes to the time log.
     */
    public function addNotes(User $user, VolunteerTimeLog $timeLog): bool
    {
        // Volunteer can add notes to their own time logs
        if ($timeLog->assignment->application->user_id === $user->id) {
            return true;
        }

        // Supervisor can add notes
        if ($timeLog->assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members can add notes
        $organizationUser = $user->organizations()
            ->where('organizations.id', $timeLog->assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }
}