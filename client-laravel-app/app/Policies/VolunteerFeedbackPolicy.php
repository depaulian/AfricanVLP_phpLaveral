<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VolunteerFeedback;
use App\Models\VolunteerAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class VolunteerFeedbackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any feedback.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own feedback
    }

    /**
     * Determine whether the user can view the feedback.
     */
    public function view(User $user, VolunteerFeedback $feedback): bool
    {
        // Users can view feedback they gave or received
        if ($feedback->reviewer_id === $user->id || $feedback->reviewee_id === $user->id) {
            return true;
        }

        // Organization admins can view feedback for their organization
        if ($feedback->assignment && $feedback->assignment->organization) {
            return $feedback->assignment->organization->users()
                ->where('user_id', $user->id)
                ->whereIn('role', ['admin', 'supervisor'])
                ->exists();
        }

        // Public feedback can be viewed by anyone
        return $feedback->is_public && $feedback->status === 'published';
    }

    /**
     * Determine whether the user can create feedback for an assignment.
     */
    public function createFeedback(User $user, VolunteerAssignment $assignment, string $feedbackType): bool
    {
        // Check if user can provide this type of feedback for this assignment
        return match ($feedbackType) {
            'volunteer_to_organization' => $this->canVolunteerProvideFeedback($user, $assignment),
            'organization_to_volunteer' => $this->canOrganizationProvideFeedback($user, $assignment),
            'supervisor_to_volunteer' => $this->canSupervisorProvideFeedback($user, $assignment),
            'volunteer_to_supervisor' => $this->canVolunteerProvideFeedback($user, $assignment),
            'beneficiary_to_volunteer' => $this->canBeneficiaryProvideFeedback($user, $assignment),
            default => false,
        };
    }

    /**
     * Determine whether the user can update the feedback.
     */
    public function update(User $user, VolunteerFeedback $feedback): bool
    {
        // Only the reviewer can update their feedback
        if ($feedback->reviewer_id !== $user->id) {
            return false;
        }

        // Feedback must be editable
        return $feedback->canBeEdited();
    }

    /**
     * Determine whether the user can delete the feedback.
     */
    public function delete(User $user, VolunteerFeedback $feedback): bool
    {
        // Only the reviewer can delete their feedback (if it's still a draft)
        return $feedback->reviewer_id === $user->id && $feedback->status === 'draft';
    }

    /**
     * Determine whether the user can respond to the feedback.
     */
    public function respond(User $user, VolunteerFeedback $feedback): bool
    {
        // Only the reviewee can respond to feedback about them
        if ($feedback->reviewee_id !== $user->id) {
            return false;
        }

        // Feedback must be submitted and not already have a response
        return $feedback->status !== 'draft' && !$feedback->response;
    }

    /**
     * Determine whether the user can request follow-up for the feedback.
     */
    public function requestFollowUp(User $user, VolunteerFeedback $feedback): bool
    {
        // Reviewee can request follow-up
        if ($feedback->reviewee_id === $user->id) {
            return true;
        }

        // Organization admins can request follow-up
        if ($feedback->assignment && $feedback->assignment->organization) {
            return $feedback->assignment->organization->users()
                ->where('user_id', $user->id)
                ->where('role', 'admin')
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can publish the feedback.
     */
    public function publish(User $user, VolunteerFeedback $feedback): bool
    {
        // Organization admins can publish feedback
        if ($feedback->assignment && $feedback->assignment->organization) {
            return $feedback->assignment->organization->users()
                ->where('user_id', $user->id)
                ->where('role', 'admin')
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can moderate the feedback.
     */
    public function moderate(User $user, VolunteerFeedback $feedback): bool
    {
        // Organization admins can moderate feedback for their organization
        if ($feedback->assignment && $feedback->assignment->organization) {
            return $feedback->assignment->organization->users()
                ->where('user_id', $user->id)
                ->where('role', 'admin')
                ->exists();
        }

        // System admins can moderate any feedback
        return $user->hasRole('admin');
    }

    /**
     * Check if volunteer can provide feedback
     */
    private function canVolunteerProvideFeedback(User $user, VolunteerAssignment $assignment): bool
    {
        // User must be the volunteer for this assignment
        if ($assignment->volunteer_id !== $user->id) {
            return false;
        }

        // Assignment must be active or completed
        return in_array($assignment->status, ['active', 'completed']);
    }

    /**
     * Check if organization can provide feedback
     */
    private function canOrganizationProvideFeedback(User $user, VolunteerAssignment $assignment): bool
    {
        // User must be an admin or supervisor of the organization
        return $assignment->organization->users()
            ->where('user_id', $user->id)
            ->whereIn('role', ['admin', 'supervisor'])
            ->exists();
    }

    /**
     * Check if supervisor can provide feedback
     */
    private function canSupervisorProvideFeedback(User $user, VolunteerAssignment $assignment): bool
    {
        // User must be a supervisor for the organization
        if (!$assignment->organization->users()
            ->where('user_id', $user->id)
            ->where('role', 'supervisor')
            ->exists()) {
            return false;
        }

        // If assignment has a specific supervisor, user must be that supervisor
        if ($assignment->supervisor_id) {
            return $assignment->supervisor_id === $user->id;
        }

        return true;
    }

    /**
     * Check if beneficiary can provide feedback
     */
    private function canBeneficiaryProvideFeedback(User $user, VolunteerAssignment $assignment): bool
    {
        // This would depend on your system's implementation of beneficiaries
        // For now, we'll allow any authenticated user to provide beneficiary feedback
        // In a real system, you'd want to verify the user is actually a beneficiary
        // of the specific volunteer assignment
        
        // You might check if the user is in a beneficiaries table related to the assignment
        // or if they have a specific role/relationship to the assignment
        
        return true; // Simplified for this implementation
    }

    /**
     * Check if user has already provided feedback of this type for the assignment
     */
    public function hasNotAlreadyProvidedFeedback(User $user, VolunteerAssignment $assignment, string $feedbackType): bool
    {
        return !VolunteerFeedback::where('assignment_id', $assignment->id)
            ->where('reviewer_id', $user->id)
            ->where('feedback_type', $feedbackType)
            ->exists();
    }

    /**
     * Check if feedback period is still open
     */
    public function feedbackPeriodOpen(VolunteerAssignment $assignment): bool
    {
        // Allow feedback for 30 days after assignment completion
        if ($assignment->status === 'completed' && $assignment->end_date) {
            return $assignment->end_date->addDays(30)->isFuture();
        }

        // Allow feedback during active assignments
        return in_array($assignment->status, ['active', 'completed']);
    }
}