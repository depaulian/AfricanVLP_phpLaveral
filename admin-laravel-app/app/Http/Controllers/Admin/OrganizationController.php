<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Country;
use App\Models\City;
use App\Models\CategoryOfOrganization;
use App\Models\InstitutionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations.
     */
    public function index(Request $request)
    {
        $query = Organization::with(['country', 'city', 'categoryOfOrganization', 'institutionType']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
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

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'created');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $organizations = $query->paginate(20)->withQueryString();

        $countries = Country::orderBy('name')->get();
        $categories = CategoryOfOrganization::orderBy('name')->get();
        $statuses = ['active', 'inactive', 'pending', 'suspended'];

        return view('admin.organizations.index', compact('organizations', 'countries', 'categories', 'statuses'));
    }

    /**
     * Show the form for creating a new organization.
     */
    public function create()
    {
        $countries = Country::orderBy('name')->get();
        $categories = CategoryOfOrganization::orderBy('name')->get();
        $institutionTypes = InstitutionType::orderBy('name')->get();
        
        return view('admin.organizations.create', compact('countries', 'categories', 'institutionTypes'));
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:organizations',
            'description' => 'nullable|string|max:5000',
            'email' => 'nullable|string|email|max:100',
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'website' => 'nullable|string|url|max:255',
            'address' => 'nullable|string|max:500',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'category_id' => 'nullable|exists:category_of_organizations,id',
            'institution_type_id' => 'nullable|exists:institution_types,id',
            'status' => 'required|in:active,inactive,pending,suspended',
            'facebook_url' => 'nullable|string|url|max:255',
            'twitter_url' => 'nullable|string|url|max:255',
            'linkedin_url' => 'nullable|string|url|max:255',
            'instagram_url' => 'nullable|string|url|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'established_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'employee_count' => 'nullable|integer|min:0',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $organizationData = [
            'name' => $request->name,
            'description' => $request->description,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'website' => $request->website,
            'address' => $request->address,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'category_id' => $request->category_id,
            'institution_type_id' => $request->institution_type_id,
            'status' => $request->status,
            'facebook_url' => $request->facebook_url,
            'twitter_url' => $request->twitter_url,
            'linkedin_url' => $request->linkedin_url,
            'instagram_url' => $request->instagram_url,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'established_year' => $request->established_year,
            'employee_count' => $request->employee_count,
            'created' => now(),
            'modified' => now(),
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $organizationData['logo'] = basename($logoPath);
        }

        $organization = Organization::create($organizationData);

        return redirect()->route('admin.organizations.index')
                        ->with('success', 'Organization created successfully.');
    }

    /**
     * Display the specified organization.
     */
    public function show(Organization $organization)
    {
        $organization->load([
            'country', 
            'city', 
            'categoryOfOrganization', 
            'institutionType',
            'users',
            'events',
            'news'
        ]);
        
        $stats = [
            'total_users' => $organization->users->count(),
            'active_users' => $organization->users->where('pivot.status', 'active')->count(),
            'total_events' => $organization->events->count(),
            'upcoming_events' => $organization->events->where('start_date', '>', now())->count(),
            'total_news' => $organization->news->count(),
            'published_news' => $organization->news->where('status', 'published')->count(),
        ];
        
        return view('admin.organizations.show', compact('organization', 'stats'));
    }

    /**
     * Show the form for editing the specified organization.
     */
    public function edit(Organization $organization)
    {
        $countries = Country::orderBy('name')->get();
        $categories = CategoryOfOrganization::orderBy('name')->get();
        $institutionTypes = InstitutionType::orderBy('name')->get();
        $cities = $organization->country_id ? City::where('country_id', $organization->country_id)->orderBy('name')->get() : collect();
        
        return view('admin.organizations.edit', compact('organization', 'countries', 'categories', 'institutionTypes', 'cities'));
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, Organization $organization)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:organizations,name,' . $organization->id,
            'description' => 'nullable|string|max:5000',
            'email' => 'nullable|string|email|max:100',
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'website' => 'nullable|string|url|max:255',
            'address' => 'nullable|string|max:500',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'category_id' => 'nullable|exists:category_of_organizations,id',
            'institution_type_id' => 'nullable|exists:institution_types,id',
            'status' => 'required|in:active,inactive,pending,suspended',
            'facebook_url' => 'nullable|string|url|max:255',
            'twitter_url' => 'nullable|string|url|max:255',
            'linkedin_url' => 'nullable|string|url|max:255',
            'instagram_url' => 'nullable|string|url|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'established_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'employee_count' => 'nullable|integer|min:0',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'website' => $request->website,
            'address' => $request->address,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'category_id' => $request->category_id,
            'institution_type_id' => $request->institution_type_id,
            'status' => $request->status,
            'facebook_url' => $request->facebook_url,
            'twitter_url' => $request->twitter_url,
            'linkedin_url' => $request->linkedin_url,
            'instagram_url' => $request->instagram_url,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'established_year' => $request->established_year,
            'employee_count' => $request->employee_count,
            'modified' => now(),
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($organization->logo) {
                Storage::disk('public')->delete('logos/' . $organization->logo);
            }
            
            $logoPath = $request->file('logo')->store('logos', 'public');
            $updateData['logo'] = basename($logoPath);
        }

        $organization->update($updateData);

        return redirect()->route('admin.organizations.show', $organization)
                        ->with('success', 'Organization updated successfully.');
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(Organization $organization)
    {
        // Delete logo if exists
        if ($organization->logo) {
            Storage::disk('public')->delete('logos/' . $organization->logo);
        }

        $organization->delete();

        return redirect()->route('admin.organizations.index')
                        ->with('success', 'Organization deleted successfully.');
    }

    /**
     * Toggle organization status.
     */
    public function toggleStatus(Organization $organization)
    {
        $newStatus = $organization->status === 'active' ? 'inactive' : 'active';
        
        $organization->update([
            'status' => $newStatus,
            'modified' => now(),
        ]);

        return back()->with('success', "Organization status changed to {$newStatus}.");
    }

    /**
     * Export organizations to CSV.
     */
    public function exportCsv(Request $request)
    {
        $query = Organization::with(['country', 'city', 'categoryOfOrganization', 'institutionType']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $organizations = $query->get();

        $filename = 'organizations_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($organizations) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Name', 'Email', 'Phone', 'Website', 'Address',
                'Country', 'City', 'Category', 'Institution Type', 'Status',
                'Established Year', 'Employee Count', 'Created', 'Modified'
            ]);

            // CSV data
            foreach ($organizations as $org) {
                fputcsv($file, [
                    $org->id,
                    $org->name,
                    $org->email,
                    $org->phone_number,
                    $org->website,
                    $org->address,
                    $org->country ? $org->country->name : '',
                    $org->city ? $org->city->name : '',
                    $org->categoryOfOrganization ? $org->categoryOfOrganization->name : '',
                    $org->institutionType ? $org->institutionType->name : '',
                    $org->status,
                    $org->established_year,
                    $org->employee_count,
                    $org->created->format('Y-m-d H:i:s'),
                    $org->modified->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Search organizations via AJAX.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $organizations = Organization::where('name', 'like', "%{$query}%")
                                   ->orWhere('email', 'like', "%{$query}%")
                                   ->limit(10)
                                   ->get(['id', 'name', 'email']);

        return response()->json($organizations);
    }
}