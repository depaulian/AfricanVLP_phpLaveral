<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Region;
use App\Models\City;
use App\Models\Organization;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class GeographicService
{
    /**
     * Get all regions with countries
     *
     * @return Collection
     */
    public function getRegionsWithCountries(): Collection
    {
        return Cache::remember('regions_with_countries', 3600, function () {
            return Region::with(['countries' => function($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get();
        });
    }

    /**
     * Get countries by region
     *
     * @param int $regionId
     * @return Collection
     */
    public function getCountriesByRegion(int $regionId): Collection
    {
        return Cache::remember("countries_region_{$regionId}", 3600, function () use ($regionId) {
            return Country::where('region_id', $regionId)
                         ->orderBy('name')
                         ->get();
        });
    }

    /**
     * Get cities by country
     *
     * @param int $countryId
     * @return Collection
     */
    public function getCitiesByCountry(int $countryId): Collection
    {
        return Cache::remember("cities_country_{$countryId}", 3600, function () use ($countryId) {
            return City::where('country_id', $countryId)
                      ->orderBy('name')
                      ->get();
        });
    }

    /**
     * Search locations by name
     *
     * @param string $query
     * @param string|null $type
     * @return array
     */
    public function searchLocations(string $query, ?string $type = null): array
    {
        $results = [];

        if (!$type || $type === 'regions') {
            $regions = Region::where('name', 'like', "%{$query}%")
                           ->limit(10)
                           ->get()
                           ->map(function($region) {
                               return [
                                   'id' => $region->id,
                                   'name' => $region->name,
                                   'type' => 'region',
                                   'full_name' => $region->name
                               ];
                           });
            $results = array_merge($results, $regions->toArray());
        }

        if (!$type || $type === 'countries') {
            $countries = Country::with('region')
                              ->where('name', 'like', "%{$query}%")
                              ->limit(10)
                              ->get()
                              ->map(function($country) {
                                  return [
                                      'id' => $country->id,
                                      'name' => $country->name,
                                      'type' => 'country',
                                      'full_name' => $country->name . ', ' . $country->region->name,
                                      'iso_code' => $country->iso_code,
                                      'region_id' => $country->region_id
                                  ];
                              });
            $results = array_merge($results, $countries->toArray());
        }

        if (!$type || $type === 'cities') {
            $cities = City::with(['country.region'])
                         ->where('name', 'like', "%{$query}%")
                         ->limit(10)
                         ->get()
                         ->map(function($city) {
                             return [
                                 'id' => $city->id,
                                 'name' => $city->name,
                                 'type' => 'city',
                                 'full_name' => $city->name . ', ' . $city->country->name . ', ' . $city->country->region->name,
                                 'country_id' => $city->country_id,
                                 'region_id' => $city->country->region_id
                             ];
                         });
            $results = array_merge($results, $cities->toArray());
        }

        return $results;
    }

    /**
     * Calculate distance between two points
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @param string $unit
     * @return float
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = 'km'): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        if ($unit === 'km') {
            return $miles * 1.609344;
        } elseif ($unit === 'm') {
            return $miles * 1609.344;
        } else {
            return $miles;
        }
    }

    /**
     * Find nearby organizations
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @param string $unit
     * @return Collection
     */
    public function findNearbyOrganizations(float $latitude, float $longitude, int $radius = 50, string $unit = 'km'): Collection
    {
        return Organization::whereNotNull('latitude')
                          ->whereNotNull('longitude')
                          ->get()
                          ->filter(function($organization) use ($latitude, $longitude, $radius, $unit) {
                              $distance = $this->calculateDistance(
                                  $latitude, 
                                  $longitude, 
                                  $organization->latitude, 
                                  $organization->longitude, 
                                  $unit
                              );
                              
                              $organization->distance = round($distance, 2);
                              return $distance <= $radius;
                          })
                          ->sortBy('distance')
                          ->values();
    }

    /**
     * Find nearby events
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @param string $unit
     * @return Collection
     */
    public function findNearbyEvents(float $latitude, float $longitude, int $radius = 50, string $unit = 'km'): Collection
    {
        return Event::whereNotNull('latitude')
                   ->whereNotNull('longitude')
                   ->where('start_date', '>=', now())
                   ->get()
                   ->filter(function($event) use ($latitude, $longitude, $radius, $unit) {
                       $distance = $this->calculateDistance(
                           $latitude, 
                           $longitude, 
                           $event->latitude, 
                           $event->longitude, 
                           $unit
                       );
                       
                       $event->distance = round($distance, 2);
                       return $distance <= $radius;
                   })
                   ->sortBy('distance')
                   ->values();
    }

    /**
     * Get organizations by location
     *
     * @param int|null $regionId
     * @param int|null $countryId
     * @param int|null $cityId
     * @return Collection
     */
    public function getOrganizationsByLocation(?int $regionId = null, ?int $countryId = null, ?int $cityId = null): Collection
    {
        $query = Organization::with(['country.region', 'city']);

        if ($cityId) {
            $query->where('city_id', $cityId);
        } elseif ($countryId) {
            $query->where('country_id', $countryId);
        } elseif ($regionId) {
            $query->whereHas('country', function($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get events by location
     *
     * @param int|null $regionId
     * @param int|null $countryId
     * @param int|null $cityId
     * @return Collection
     */
    public function getEventsByLocation(?int $regionId = null, ?int $countryId = null, ?int $cityId = null): Collection
    {
        $query = Event::with(['country.region', 'city', 'organization']);

        if ($cityId) {
            $query->where('city_id', $cityId);
        } elseif ($countryId) {
            $query->where('country_id', $countryId);
        } elseif ($regionId) {
            $query->whereHas('country', function($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }

        return $query->where('start_date', '>=', now())
                    ->orderBy('start_date')
                    ->get();
    }
}