<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminManagementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the admin list.
     */
    public function viewAdminList(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAdminRole('admin');
    }

    /**
     * Determine if the user can view a specific admin.
     */
    public function viewAdmin(User $user, User $admin): bool
    {
        // Super admins can view all admins
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admins can view other admins except super admins
        if ($user->hasAdminRole('admin')) {
            return !$admin->isSuperAdmin();
        }

        // Users can view their own profile
        return $user->id === $admin->id;
    }

    /**
     * Determine if the user can create admin users.
     */
    public function createAdmin(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAdminRole('admin');
    }

    /**
     * Determine if the user can edit a specific admin.
     */
    public function editAdmin(User $user, User $admin): bool
    {
        // Super admins can edit all admins
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admins can edit other admins except super admins
        if ($user->hasAdminRole('admin')) {
            return !$admin->isSuperAdmin();
        }

        // Users can edit their own profile (limited fields)
        return $user->id === $admin->id;
    }

    /**
     * Determine if the user can delete a specific admin.
     */
    public function deleteAdmin(User $user, User $admin): bool
    {
        // Cannot delete yourself
        if ($user->id === $admin->id) {
            return false;
        }

        // Super admins can delete all admins except other super admins
        if ($user->isSuperAdmin()) {
            return !$admin->isSuperAdmin() || $user->id !== $admin->id;
        }

        // Admins can delete lower-level admins
        if ($user->hasAdminRole('admin')) {
            return !in_array($admin->admin_role, ['super_admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine if the user can perform bulk actions on admins.
     */
    public function bulkActionAdmins(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAdminRole('admin');
    }

    /**
     * Determine if the user can export admin data.
     */
    public function exportAdmins(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAdminRole('admin');
    }

    /**
     * Determine if the user can manage admin roles.
     */
    public function manageAdminRoles(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can assign a specific role.
     */
    public function assignRole(User $user, string $role): bool
    {
        // Super admins can assign any role
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admins can assign roles below admin level
        if ($user->hasAdminRole('admin')) {
            return !in_array($role, ['super_admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine if the user can manage admin permissions.
     */
    public function manageAdminPermissions(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAdminRole('admin');
    }
}
