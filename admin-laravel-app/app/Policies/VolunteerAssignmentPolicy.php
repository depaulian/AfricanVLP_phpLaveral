<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class VolunteerAssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assignments.
     */
    public function viewAny(User $user): bool
    {
        // Admin users can view all assignments
        return $user->hasRole(['admin', 'super_admin']) || $user->organizations()->exists();
    }

    /**
     * Determine whether the user can view the assignment.
     */
    public function view(User $user, VolunteerAssignment $assignment): bool
    {
        // Admin users can view all assignments
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can view assignments they supervise
        if ($assignment->supervisor_id === $user->id) {
            return true;
        }

        // Opportunity creator can view assignments
        if ($assignment->application->opportunity->created_by === $user->id) {
            return true;
        }

        // Organization members with appropriate role can view assignments
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create assignments.
     */
    public function create(User $user): bool
    {
        // Admin users can create assignments
        return $user->hasRole(['admin', 'super_admin']) || $user->organizations()->exists();
    }

    /**
     * Determine whether the user can update the assignment.
     */
    public function update(User $user, VolunteerAssignment $assignment): bool
    {
        // Admin users can update assignments
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can update assignments they supervise
        if ($assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can update assignments
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the assignment.
     */
    public function delete(User $user, VolunteerAssignment $assignment): bool
    {
        // Super admin can delete any assignment
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can delete assignments with restrictions
        if ($user->hasRole('admin') && $assignment->timeLogs()->count() === 0) {
            return true;
        }

        // Organization admin can delete assignments
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && $organizationUser->pivot->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve hours for the assignment.
     */
    public function approveHours(User $user, VolunteerAssignment $assignment): bool
    {
        // Admin users can approve hours
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can approve hours
        if ($assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can approve hours
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk approve hours.
     */
    public function bulkApproveHours(User $user): bool
    {
        // Admin users can bulk approve hours
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can complete the assignment.
     */
    public function complete(User $user, VolunteerAssignment $assignment): bool
    {
        // Admin users can complete assignments
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can complete assignments they supervise
        if ($assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can complete assignments
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can issue certificate for the assignment.
     */
    public function issueCertificate(User $user, VolunteerAssignment $assignment): bool
    {
        // Assignment must be completed
        if ($assignment->status !== 'completed') {
            return false;
        }

        // Admin users can issue certificates
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization members with appropriate role can issue certificates
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view time logs for the assignment.
     */
    public function viewTimeLogs(User $user, VolunteerAssignment $assignment): bool
    {
        return $this->view($user, $assignment);
    }

    /**
     * Determine whether the user can generate reports for the assignment.
     */
    public function generateReports(User $user, VolunteerAssignment $assignment): bool
    {
        // Admin users can generate reports
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can generate reports for assignments they supervise
        if ($assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can generate reports
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can extend the assignment.
     */
    public function extend(User $user, VolunteerAssignment $assignment): bool
    {
        // Assignment must be active
        if ($assignment->status !== 'active') {
            return false;
        }

        // Admin users can extend assignments
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can extend assignments they supervise
        if ($assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can extend assignments
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can terminate the assignment.
     */
    public function terminate(User $user, VolunteerAssignment $assignment): bool
    {
        // Assignment must be active
        if ($assignment->status !== 'active') {
            return false;
        }

        // Admin users can terminate assignments
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor can terminate assignments they supervise
        if ($assignment->supervisor_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can terminate assignments
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        if ($organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export assignment data.
     */
    public function export(User $user, VolunteerAssignment $assignment): bool
    {
        // Admin users can export data
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization admins can export their assignment data
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        return $organizationUser && $organizationUser->pivot->role === 'admin';
    }

    /**
     * Determine whether the user can assign supervisor.
     */
    public function assignSupervisor(User $user, VolunteerAssignment $assignment): bool
    {
        // Admin users can assign supervisors
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization admins can assign supervisors
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        return $organizationUser && $organizationUser->pivot->role === 'admin';
    }
}