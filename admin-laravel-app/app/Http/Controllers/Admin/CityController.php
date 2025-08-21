<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CityController extends Controller
{
    /**
     * Display a listing of cities.
     */
    public function index()
    {
        $cities = City::with(['country.region'])
            ->withCount(['organizations', 'users', 'events'])
            ->orderBy('name');

        // Apply filters
        if ($countryId = request('country_id')) {
            $cities->where('country_id', $countryId);
        }

        if ($status = request('status')) {
            $cities->where('status', $status);
        }

        if ($search = request('s')) {
            $cities->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('state_province', 'LIKE', "%{$search}%");
            });
        }

        $cities = $cities->paginate(15);
        $countries = Country::active()->orderBy('name')->get();

        return view('admin.cities.index', compact('cities', 'countries'));
    }

    /**
     * Show the form for creating a new city.
     */
    public function create()
    {
        $countries = Country::active()->orderBy('name')->get();
        
        return view('admin.cities.create', compact('countries'));
    }

    /**
     * Store a newly created city.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_province' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'population' => 'nullable|integer|min:0',
            'timezone' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            $city = City::create($request->all());

            DB::commit();

            // Clear cache
            Cache::tags(['cities', 'geographic'])->flush();

            Log::info('City created', [
                'city_id' => $city->id,
                'name' => $city->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.cities.index')
                ->with('success', 'City created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create city', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create city. Please try again.');
        }
    }

    /**
     * Display the specified city.
     */
    public function show(City $city)
    {
        $city->load(['country.region', 'organizations', 'users', 'events', 'organizationOffices']);
        
        return view('admin.cities.show', compact('city'));
    }

    /**
     * Show the form for editing the specified city.
     */
    public function edit(City $city)
    {
        $countries = Country::active()->orderBy('name')->get();
        
        return view('admin.cities.edit', compact('city', 'countries'));
    }

    /**
     * Update the specified city.
     */
    public function update(Request $request, City $city)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_province' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'population' => 'nullable|integer|min:0',
            'timezone' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            $city->update($request->all());

            DB::commit();

            // Clear cache
            Cache::tags(['cities', 'geographic'])->flush();

            Log::info('City updated', [
                'city_id' => $city->id,
                'name' => $city->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.cities.index')
                ->with('success', 'City updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update city', [
                'city_id' => $city->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update city. Please try again.');
        }
    }

    /**
     * Remove the specified city.
     */
    public function destroy(City $city)
    {
        try {
            DB::beginTransaction();

            // Check if city has dependencies
            if ($city->organizations()->count() > 0) {
                return back()->with('error', 'Cannot delete city with existing organizations.');
            }

            if ($city->users()->count() > 0) {
                return back()->with('error', 'Cannot delete city with existing users.');
            }

            if ($city->events()->count() > 0) {
                return back()->with('error', 'Cannot delete city with existing events.');
            }

            $city->delete();

            DB::commit();

            // Clear cache
            Cache::tags(['cities', 'geographic'])->flush();

            Log::info('City deleted', [
                'city_id' => $city->id,
                'name' => $city->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.cities.index')
                ->with('success', 'City deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete city', [
                'city_id' => $city->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->with('error', 'Failed to delete city. Please try again.');
        }
    }

    /**
     * Toggle city status.
     */
    public function toggleStatus(City $city)
    {
        try {
            $newStatus = $city->status === 'active' ? 'inactive' : 'active';
            
            $city->update(['status' => $newStatus]);

            // Clear cache
            Cache::tags(['cities', 'geographic'])->flush();

            Log::info('City status toggled', [
                'city_id' => $city->id,
                'new_status' => $newStatus,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => "City {$newStatus} successfully."
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle city status', [
                'city_id' => $city->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update city status.'
            ], 500);
        }
    }

    /**
     * Export cities to CSV.
     */
    public function exportCsv()
    {
        $cities = City::with('country')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cities.csv"',
        ];

        $callback = function () use ($cities) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Name', 'Country', 'State/Province', 'Population', 
                'Timezone', 'Status', 'Organizations Count', 'Created At'
            ]);

            // CSV data
            foreach ($cities as $city) {
                fputcsv($file, [
                    $city->id,
                    $city->name,
                    $city->country->name,
                    $city->state_province,
                    $city->population,
                    $city->timezone,
                    $city->status,
                    $city->organizations_count,
                    $city->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}