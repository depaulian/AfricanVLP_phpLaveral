<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VolunteerApplicationRequest extends FormRequest
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
            'cover_letter' => [
                'required',
                'string',
                'min:100',
                'max:2000'
            ],
            'availability' => [
                'required',
                'string',
                'max:1000'
            ],
            'experience' => [
                'nullable',
                'string',
                'max:2000'
            ],
            'skills' => [
                'nullable',
                'array'
            ],
            'skills.*' => [
                'string',
                'max:100'
            ],
            'terms' => [
                'required',
                'accepted'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cover_letter.required' => 'Please provide a cover letter explaining your interest.',
            'cover_letter.min' => 'Your cover letter should be at least 100 characters long.',
            'cover_letter.max' => 'Your cover letter should not exceed 2000 characters.',
            'availability.required' => 'Please describe your availability.',
            'availability.max' => 'Your availability description should not exceed 1000 characters.',
            'experience.max' => 'Your experience description should not exceed 2000 characters.',
            'skills.*.max' => 'Each skill should not exceed 100 characters.',
            'terms.required' => 'You must accept the terms and conditions to apply.',
            'terms.accepted' => 'You must accept the terms and conditions to apply.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cover_letter' => 'cover letter',
            'availability' => 'availability',
            'experience' => 'relevant experience',
            'skills' => 'skills',
            'terms' => 'terms and conditions'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Parse skills JSON if provided
        if ($this->has('skills') && is_string($this->skills)) {
            $skills = json_decode($this->skills, true);
            $this->merge([
                'skills' => is_array($skills) ? $skills : []
            ]);
        }

        // Convert terms to boolean
        if ($this->has('terms')) {
            $this->merge([
                'terms' => $this->boolean('terms')
            ]);
        }
    }
}