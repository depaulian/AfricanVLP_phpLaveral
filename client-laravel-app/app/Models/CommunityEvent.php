<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class CommunityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'organizer_id',
        'organization_id',
        'start_datetime',
        'end_datetime',
        'timezone',
        'format',
        'venue_name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'meeting_url',
        'meeting_id',
        'meeting_password',
        'virtual_instructions',
        'max_attendees',
        'registration_fee',
        'is_free',
        'registration_deadline',
        'requires_approval',
        'target_audience',
        'tags',
        'requirements',
        'what_to_bring',
        'agenda',
        'status',
        'visibility',
        'is_featured',
        'featured_image',
        'gallery_images',
        'registered_count',
        'attended_count',
        'waitlist_count',
        'post_event_notes',
        'feedback_summary',
        'average_rating',
        'feedback_count',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'registration_deadline' => 'datetime',
        'target_audience' => 'array',
        'tags' => 'array',
        'gallery_images' => 'array',
        'feedback_summary' => 'array',
        'registration_fee' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'average_rating' => 'decimal:1',
        'is_free' => 'boolean',
        'requires_approval' => 'boolean',
        'is_featured' => 'boolean',
        'registered_count' => 'integer',
        'attended_count' => 'integer',
        'waitlist_count' => 'integer',
        'feedback_count' => 'integer',
        'max_attendees' => 'integer',
    ];

    /**
     * Get the organizer user
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get event registrations
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'event_id');
    }

    /**
     * Scope for published events
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for upcoming events
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>', now());
    }

    /**
     * Scope for past events
     */
    public function scopePast($query)
    {
        return $query->where('end_datetime', '<', now());
    }

    /**
     * Scope for featured events
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for public events
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope for events by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for events in a specific location
     */
    public function scopeInLocation($query, $city = null, $country = null)
    {
        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }
        
        if ($country) {
            $query->where('country', 'like', "%{$country}%");
        }
        
        return $query;
    }

    /**
     * Scope for events within date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_datetime', [$startDate, $endDate]);
    }

    /**
     * Check if event is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->start_datetime->isFuture();
    }

    /**
     * Check if event is ongoing
     */
    public function isOngoing(): bool
    {
        return $this->start_datetime->isPast() && $this->end_datetime->isFuture();
    }

    /**
     * Check if event is past
     */
    public function isPast(): bool
    {
        return $this->end_datetime->isPast();
    }

    /**
     * Check if registration is open
     */
    public function isRegistrationOpen(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }
        
        if ($this->registration_deadline && $this->registration_deadline->isPast()) {
            return false;
        }
        
        if ($this->max_attendees && $this->registered_count >= $this->max_attendees) {
            return false;
        }
        
        return $this->isUpcoming();
    }

    /**
     * Check if event is full
     */
    public function isFull(): bool
    {
        return $this->max_attendees && $this->registered_count >= $this->max_attendees;
    }

    /**
     * Check if user can register
     */
    public function canUserRegister(User $user): bool
    {
        if (!$this->isRegistrationOpen()) {
            return false;
        }
        
        // Check if already registered
        if ($this->registrations()->where('user_id', $user->id)->exists()) {
            return false;
        }
        
        // Check visibility restrictions
        if ($this->visibility === 'members_only' && !$user->hasRole('volunteer')) {
            return false;
        }
        
        if ($this->visibility === 'organization_only' && 
            (!$this->organization_id || !$user->organizations()->where('id', $this->organization_id)->exists())) {
            return false;
        }
        
        return true;
    }

    /**
     * Register a user for the event
     */
    public function registerUser(User $user, array $data = []): EventRegistration
    {
        if (!$this->canUserRegister($user)) {
            throw new \InvalidArgumentException('User cannot register for this event.');
        }
        
        $status = 'registered';
        
        // Check if event is full - add to waitlist
        if ($this->isFull()) {
            $status = 'waitlisted';
            $this->increment('waitlist_count');
        } else {
            $this->increment('registered_count');
        }
        
        // If requires approval, set status accordingly
        if ($this->requires_approval && $status === 'registered') {
            $status = 'registered'; // Will need admin approval
        }
        
        return $this->registrations()->create([
            'user_id' => $user->id,
            'status' => $status,
            'registration_notes' => $data['notes'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? null,
            'payment_required' => !$this->is_free,
            'amount_paid' => $this->registration_fee,
            'registered_at' => now(),
        ]);
    }

    /**
     * Cancel user registration
     */
    public function cancelRegistration(User $user): bool
    {
        $registration = $this->registrations()->where('user_id', $user->id)->first();
        
        if (!$registration) {
            return false;
        }
        
        $registration->update(['status' => 'cancelled']);
        
        // Update counts
        if ($registration->status === 'registered') {
            $this->decrement('registered_count');
            
            // Move someone from waitlist if available
            $this->promoteFromWaitlist();
        } elseif ($registration->status === 'waitlisted') {
            $this->decrement('waitlist_count');
        }
        
        return true;
    }

    /**
     * Promote someone from waitlist
     */
    private function promoteFromWaitlist(): void
    {
        if ($this->isFull()) {
            return;
        }
        
        $waitlistedRegistration = $this->registrations()
            ->where('status', 'waitlisted')
            ->orderBy('registered_at')
            ->first();
        
        if ($waitlistedRegistration) {
            $waitlistedRegistration->update(['status' => 'registered']);
            $this->increment('registered_count');
            $this->decrement('waitlist_count');
            
            // Notify user they've been promoted
            // TODO: Send notification
        }
    }

    /**
     * Mark user as attended
     */
    public function markAttended(User $user): bool
    {
        $registration = $this->registrations()->where('user_id', $user->id)->first();
        
        if (!$registration || $registration->status !== 'registered') {
            return false;
        }
        
        $registration->update([
            'status' => 'attended',
            'attended_at' => now(),
        ]);
        
        $this->increment('attended_count');
        
        return true;
    }

    /**
     * Get event type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'meetup' => 'Meetup',
            'workshop' => 'Workshop',
            'training' => 'Training',
            'networking' => 'Networking',
            'social' => 'Social Event',
            'conference' => 'Conference',
            'webinar' => 'Webinar',
            'volunteer_fair' => 'Volunteer Fair',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get format display name
     */
    public function getFormatDisplayAttribute(): string
    {
        return match ($this->format) {
            'in_person' => 'In Person',
            'virtual' => 'Virtual',
            'hybrid' => 'Hybrid',
            default => ucfirst($this->format),
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'published' => 'green',
            'cancelled' => 'red',
            'completed' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get event duration in human readable format
     */
    public function getDurationDisplayAttribute(): string
    {
        $duration = $this->start_datetime->diffInMinutes($this->end_datetime);
        
        if ($duration < 60) {
            return $duration . ' minutes';
        } elseif ($duration < 1440) { // Less than 24 hours
            $hours = floor($duration / 60);
            $minutes = $duration % 60;
            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
        } else {
            $days = floor($duration / 1440);
            return $days . ' day' . ($days > 1 ? 's' : '');
        }
    }

    /**
     * Get available spots
     */
    public function getAvailableSpotsAttribute(): ?int
    {
        if (!$this->max_attendees) {
            return null;
        }
        
        return max(0, $this->max_attendees - $this->registered_count);
    }

    /**
     * Get attendance rate
     */
    public function getAttendanceRateAttribute(): float
    {
        if ($this->registered_count === 0) {
            return 0;
        }
        
        return ($this->attended_count / $this->registered_count) * 100;
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->venue_name,
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get event URL slug
     */
    public function getSlugAttribute(): string
    {
        return \Str::slug($this->title . '-' . $this->id);
    }

    /**
     * Check if event requires payment
     */
    public function requiresPayment(): bool
    {
        return !$this->is_free && $this->registration_fee > 0;
    }

    /**
     * Get similar events
     */
    public function getSimilarEvents(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return static::published()
            ->upcoming()
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('type', $this->type)
                      ->orWhere('city', $this->city)
                      ->orWhere('organization_id', $this->organization_id);
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get event statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_registered' => $this->registered_count,
            'total_attended' => $this->attended_count,
            'total_waitlisted' => $this->waitlist_count,
            'attendance_rate' => $this->attendance_rate,
            'available_spots' => $this->available_spots,
            'is_full' => $this->isFull(),
            'average_rating' => $this->average_rating,
            'feedback_count' => $this->feedback_count,
            'registration_status' => $this->isRegistrationOpen() ? 'open' : 'closed',
        ];
    }

    /**
     * Search events
     */
    public static function search(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = static::published()->public();
        
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhereJsonContains('tags', $keyword);
            });
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }
        
        if (!empty($filters['city'])) {
            $query->where('city', 'like', "%{$filters['city']}%");
        }
        
        if (!empty($filters['country'])) {
            $query->where('country', 'like', "%{$filters['country']}%");
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('start_datetime', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('start_datetime', '<=', $filters['date_to']);
        }
        
        if (!empty($filters['is_free'])) {
            $query->where('is_free', $filters['is_free']);
        }
        
        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        
        return $query->orderBy('start_datetime');
    }

    /**
     * Get event analytics
     */
    public static function getAnalytics(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        $totalEvents = $query->count();
        $publishedEvents = $query->where('status', 'published')->count();
        $completedEvents = $query->where('status', 'completed')->count();
        
        $totalRegistrations = EventRegistration::whereHas('event', function ($q) use ($filters) {
            if (isset($filters['date_from'])) {
                $q->where('created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $q->where('created_at', '<=', $filters['date_to']);
            }
        })->count();
        
        $totalAttendance = EventRegistration::where('status', 'attended')
            ->whereHas('event', function ($q) use ($filters) {
                if (isset($filters['date_from'])) {
                    $q->where('created_at', '>=', $filters['date_from']);
                }
                if (isset($filters['date_to'])) {
                    $q->where('created_at', '<=', $filters['date_to']);
                }
            })->count();
        
        $averageAttendanceRate = $totalRegistrations > 0 ? ($totalAttendance / $totalRegistrations) * 100 : 0;
        $averageRating = static::whereNotNull('average_rating')->avg('average_rating');
        
        return [
            'total_events' => $totalEvents,
            'published_events' => $publishedEvents,
            'completed_events' => $completedEvents,
            'total_registrations' => $totalRegistrations,
            'total_attendance' => $totalAttendance,
            'average_attendance_rate' => round($averageAttendanceRate, 2),
            'average_rating' => round($averageRating, 2),
            'events_by_type' => static::getEventsByType($filters),
            'events_by_format' => static::getEventsByFormat($filters),
            'monthly_trends' => static::getMonthlyTrends($filters),
            'top_locations' => static::getTopLocations($filters),
        ];
    }

    /**
     * Get events by type
     */
    private static function getEventsByType(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get events by format
     */
    private static function getEventsByFormat(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->selectRaw('format, COUNT(*) as count')
            ->groupBy('format')
            ->orderByDesc('count')
            ->pluck('count', 'format')
            ->toArray();
    }

    /**
     * Get monthly trends
     */
    private static function getMonthlyTrends(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        } else {
            $query->where('created_at', '>=', now()->subYear());
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as total_events,
                SUM(registered_count) as total_registrations,
                SUM(attended_count) as total_attendance,
                AVG(average_rating) as avg_rating
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get top locations
     */
    private static function getTopLocations(array $filters = [], int $limit = 10): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->selectRaw('city, country, COUNT(*) as event_count')
            ->whereNotNull('city')
            ->groupBy('city', 'country')
            ->orderByDesc('event_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}