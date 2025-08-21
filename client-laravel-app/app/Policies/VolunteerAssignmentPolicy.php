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
        // Users can view their own assignments
        return true;
    }

    /**
     * Determine whether the user can view the assignment.
     */
    public function view(User $user, VolunteerAssignment $assignment): bool
    {
        // Volunteer can view their own assignment
        if ($assignment->application->user_id === $user->id) {
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
     * Determine whether the user can update the assignment.
     */
    public function update(User $user, VolunteerAssignment $assignment): bool
    {
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
        // Only organization admin can delete assignments
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        return $organizationUser && $organizationUser->pivot->role === 'admin';
    }

    /**
     * Determine whether the user can log hours for the assignment.
     */
    public function logHours(User $user, VolunteerAssignment $assignment): bool
    {
        // Only the volunteer can log their own hours
        if ($assignment->application->user_id !== $user->id) {
            return false;
        }

        // Assignment must be active
        if ($assignment->status !== 'active') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can approve hours for the assignment.
     */
    public function approveHours(User $user, VolunteerAssignment $assignment): bool
    {
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
     * Determine whether the user can complete the assignment.
     */
    public function complete(User $user, VolunteerAssignment $assignment): bool
    {
        // Volunteer can complete their own assignment
        if ($assignment->application->user_id === $user->id && $assignment->status === 'active') {
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
     * Determine whether the user can download certificate for the assignment.
     */
    public function downloadCertificate(User $user, VolunteerAssignment $assignment): bool
    {
        // Only the volunteer can download their certificate
        if ($assignment->application->user_id !== $user->id) {
            return false;
        }

        // Assignment must be completed and certificate issued
        if ($assignment->status !== 'completed' || !$assignment->certificate_issued) {
            return false;
        }

        return true;
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
        // Volunteer can generate their own reports
        if ($assignment->application->user_id === $user->id) {
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

        // Volunteer can terminate their own assignment
        if ($assignment->application->user_id === $user->id) {
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
}