<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Auth\Access\Response;

class UserDocumentPolicy
{
    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own documents
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, UserDocument $document): bool
    {
        // Users can view their own documents
        if ($user->id === $document->user_id) {
            return true;
        }

        // Admins can view any document
        if ($user->hasRole('admin')) {
            return true;
        }

        // Organization admins can view documents of their organization members
        if ($user->hasRole('organization_admin')) {
            return $user->organizations()
                ->whereHas('users', function ($query) use ($document) {
                    $query->where('user_id', $document->user_id);
                })
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user): bool
    {
        // Check if user has reached their document limit
        $maxDocuments = config('documents.max_files_per_user', 50);
        $currentCount = $user->documents()->count();
        
        if ($currentCount >= $maxDocuments) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, UserDocument $document): bool
    {
        // Users can only update their own documents
        if ($user->id !== $document->user_id) {
            return false;
        }

        // Cannot update verified documents (would need to upload new version)
        if ($document->verification_status === 'verified') {
            return false;
        }

        // Cannot update archived documents
        if ($document->is_archived) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, UserDocument $document): bool
    {
        // Users can delete their own documents
        if ($user->id === $document->user_id) {
            // Cannot delete verified documents that are required
            if ($document->verification_status === 'verified' && $this->isRequiredDocument($document)) {
                return false;
            }
            return true;
        }

        // Admins can delete any document
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the document.
     */
    public function restore(User $user, UserDocument $document): bool
    {
        // Only admins can restore documents
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the document.
     */
    public function forceDelete(User $user, UserDocument $document): bool
    {
        // Only super admins can permanently delete documents
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, UserDocument $document): bool
    {
        // Same rules as viewing
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can share the document.
     */
    public function share(User $user, UserDocument $document): bool
    {
        // Users can share their own documents
        if ($user->id !== $document->user_id) {
            return false;
        }

        // Check if sharing is enabled
        if (!config('documents.sharing.enabled', true)) {
            return false;
        }

        // Cannot share sensitive documents
        if ($document->is_sensitive) {
            return false;
        }

        // Cannot share unverified identity documents
        if ($document->category === 'identity' && $document->verification_status !== 'verified') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can verify the document.
     */
    public function verify(User $user, UserDocument $document): bool
    {
        // Only admins can verify documents
        if (!$user->hasRole('admin')) {
            return false;
        }

        // Cannot verify own documents
        if ($user->id === $document->user_id) {
            return false;
        }

        // Can only verify pending documents
        if ($document->verification_status !== 'pending') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can archive the document.
     */
    public function archive(User $user, UserDocument $document): bool
    {
        // Users can archive their own documents
        if ($user->id === $document->user_id) {
            return true;
        }

        // Admins can archive any document
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create a backup of the document.
     */
    public function backup(User $user, UserDocument $document): bool
    {
        // Users can backup their own documents
        if ($user->id === $document->user_id) {
            return true;
        }

        // Admins can backup any document
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view document analytics.
     */
    public function viewAnalytics(User $user, UserDocument $document): bool
    {
        // Users can view analytics for their own documents
        if ($user->id === $document->user_id) {
            return true;
        }

        // Admins can view analytics for any document
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage document categories.
     */
    public function manageCategories(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can access the verification queue.
     */
    public function accessVerificationQueue(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can export documents.
     */
    public function export(User $user, UserDocument $document = null): bool
    {
        if ($document) {
            // Users can export their own documents
            if ($user->id === $document->user_id) {
                return true;
            }
        }

        // Admins can export any documents
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can bulk manage documents.
     */
    public function bulkManage(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Check if a document is required based on its category and user profile.
     */
    protected function isRequiredDocument(UserDocument $document): bool
    {
        $requiredCategories = config('documents.verification.require_admin_verification', []);
        
        return in_array($document->category, $requiredCategories);
    }

    /**
     * Determine whether the user can view document history.
     */
    public function viewHistory(User $user, UserDocument $document): bool
    {
        // Users can view history of their own documents
        if ($user->id === $document->user_id) {
            return true;
        }

        // Admins can view history of any document
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage document expiration.
     */
    public function manageExpiration(User $user, UserDocument $document): bool
    {
        // Users can manage expiration of their own documents
        if ($user->id === $document->user_id) {
            return true;
        }

        // Admins can manage expiration of any document
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }
}