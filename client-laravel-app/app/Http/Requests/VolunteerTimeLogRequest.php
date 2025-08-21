<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VolunteerTimeLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
                'before_or_equal:today',
                'after_or_equal:' . now()->subMonths(3)->format('Y-m-d') // Can't log hours older than 3 months
            ],
            'start_time' => [
                'required',
                'date_format:H:i'
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time'
            ],
            'activity_description' => [
                'required',
                'string',
                'min:10',
                'max:500'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'location' => [
                'nullable',
                'string',
                'max:255'
            ],
            'break_duration' => [
                'nullable',
                'integer',
                'min:0',
                'max:480' // Max 8 hours break
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Please select the date you volunteered.',
            'date.before_or_equal' => 'You cannot log hours for future dates.',
            'date.after_or_equal' => 'You cannot log hours older than 3 months.',
            'start_time.required' => 'Please enter the start time.',
            'start_time.date_format' => 'Please enter a valid start time (HH:MM format).',
            'end_time.required' => 'Please enter the end time.',
            'end_time.date_format' => 'Please enter a valid end time (HH:MM format).',
            'end_time.after' => 'End time must be after start time.',
            'activity_description.required' => 'Please describe what activities you performed.',
            'activity_description.min' => 'Activity description should be at least 10 characters long.',
            'activity_description.max' => 'Activity description should not exceed 500 characters.',
            'notes.max' => 'Notes should not exceed 1000 characters.',
            'location.max' => 'Location should not exceed 255 characters.',
            'break_duration.integer' => 'Break duration must be a number.',
            'break_duration.min' => 'Break duration cannot be negative.',
            'break_duration.max' => 'Break duration cannot exceed 8 hours.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'date' => 'volunteer date',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'activity_description' => 'activity description',
            'notes' => 'additional notes',
            'location' => 'location',
            'break_duration' => 'break duration'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate total hours don't exceed 16 hours per day
            if ($this->start_time && $this->end_time) {
                $start = \Carbon\Carbon::createFromFormat('H:i', $this->start_time);
                $end = \Carbon\Carbon::createFromFormat('H:i', $this->end_time);
                
                // Handle overnight shifts
                if ($end->lt($start)) {
                    $end->addDay();
                }
                
                $totalMinutes = $end->diffInMinutes($start);
                $breakMinutes = $this->break_duration ?? 0;
                $workMinutes = $totalMinutes - $breakMinutes;
                
                if ($workMinutes > 960) { // 16 hours
                    $validator->errors()->add('end_time', 'Total work hours cannot exceed 16 hours per day.');
                }
                
                if ($workMinutes < 15) { // Minimum 15 minutes
                    $validator->errors()->add('end_time', 'Minimum volunteer time is 15 minutes.');
                }
            }
        });
    }
}