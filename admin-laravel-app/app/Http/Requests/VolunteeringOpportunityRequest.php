<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VolunteeringOpportunityRequest extends FormRequest
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
        $opportunityId = $this->route('opportunity')?->id;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('volunteering_opportunities')->ignore($opportunityId)
            ],
            'description' => [
                'required',
                'string',
                'min:100'
            ],
            'organization_id' => [
                'required',
                'exists:organizations,id'
            ],
            'category_id' => [
                'required',
                'exists:volunteering_categories,id'
            ],
            'role_id' => [
                'nullable',
                'exists:volunteering_roles,id'
            ],
            'location_type' => [
                'required',
                'in:onsite,remote,hybrid'
            ],
            'city_id' => [
                'nullable',
                'exists:cities,id',
                'required_if:location_type,onsite,hybrid'
            ],
            'country_id' => [
                'nullable',
                'exists:countries,id',
                'required_if:location_type,onsite,hybrid'
            ],
            'address' => [
                'nullable',
                'string',
                'max:500',
                'required_if:location_type,onsite'
            ],
            'volunteers_needed' => [
                'required',
                'integer',
                'min:1',
                'max:1000'
            ],
            'experience_level' => [
                'required',
                'in:beginner,intermediate,advanced,expert,any'
            ],
            'time_commitment' => [
                'required',
                'string',
                'max:255'
            ],
            'start_date' => [
                'required',
                'date',
                $this->isMethod('POST') ? 'after_or_equal:today' : 'date'
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:start_date'
            ],
            'application_deadline' => [
                'nullable',
                'date',
                'before_or_equal:start_date'
            ],
            'required_skills' => [
                'nullable',
                'array',
                'max:10'
            ],
            'required_skills.*' => [
                'string',
                'max:100'
            ],
            'benefits' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'requirements' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'contact_email' => [
                'nullable',
                'email',
                'max:255'
            ],
            'contact_phone' => [
                'nullable',
                'string',
                'max:20'
            ],
            'featured' => [
                'boolean'
            ],
            'status' => [
                'required',
                'in:draft,active,paused,closed'
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:2048'
            ],
            'documents' => [
                'nullable',
                'array',
                'max:5'
            ],
            'documents.*' => [
                'file',
                'mimes:pdf,doc,docx',
                'max:5120'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please enter an opportunity title.',
            'title.unique' => 'An opportunity with this title already exists.',
            'description.required' => 'Please provide a detailed description.',
            'description.min' => 'Description should be at least 100 characters long.',
            'organization_id.required' => 'Please select an organization.',
            'organization_id.exists' => 'Selected organization is invalid.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category is invalid.',
            'location_type.required' => 'Please select a location type.',
            'city_id.required_if' => 'City is required for onsite and hybrid opportunities.',
            'country_id.required_if' => 'Country is required for onsite and hybrid opportunities.',
            'address.required_if' => 'Address is required for onsite opportunities.',
            'volunteers_needed.required' => 'Please specify how many volunteers are needed.',
            'volunteers_needed.min' => 'At least 1 volunteer is required.',
            'volunteers_needed.max' => 'Maximum 1000 volunteers allowed.',
            'start_date.required' => 'Please select a start date.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
            'end_date.after' => 'End date must be after start date.',
            'application_deadline.before_or_equal' => 'Application deadline must be before or on start date.',
            'required_skills.max' => 'Maximum 10 skills allowed.',
            'contact_email.email' => 'Please enter a valid email address.',
            'image.image' => 'Please upload a valid image file.',
            'image.mimes' => 'Image must be jpeg, png, jpg, or gif format.',
            'image.max' => 'Image size cannot exceed 2MB.',
            'documents.max' => 'Maximum 5 documents allowed.',
            'documents.*.mimes' => 'Documents must be PDF, DOC, or DOCX format.',
            'documents.*.max' => 'Document size cannot exceed 5MB.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'opportunity title',
            'description' => 'description',
            'organization_id' => 'organization',
            'category_id' => 'category',
            'role_id' => 'role',
            'location_type' => 'location type',
            'city_id' => 'city',
            'country_id' => 'country',
            'address' => 'address',
            'volunteers_needed' => 'volunteers needed',
            'experience_level' => 'experience level',
            'time_commitment' => 'time commitment',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'application_deadline' => 'application deadline',
            'required_skills' => 'required skills',
            'benefits' => 'benefits',
            'requirements' => 'requirements',
            'contact_email' => 'contact email',
            'contact_phone' => 'contact phone',
            'featured' => 'featured status',
            'status' => 'status',
            'image' => 'opportunity image',
            'documents' => 'documents'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert featured to boolean
        if ($this->has('featured')) {
            $this->merge([
                'featured' => $this->boolean('featured')
            ]);
        }

        // Clean up required_skills array
        if ($this->has('required_skills') && is_array($this->required_skills)) {
            $skills = array_filter($this->required_skills, function ($skill) {
                return !empty(trim($skill));
            });
            $this->merge([
                'required_skills' => array_values($skills)
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate location requirements
            if ($this->location_type === 'remote') {
                // Remote opportunities shouldn't have city/country/address
                if ($this->city_id || $this->country_id || $this->address) {
                    $validator->errors()->add('location_type', 'Remote opportunities should not have location details.');
                }
            }

            // Validate application deadline
            if ($this->application_deadline && $this->start_date) {
                $deadline = \Carbon\Carbon::parse($this->application_deadline);
                $startDate = \Carbon\Carbon::parse($this->start_date);
                
                if ($deadline->gte($startDate)) {
                    $validator->errors()->add('application_deadline', 'Application deadline must be before start date.');
                }
            }
        });
    }
}