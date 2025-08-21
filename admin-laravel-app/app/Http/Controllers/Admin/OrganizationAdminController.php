<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationAdmin;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class OrganizationAdminController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display organization admin dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = OrganizationAdmin::with(['organization', 'user', 'assignedBy'])
            ->orderBy('created', 'desc');

        // Apply filters
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->inactive();
                    break;
                case 'expired':
                    $query->expired();
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('organization', function ($orgQuery) use ($search) {
                    $orgQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created', '<=', $request->date_to);
        }

        $admins = $query->paginate(20);
        $organizations = Organization::orderBy('name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'admins' => $admins,
                'organizations' => $organizations,
                'stats' => $this->getAdminStats(),
            ]);
        }

        return view('admin.organization-admins.index', compact('admins', 'organizations'));
    }

    /**
     * Show create admin form
     */
    public function create(): View
    {
        $organizations = Organization::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();
        $roles = OrganizationAdmin::getAllRoles();
        $permissions = OrganizationAdmin::getAllPermissions();

        return view('admin.organization-admins.create', compact('organizations', 'users', 'roles', 'permissions'));
    }

    /**
     * Store new organization admin
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'organization_id' => 'required|exists:organizations,id',
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:' . implode(',', OrganizationAdmin::getAllRoles()),
            'permissions' => 'sometimes|array',
            'permissions.*' => 'in:' . implode(',', OrganizationAdmin::getAllPermissions()),
            'expires_at' => 'sometimes|nullable|date|after:today',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Check if user is already an admin for this organization
        $existingAdmin = OrganizationAdmin::where('organization_id', $request->organization_id)
            ->where('user_id', $request->user_id)
            ->where('is_active', true)
            ->first();

        if ($existingAdmin) {
            $error = 'User is already an active admin for this organization';
            if ($request->expectsJson()) {
                return response()->json(['error' => $error], 422);
            }
            return back()->withErrors(['user_id' => $error])->withInput();
        }

        DB::beginTransaction();
        try {
            // Set default permissions if not provided
            $permissions = $request->permissions ?? OrganizationAdmin::getDefaultPermissions($request->role);

            $admin = OrganizationAdmin::create([
                'organization_id' => $request->organization_id,
                'user_id' => $request->user_id,
                'role' => $request->role,
                'permissions' => $permissions,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'expires_at' => $request->expires_at,
                'is_active' => true,
                'notes' => $request->notes,
            ]);

            // Log the assignment
            $this->activityLogService->log(
                'create',
                $admin,
                auth()->user(),
                "Assigned {$admin->user->name} as {$admin->role} for {$admin->organization->name}"
            );

            // Send notification email (if implemented)
            // $this->sendAdminAssignmentNotification($admin);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Organization admin assigned successfully',
                    'admin' => $admin->load(['organization', 'user', 'assignedBy'])
                ]);
            }

            return redirect()->route('admin.organization-admins.index')
                            ->with('success', 'Organization admin assigned successfully');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to assign admin'], 500);
            }
            
            return back()->with('error', 'Failed to assign admin');
        }
    }

    /**
     * Show specific organization admin
     */
    public function show(OrganizationAdmin $organizationAdmin): View|JsonResponse
    {
        $organizationAdmin->load(['organization', 'user', 'assignedBy']);

        if (request()->expectsJson()) {
            return response()->json(['admin' => $organizationAdmin]);
        }

        return view('admin.organization-admins.show', compact('organizationAdmin'));
    }

    /**
     * Show edit admin form
     */
    public function edit(OrganizationAdmin $organizationAdmin): View
    {
        $organizationAdmin->load(['organization', 'user']);
        $organizations = Organization::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();
        $roles = OrganizationAdmin::getAllRoles();
        $permissions = OrganizationAdmin::getAllPermissions();

        return view('admin.organization-admins.edit', compact('organizationAdmin', 'organizations', 'users', 'roles', 'permissions'));
    }

    /**
     * Update organization admin
     */
    public function update(Request $request, OrganizationAdmin $organizationAdmin): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'sometimes|in:' . implode(',', OrganizationAdmin::getAllRoles()),
            'permissions' => 'sometimes|array',
            'permissions.*' => 'in:' . implode(',', OrganizationAdmin::getAllPermissions()),
            'expires_at' => 'sometimes|nullable|date|after:today',
            'is_active' => 'sometimes|boolean',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $oldData = $organizationAdmin->toArray();

        // Update permissions if role changed
        if ($request->filled('role') && $request->role !== $organizationAdmin->role) {
            $request->merge([
                'permissions' => $request->permissions ?? OrganizationAdmin::getDefaultPermissions($request->role)
            ]);
        }

        $organizationAdmin->update($request->only([
            'role', 'permissions', 'expires_at', 'is_active', 'notes'
        ]));

        // Log the update
        $this->activityLogService->log(
            'update',
            $organizationAdmin,
            auth()->user(),
            "Updated admin role for {$organizationAdmin->user->name} in {$organizationAdmin->organization->name}",
            ['old_data' => $oldData, 'new_data' => $organizationAdmin->fresh()->toArray()]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Organization admin updated successfully',
                'admin' => $organizationAdmin->fresh()->load(['organization', 'user', 'assignedBy'])
            ]);
        }

        return redirect()->route('admin.organization-admins.show', $organizationAdmin)
                        ->with('success', 'Organization admin updated successfully');
    }

    /**
     * Remove organization admin
     */
    public function destroy(OrganizationAdmin $organizationAdmin): JsonResponse|RedirectResponse
    {
        try {
            $adminName = $organizationAdmin->user->name;
            $orgName = $organizationAdmin->organization->name;

            $organizationAdmin->delete();

            // Log the removal
            $this->activityLogService->log(
                'delete',
                $organizationAdmin,
                auth()->user(),
                "Removed {$adminName} as admin from {$orgName}"
            );

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Organization admin removed successfully']);
            }

            return redirect()->route('admin.organization-admins.index')
                            ->with('success', 'Organization admin removed successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to remove admin'], 500);
            }
            
            return back()->with('error', 'Failed to remove admin');
        }
    }

    /**
     * Bulk update organization admins
     */
    public function bulkUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'admin_ids' => 'required|array',
            'admin_ids.*' => 'exists:organization_admins,id',
            'action' => 'required|in:activate,deactivate,extend,role,delete',
            'value' => 'required_unless:action,activate,deactivate,delete',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        $admins = OrganizationAdmin::whereIn('id', $request->admin_ids)->get();
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($admins as $admin) {
                switch ($request->action) {
                    case 'activate':
                        $admin->update(['is_active' => true]);
                        break;
                    case 'deactivate':
                        $admin->update(['is_active' => false]);
                        break;
                    case 'extend':
                        $admin->update(['expires_at' => Carbon::parse($request->value)]);
                        break;
                    case 'role':
                        $admin->update([
                            'role' => $request->value,
                            'permissions' => OrganizationAdmin::getDefaultPermissions($request->value)
                        ]);
                        break;
                    case 'delete':
                        $admin->delete();
                        break;
                }

                $this->activityLogService->log(
                    $request->action === 'delete' ? 'delete' : 'update',
                    $admin,
                    auth()->user(),
                    "Bulk {$request->action} on admin: {$admin->user->name}"
                );

                $updated++;
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Successfully updated {$updated} admin records"
                ]);
            }

            return back()->with('success', "Successfully updated {$updated} admin records");

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bulk update failed'], 500);
            }
            
            return back()->with('error', 'Bulk update failed');
        }
    }

    /**
     * Get organization admin statistics
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getAdminStats());
    }

    /**
     * Export organization admin data as CSV
     */
    public function export(Request $request)
    {
        $query = OrganizationAdmin::with(['organization', 'user', 'assignedBy']);

        // Apply same filters as index
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->inactive();
                    break;
                case 'expired':
                    $query->expired();
                    break;
            }
        }

        $admins = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="organization_admins_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($admins) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Organization', 'Admin Name', 'Admin Email', 'Role', 'Permissions',
                'Status', 'Assigned By', 'Assigned At', 'Expires At', 'Notes'
            ]);

            // CSV data
            foreach ($admins as $admin) {
                fputcsv($file, [
                    $admin->id,
                    $admin->organization->name,
                    $admin->user->name,
                    $admin->user->email,
                    $admin->role,
                    $admin->formatted_permissions,
                    $admin->isActive() ? 'Active' : 'Inactive',
                    $admin->assignedBy->name ?? '',
                    $admin->assigned_at->format('Y-m-d H:i:s'),
                    $admin->expires_at ? $admin->expires_at->format('Y-m-d H:i:s') : 'Never',
                    $admin->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get organization admin analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30));
        $dateTo = $request->get('date_to', Carbon::now());

        // Admins by role
        $adminsByRole = OrganizationAdmin::selectRaw('role, COUNT(*) as count')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->groupBy('role')
            ->get();

        // Admins by organization
        $adminsByOrg = OrganizationAdmin::selectRaw('organization_id, COUNT(*) as count')
            ->with('organization:id,name')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->groupBy('organization_id')
            ->get();

        // Admin status distribution
        $statusDistribution = [
            'active' => OrganizationAdmin::active()->count(),
            'inactive' => OrganizationAdmin::inactive()->count(),
            'expired' => OrganizationAdmin::expired()->count(),
        ];

        // Daily admin assignments
        $dailyAssignments = OrganizationAdmin::selectRaw('DATE(created) as date, COUNT(*) as count')
            ->whereBetween('created', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Expiring admins (next 30 days)
        $expiringAdmins = OrganizationAdmin::with(['organization', 'user'])
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->where('is_active', true)
            ->orderBy('expires_at')
            ->get();

        return response()->json([
            'admins_by_role' => $adminsByRole,
            'admins_by_organization' => $adminsByOrg,
            'status_distribution' => $statusDistribution,
            'daily_assignments' => $dailyAssignments,
            'expiring_admins' => $expiringAdmins,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Check admin permissions for organization
     */
    public function checkPermissions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'organization_id' => 'required|exists:organizations,id',
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = OrganizationAdmin::where('organization_id', $request->organization_id)
            ->where('user_id', $request->user_id)
            ->where('is_active', true)
            ->first();

        $hasPermission = $admin ? $admin->hasPermission($request->permission) : false;

        return response()->json([
            'has_permission' => $hasPermission,
            'admin' => $admin,
        ]);
    }

    /**
     * Get admin statistics for dashboard
     */
    private function getAdminStats(): array
    {
        $total = OrganizationAdmin::count();
        $active = OrganizationAdmin::active()->count();
        $inactive = OrganizationAdmin::inactive()->count();
        $expired = OrganizationAdmin::expired()->count();
        
        $recentAssignments = OrganizationAdmin::where('created', '>=', Carbon::now()->subDays(7))->count();
        $expiringThisMonth = OrganizationAdmin::where('expires_at', '>', now())
                                           ->where('expires_at', '<=', now()->addDays(30))
                                           ->where('is_active', true)
                                           ->count();

        // Role distribution
        $roleDistribution = OrganizationAdmin::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'expired' => $expired,
            'recent_assignments' => $recentAssignments,
            'expiring_this_month' => $expiringThisMonth,
            'role_distribution' => $roleDistribution,
            'activity_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }
}
