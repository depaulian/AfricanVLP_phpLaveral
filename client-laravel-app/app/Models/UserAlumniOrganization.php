<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserAlumniOrganization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'organization_name',
        'degree',
        'field_of_study',
        'graduation_year',
        'status',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'graduation_year' => 'integer',
    ];

    /**
     * Alumni status enum values.
     */
    const STATUSES = [
        'student' => 'Student',
        'graduate' => 'Graduate',
        'faculty' => 'Faculty',
        'staff' => 'Staff',
    ];

    /**
     * Get the user that owns the alumni organization record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization associated with this alumni record.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who verified this alumni record.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Verify the alumni organization record.
     */
    public function verify(User $verifier): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $verifier->id,
            'verified_at' => now()
        ]);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the education summary.
     */
    public function getEducationSummaryAttribute(): string
    {
        $parts = array_filter([
            $this->degree,
            $this->field_of_study,
            $this->graduation_year
        ]);
        
        return implode(' - ', $parts);
    }

    /**
     * Get the organization name (either from relationship or stored name).
     */
    public function getOrganizationNameAttribute(): string
    {
        return $this->organization?->name ?? $this->attributes['organization_name'] ?? 'Unknown Organization';
    }

    /**
     * Get the full education description.
     */
    public function getFullEducationDescriptionAttribute(): string
    {
        $parts = [];
        
        if ($this->degree) {
            $parts[] = $this->degree;
        }
        
        if ($this->field_of_study) {
            $parts[] = 'in ' . $this->field_of_study;
        }
        
        if ($this->graduation_year) {
            $parts[] = '(' . $this->graduation_year . ')';
        }
        
        $description = implode(' ', $parts);
        
        if ($description) {
            return $description . ' from ' . $this->organization_name;
        }
        
        return $this->organization_name;
    }

    /**
     * Scope to get verified alumni records.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get unverified alumni records.
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope to get graduates.
     */
    public function scopeGraduates(Builder $query): Builder
    {
        return $query->where('status', 'graduate');
    }

    /**
     * Scope to get current students.
     */
    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('status', 'student');
    }

    /**
     * Scope to get faculty members.
     */
    public function scopeFaculty(Builder $query): Builder
    {
        return $query->where('status', 'faculty');
    }

    /**
     * Scope to get staff members.
     */
    public function scopeStaff(Builder $query): Builder
    {
        return $query->where('status', 'staff');
    }

    /**
     * Scope to filter by graduation year.
     */
    public function scopeGraduatedIn(Builder $query, int $year): Builder
    {
        return $query->where('graduation_year', $year);
    }

    /**
     * Scope to order by graduation year (most recent first).
     */
    public function scopeOrderByGraduationYear(Builder $query): Builder
    {
        return $query->orderByDesc('graduation_year');
    }

    /**
     * Check if this is a current student.
     */
    public function isCurrentStudent(): bool
    {
        return $this->status === 'student';
    }

    /**
     * Check if this is a graduate.
     */
    public function isGraduate(): bool
    {
        return $this->status === 'graduate';
    }

    /**
     * Check if this person is faculty.
     */
    public function isFaculty(): bool
    {
        return $this->status === 'faculty';
    }

    /**
     * Check if this person is staff.
     */
    public function isStaff(): bool
    {
        return $this->status === 'staff';
    }
}