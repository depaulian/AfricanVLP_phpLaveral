<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminManagementController extends Controller
{
    // Admin roles hierarchy
    const ADMIN_ROLES = [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator', 
        'moderator' => 'Moderator',
        'editor' => 'Editor',
        'viewer' => 'Viewer'
    ];

    // Role permissions
    const ROLE_PERMISSIONS = [
        'super_admin' => ['*'], // All permissions
        'admin' => [
            'manage_users', 'manage_organizations', 'manage_content', 
            'manage_events', 'manage_news', 'manage_resources', 
            'view_analytics', 'manage_settings'
        ],
        'moderator' => [
            'manage_content', 'manage_events', 'manage_news', 
            'view_analytics', 'moderate_forums'
        ],
        'editor' => [
            'manage_content', 'manage_events', 'manage_news'
        ],
        'viewer' => [
            'view_analytics', 'view_content'
        ]
    ];

    /**
     * Display a listing of admin users.
     */
    public function index(Request $request)
    {
        // Only super admins and admins can view admin list
        $this->authorize('viewAdminList');

        $query = User::where('is_admin', true)->with(['country', 'city']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('admin_role')) {
            $query->where('admin_role', $request->admin_role);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort
        $sortBy = $request->get('sort', 'created');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $admins = $query->paginate(20)->withQueryString();

        $countries = Country::orderBy('name')->get();
        $statuses = ['active', 'inactive', 'pending', 'suspended'];
        $roles = self::ADMIN_ROLES;

        return view('admin.admin-management.index', compact('admins', 'countries', 'statuses', 'roles'));
    }

    /**
     * Show the form for creating a new admin user.
     */
    public function create()
    {
        $this->authorize('createAdmin');

        $countries = Country::orderBy('name')->get();
        $roles = self::ADMIN_ROLES;
        
        // Only super admins can create other super admins
        if (!Auth::user()->isSuperAdmin()) {
            unset($roles['super_admin']);
        }

        return view('admin.admin-management.create', compact('countries', 'roles'));
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request)
    {
        $this->authorize('createAdmin');

        $availableRoles = array_keys(self::ADMIN_ROLES);
        
        // Only super admins can create other super admins
        if (!Auth::user()->isSuperAdmin()) {
            $availableRoles = array_filter($availableRoles, fn($role) => $role !== 'super_admin');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'last_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'admin_role' => ['required', Rule::in($availableRoles)],
            'status' => 'required|in:active,inactive,pending',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Get role permissions
        $rolePermissions = self::ROLE_PERMISSIONS[$request->admin_role] ?? [];
        $customPermissions = $request->permissions ?? [];
        
        // Merge role permissions with custom permissions
        $allPermissions = array_unique(array_merge($rolePermissions, $customPermissions));

        $admin = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'status' => $request->status,
            'is_admin' => true,
            'admin_role' => $request->admin_role,
            'admin_permissions' => json_encode($allPermissions),
            'email_verified_at' => $request->status === 'active' ? now() : null,
            'created_by' => Auth::id(),
            'created' => now(),
            'modified' => now(),
        ]);

        // Log admin creation
        activity()
            ->performedOn($admin)
            ->causedBy(Auth::user())
            ->log("Created admin user with role: {$request->admin_role}");

        return redirect()->route('admin.admin-management.index')
                        ->with('success', 'Admin user created successfully.');
    }

    /**
     * Display the specified admin user.
     */
    public function show(User $admin)
    {
        $this->authorize('viewAdmin', $admin);

        if (!$admin->is_admin) {
            abort(404, 'Admin user not found.');
        }

        $admin->load(['country', 'city']);
        
        // Get admin permissions
        $permissions = json_decode($admin->admin_permissions ?? '[]', true);
        $rolePermissions = self::ROLE_PERMISSIONS[$admin->admin_role] ?? [];
        
        // Get recent activity
        $recentActivity = activity()
            ->causedBy($admin)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.admin-management.show', compact('admin', 'permissions', 'rolePermissions', 'recentActivity'));
    }

    /**
     * Show the form for editing the specified admin user.
     */
    public function edit(User $admin)
    {
        $this->authorize('editAdmin', $admin);

        if (!$admin->is_admin) {
            abort(404, 'Admin user not found.');
        }

        $countries = Country::orderBy('name')->get();
        $roles = self::ADMIN_ROLES;
        
        // Only super admins can edit super admin role
        if (!Auth::user()->isSuperAdmin() && $admin->admin_role === 'super_admin') {
            abort(403, 'Insufficient permissions to edit super admin.');
        }

        // Only super admins can assign super admin role
        if (!Auth::user()->isSuperAdmin()) {
            unset($roles['super_admin']);
        }

        $currentPermissions = json_decode($admin->admin_permissions ?? '[]', true);
        $allPermissions = [
            'manage_users', 'manage_organizations', 'manage_content', 
            'manage_events', 'manage_news', 'manage_resources', 
            'view_analytics', 'manage_settings', 'moderate_forums'
        ];

        return view('admin.admin-management.edit', compact('admin', 'countries', 'roles', 'currentPermissions', 'allPermissions'));
    }

    /**
     * Update the specified admin user.
     */
    public function update(Request $request, User $admin)
    {
        $this->authorize('editAdmin', $admin);

        if (!$admin->is_admin) {
            abort(404, 'Admin user not found.');
        }

        // Prevent editing own super admin role
        if ($admin->id === Auth::id() && $admin->admin_role === 'super_admin' && $request->admin_role !== 'super_admin') {
            return back()->with('error', 'You cannot demote yourself from super admin role.');
        }

        $availableRoles = array_keys(self::ADMIN_ROLES);
        
        // Only super admins can assign super admin role
        if (!Auth::user()->isSuperAdmin()) {
            $availableRoles = array_filter($availableRoles, fn($role) => $role !== 'super_admin');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'last_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users')->ignore($admin->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'admin_role' => ['required', Rule::in($availableRoles)],
            'status' => 'required|in:active,inactive,pending,suspended',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Get role permissions
        $rolePermissions = self::ROLE_PERMISSIONS[$request->admin_role] ?? [];
        $customPermissions = $request->permissions ?? [];
        
        // Merge role permissions with custom permissions
        $allPermissions = array_unique(array_merge($rolePermissions, $customPermissions));

        $updateData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'status' => $request->status,
            'admin_role' => $request->admin_role,
            'admin_permissions' => json_encode($allPermissions),
            'modified' => now(),
        ];

        // Update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Update email verification if status changed to active
        if ($request->status === 'active' && !$admin->hasVerifiedEmail()) {
            $updateData['email_verified_at'] = now();
        }

        $oldRole = $admin->admin_role;
        $admin->update($updateData);

        // Log role change if applicable
        if ($oldRole !== $request->admin_role) {
            activity()
                ->performedOn($admin)
                ->causedBy(Auth::user())
                ->log("Changed admin role from {$oldRole} to {$request->admin_role}");
        }

        return redirect()->route('admin.admin-management.show', $admin)
                        ->with('success', 'Admin user updated successfully.');
    }

    /**
     * Remove the specified admin user.
     */
    public function destroy(User $admin)
    {
        $this->authorize('deleteAdmin', $admin);

        if (!$admin->is_admin) {
            abort(404, 'Admin user not found.');
        }

        // Prevent deleting own account
        if ($admin->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Prevent non-super admins from deleting super admins
        if ($admin->admin_role === 'super_admin' && !Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'You cannot delete a super admin account.');
        }

        $adminName = $admin->full_name;
        $adminRole = $admin->admin_role;

        $admin->delete();

        // Log admin deletion
        activity()
            ->causedBy(Auth::user())
            ->log("Deleted admin user: {$adminName} (Role: {$adminRole})");

        return redirect()->route('admin.admin-management.index')
                        ->with('success', 'Admin user deleted successfully.');
    }

    /**
     * Toggle admin user status.
     */
    public function toggleStatus(User $admin)
    {
        $this->authorize('editAdmin', $admin);

        if (!$admin->is_admin) {
            abort(404, 'Admin user not found.');
        }

        // Prevent deactivating own account
        if ($admin->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $newStatus = $admin->status === 'active' ? 'inactive' : 'active';
        
        $admin->update([
            'status' => $newStatus,
            'modified' => now(),
        ]);

        // Log status change
        activity()
            ->performedOn($admin)
            ->causedBy(Auth::user())
            ->log("Changed admin status to: {$newStatus}");

        return back()->with('success', "Admin status changed to {$newStatus}.");
    }

    /**
     * Bulk actions for admin users.
     */
    public function bulkAction(Request $request)
    {
        $this->authorize('bulkActionAdmins');

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete,change_role',
            'admin_ids' => 'required|array|min:1',
            'admin_ids.*' => 'exists:users,id',
            'new_role' => 'required_if:action,change_role|in:admin,moderator,editor,viewer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $adminIds = $request->admin_ids;
        $action = $request->action;
        $currentUserId = Auth::id();

        // Remove current user from bulk actions
        $adminIds = array_filter($adminIds, fn($id) => $id != $currentUserId);

        if (empty($adminIds)) {
            return back()->with('error', 'Cannot perform bulk actions on your own account.');
        }

        $admins = User::whereIn('id', $adminIds)->where('is_admin', true)->get();
        $count = 0;

        foreach ($admins as $admin) {
            // Skip super admins if current user is not super admin
            if ($admin->admin_role === 'super_admin' && !Auth::user()->isSuperAdmin()) {
                continue;
            }

            switch ($action) {
                case 'activate':
                    $admin->update(['status' => 'active']);
                    $count++;
                    break;
                case 'deactivate':
                    $admin->update(['status' => 'inactive']);
                    $count++;
                    break;
                case 'delete':
                    $admin->delete();
                    $count++;
                    break;
                case 'change_role':
                    $admin->update(['admin_role' => $request->new_role]);
                    $count++;
                    break;
            }
        }

        // Log bulk action
        activity()
            ->causedBy(Auth::user())
            ->log("Performed bulk action '{$action}' on {$count} admin users");

        return back()->with('success', "Bulk action completed on {$count} admin users.");
    }

    /**
     * Export admin users to CSV.
     */
    public function exportCsv(Request $request)
    {
        $this->authorize('exportAdmins');

        $query = User::where('is_admin', true)->with(['country', 'city']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('admin_role')) {
            $query->where('admin_role', $request->admin_role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $admins = $query->get();

        $filename = 'admin_users_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($admins) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'First Name', 'Last Name', 'Email', 'Phone', 
                'Country', 'City', 'Admin Role', 'Status', 'Email Verified', 
                'Last Login', 'Created', 'Modified'
            ]);

            // CSV data
            foreach ($admins as $admin) {
                fputcsv($file, [
                    $admin->id,
                    $admin->first_name,
                    $admin->last_name,
                    $admin->email,
                    $admin->phone_number,
                    $admin->country ? $admin->country->name : '',
                    $admin->city ? $admin->city->name : '',
                    $admin->admin_role ?? 'admin',
                    $admin->status,
                    $admin->hasVerifiedEmail() ? 'Yes' : 'No',
                    $admin->last_login ? $admin->last_login->format('Y-m-d H:i:s') : '',
                    $admin->created->format('Y-m-d H:i:s'),
                    $admin->modified->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
