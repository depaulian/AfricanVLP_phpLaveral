<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CountryController extends Controller
{
    /**
     * Display a listing of countries.
     */
    public function index()
    {
        $countries = Country::with(['region'])
            ->withCount(['cities', 'organizations', 'users'])
            ->orderBy('name');

        // Apply filters
        if ($regionId = request('region_id')) {
            $countries->where('region_id', $regionId);
        }

        if ($status = request('status')) {
            $countries->where('status', $status);
        }

        if ($search = request('s')) {
            $countries->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%")
                      ->orWhere('iso_code', 'LIKE', "%{$search}%");
            });
        }

        $countries = $countries->paginate(15);
        $regions = Region::active()->orderBy('name')->get();

        return view('admin.countries.index', compact('countries', 'regions'));
    }

    /**
     * Show the form for creating a new country.
     */
    public function create()
    {
        $regions = Region::active()->orderBy('name')->get();
        
        return view('admin.countries.create', compact('regions'));
    }

    /**
     * Store a newly created country.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:3|unique:countries,code',
            'iso_code' => 'required|string|max:2|unique:countries,iso_code',
            'region_id' => 'nullable|exists:regions,id',
            'flag_url' => 'nullable|url|max:255',
            'currency_code' => 'nullable|string|max:3',
            'phone_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            $country = Country::create($request->all());

            DB::commit();

            // Clear cache
            Cache::tags(['countries', 'geographic'])->flush();

            Log::info('Country created', [
                'country_id' => $country->id,
                'name' => $country->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.countries.index')
                ->with('success', 'Country created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create country', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create country. Please try again.');
        }
    }

    /**
     * Display the specified country.
     */
    public function show(Country $country)
    {
        $country->load(['region', 'cities', 'organizations', 'users']);
        
        return view('admin.countries.show', compact('country'));
    }

    /**
     * Show the form for editing the specified country.
     */
    public function edit(Country $country)
    {
        $regions = Region::active()->orderBy('name')->get();
        
        return view('admin.countries.edit', compact('country', 'regions'));
    }

    /**
     * Update the specified country.
     */
    public function update(Request $request, Country $country)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:3|unique:countries,code,' . $country->id,
            'iso_code' => 'required|string|max:2|unique:countries,iso_code,' . $country->id,
            'region_id' => 'nullable|exists:regions,id',
            'flag_url' => 'nullable|url|max:255',
            'currency_code' => 'nullable|string|max:3',
            'phone_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            $country->update($request->all());

            DB::commit();

            // Clear cache
            Cache::tags(['countries', 'geographic'])->flush();

            Log::info('Country updated', [
                'country_id' => $country->id,
                'name' => $country->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.countries.index')
                ->with('success', 'Country updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update country', [
                'country_id' => $country->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update country. Please try again.');
        }
    }

    /**
     * Remove the specified country.
     */
    public function destroy(Country $country)
    {
        try {
            DB::beginTransaction();

            // Check if country has dependencies
            if ($country->cities()->count() > 0) {
                return back()->with('error', 'Cannot delete country with existing cities.');
            }

            if ($country->organizations()->count() > 0) {
                return back()->with('error', 'Cannot delete country with existing organizations.');
            }

            if ($country->users()->count() > 0) {
                return back()->with('error', 'Cannot delete country with existing users.');
            }

            $country->delete();

            DB::commit();

            // Clear cache
            Cache::tags(['countries', 'geographic'])->flush();

            Log::info('Country deleted', [
                'country_id' => $country->id,
                'name' => $country->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.countries.index')
                ->with('success', 'Country deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete country', [
                'country_id' => $country->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->with('error', 'Failed to delete country. Please try again.');
        }
    }

    /**
     * Toggle country status.
     */
    public function toggleStatus(Country $country)
    {
        try {
            $newStatus = $country->status === 'active' ? 'inactive' : 'active';
            
            $country->update(['status' => $newStatus]);

            // Clear cache
            Cache::tags(['countries', 'geographic'])->flush();

            Log::info('Country status toggled', [
                'country_id' => $country->id,
                'new_status' => $newStatus,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => "Country {$newStatus} successfully."
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle country status', [
                'country_id' => $country->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update country status.'
            ], 500);
        }
    }

    /**
     * Get cities for a country (AJAX).
     */
    public function getCities(Country $country)
    {
        $cities = $country->activeCities()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'cities' => $cities
        ]);
    }

    /**
     * Export countries to CSV.
     */
    public function exportCsv()
    {
        $countries = Country::with('region')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="countries.csv"',
        ];

        $callback = function () use ($countries) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Name', 'Code', 'ISO Code', 'Region', 'Currency', 
                'Phone Code', 'Status', 'Cities Count', 'Created At'
            ]);

            // CSV data
            foreach ($countries as $country) {
                fputcsv($file, [
                    $country->id,
                    $country->name,
                    $country->code,
                    $country->iso_code,
                    $country->region->name ?? 'N/A',
                    $country->currency_code,
                    $country->phone_code,
                    $country->status,
                    $country->cities_count,
                    $country->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}