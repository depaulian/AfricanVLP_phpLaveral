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
     * Get geographic coordinates for location
     *
     * @param string $address
     * @return array|null
     */
    public function getCoordinates(string $address): ?array
    {
        $apiKey = config('services.maps.google_maps_api_key');
        
        if (!$apiKey) {
            Log::warning('Google Maps API key not configured');
            return null;
        }

        $cacheKey = 'coordinates_' . md5($address);
        
        return Cache::remember($cacheKey, 86400, function () use ($address, $apiKey) {
            try {
                $url = "https://maps.googleapis.com/maps/api/geocode/json?" . http_build_query([
                    'address' => $address,
                    'key' => $apiKey
                ]);

                $response = file_get_contents($url);
                $data = json_decode($response, true);

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $location = $data['results'][0]['geometry']['location'];
                    
                    return [
                        'latitude' => $location['lat'],
                        'longitude' => $location['lng'],
                        'formatted_address' => $data['results'][0]['formatted_address']
                    ];
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Failed to get coordinates: ' . $e->getMessage(), [
                    'address' => $address
                ]);
                return null;
            }
        });
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

    /**
     * Update organization coordinates
     *
     * @param Organization $organization
     * @return bool
     */
    public function updateOrganizationCoordinates(Organization $organization): bool
    {
        $address = $this->buildAddress($organization);
        
        if (!$address) {
            return false;
        }

        $coordinates = $this->getCoordinates($address);
        
        if ($coordinates) {
            $organization->update([
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
                'formatted_address' => $coordinates['formatted_address']
            ]);
            
            return true;
        }

        return false;
    }

    /**
     * Update event coordinates
     *
     * @param Event $event
     * @return bool
     */
    public function updateEventCoordinates(Event $event): bool
    {
        $address = $this->buildAddress($event);
        
        if (!$address) {
            return false;
        }

        $coordinates = $this->getCoordinates($address);
        
        if ($coordinates) {
            $event->update([
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
                'formatted_address' => $coordinates['formatted_address']
            ]);
            
            return true;
        }

        return false;
    }

    /**
     * Build address string from model
     *
     * @param mixed $model
     * @return string|null
     */
    protected function buildAddress($model): ?string
    {
        $parts = [];

        if ($model->address) {
            $parts[] = $model->address;
        }

        if ($model->city) {
            $parts[] = $model->city->name;
        }

        if ($model->country) {
            $parts[] = $model->country->name;
        }

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Get geographic statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        return Cache::remember('geographic_stats', 3600, function () {
            return [
                'total_regions' => Region::count(),
                'total_countries' => Country::count(),
                'total_cities' => City::count(),
                'organizations_with_coordinates' => Organization::whereNotNull('latitude')->whereNotNull('longitude')->count(),
                'events_with_coordinates' => Event::whereNotNull('latitude')->whereNotNull('longitude')->count(),
                'organizations_by_region' => Region::withCount('organizations')->get()->pluck('organizations_count', 'name'),
                'events_by_region' => Region::withCount('events')->get()->pluck('events_count', 'name')
            ];
        });
    }

    /**
     * Clear geographic cache
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        try {
            $patterns = [
                'regions_with_countries',
                'countries_region_*',
                'cities_country_*',
                'coordinates_*',
                'geographic_stats'
            ];
            
            foreach ($patterns as $pattern) {
                if (str_contains($pattern, '*')) {
                    $keys = Cache::getRedis()->keys($pattern);
                    if (!empty($keys)) {
                        Cache::getRedis()->del($keys);
                    }
                } else {
                    Cache::forget($pattern);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear geographic cache: ' . $e->getMessage());
            return false;
        }
    }
}