<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with(['country', 'city']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Country filter
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'created');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $users = $query->paginate(20)->withQueryString();

        $countries = Country::orderBy('name')->get();
        $statuses = ['active', 'inactive', 'pending', 'suspended'];

        return view('admin.users.index', compact('users', 'countries', 'statuses'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $countries = Country::orderBy('name')->get();
        return view('admin.users.create', compact('countries'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'last_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'status' => 'required|in:active,inactive,pending,suspended',
            'is_admin' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'status' => $request->status,
            'is_admin' => $request->boolean('is_admin'),
            'email_verified_at' => $request->status === 'active' ? now() : null,
            'created' => now(),
            'modified' => now(),
        ]);

        return redirect()->route('admin.users.index')
                        ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['country', 'city', 'organizations']);
        
        $volunteeringHistory = $user->volunteeringHistory ?? collect();
        $organizationCount = $user->organizations->count();
        
        return view('admin.users.show', compact('user', 'volunteeringHistory', 'organizationCount'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $countries = Country::orderBy('name')->get();
        $cities = $user->country_id ? City::where('country_id', $user->country_id)->orderBy('name')->get() : collect();
        
        return view('admin.users.edit', compact('user', 'countries', 'cities'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'last_name' => 'required|string|max:45|regex:/^[a-zA-Z\s\-\'\.]+$/',
            'email' => 'required|string|email|max:100|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'status' => 'required|in:active,inactive,pending,suspended',
            'is_admin' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'status' => $request->status,
            'is_admin' => $request->boolean('is_admin'),
            'modified' => now(),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Set email verification if status is active and not already verified
        if ($request->status === 'active' && !$user->hasVerifiedEmail()) {
            $updateData['email_verified_at'] = now();
        }

        $user->update($updateData);

        return redirect()->route('admin.users.show', $user)
                        ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting the current admin user
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
                        ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        
        $user->update([
            'status' => $newStatus,
            'modified' => now(),
        ]);

        return back()->with('success', "User status changed to {$newStatus}.");
    }

    /**
     * Export users to CSV.
     */
    public function exportCsv(Request $request)
    {
        $query = User::with(['country', 'city']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        $users = $query->get();

        $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'First Name', 'Last Name', 'Email', 'Phone', 
                'Country', 'City', 'Status', 'Is Admin', 'Email Verified', 
                'Last Login', 'Created', 'Modified'
            ]);

            // CSV data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->first_name,
                    $user->last_name,
                    $user->email,
                    $user->phone_number,
                    $user->country ? $user->country->name : '',
                    $user->city ? $user->city->name : '',
                    $user->status,
                    $user->is_admin ? 'Yes' : 'No',
                    $user->hasVerifiedEmail() ? 'Yes' : 'No',
                    $user->last_login ? $user->last_login->format('Y-m-d H:i:s') : '',
                    $user->created->format('Y-m-d H:i:s'),
                    $user->modified->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Search users via AJAX.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $users = User::where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->limit(10)
                    ->get(['id', 'first_name', 'last_name', 'email']);

        return response()->json($users);
    }
}