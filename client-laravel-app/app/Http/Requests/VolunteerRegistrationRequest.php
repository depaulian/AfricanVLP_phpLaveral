<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class VolunteerRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Basic Information (Step 1)
            'first_name' => 'required|string|max:45',
            'last_name' => 'required|string|max:45',
            'email' => 'required|email:rfc,dns|max:100|unique:users,email',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'password_confirmation' => 'required|string|min:8',

            // Profile Details (Step 2)
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
            'gender' => 'nullable|in:male,female,other',
            'preferred_language' => 'nullable|in:' . implode(',', User::LANGUAGES),
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max

            // Interests & Preferences (Step 3)
            'volunteer_mode' => 'required|in:' . implode(',', User::VOLUNTEER_MODES),
            'time_commitment' => 'required|in:' . implode(',', User::TIME_COMMITMENTS),
            'volunteering_interests' => 'nullable|array|min:1',
            'volunteering_interests.*' => 'exists:volunteering_categories,id',
            'organization_interests' => 'nullable|array',
            'organization_interests.*' => 'exists:organization_categories,id',

            // Combined interests (for backward compatibility)
            'interests' => 'nullable|array',
            'interests.*' => 'integer',

            // Terms and conditions
            'terms_accepted' => 'required|accepted',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            // Basic Information
            'first_name.required' => 'First name is required.',
            'first_name.max' => 'First name must not exceed 45 characters.',
            'last_name.required' => 'Last name is required.',
            'last_name.max' => 'Last name must not exceed 45 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',

            // Profile Details
            'phone_number.regex' => 'Please enter a valid phone number.',
            'phone_number.max' => 'Phone number must not exceed 20 characters.',
            'address.max' => 'Address must not exceed 500 characters.',
            'city_id.exists' => 'Selected city is invalid.',
            'country_id.exists' => 'Selected country is invalid.',
            'date_of_birth.date' => 'Please enter a valid date of birth.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'date_of_birth.after' => 'Please enter a valid date of birth.',
            'gender.in' => 'Please select a valid gender option.',
            'preferred_language.in' => 'Please select a valid language option.',
            'cv.file' => 'CV must be a valid file.',
            'cv.mimes' => 'CV must be a PDF, DOC, or DOCX file.',
            'cv.max' => 'CV file size must not exceed 5MB.',

            // Interests & Preferences
            'volunteer_mode.required' => 'Please select your preferred volunteer mode.',
            'volunteer_mode.in' => 'Please select a valid volunteer mode.',
            'time_commitment.required' => 'Please select your time commitment preference.',
            'time_commitment.in' => 'Please select a valid time commitment option.',
            'volunteering_interests.min' => 'Please select at least one area of interest.',
            'volunteering_interests.*.exists' => 'One or more selected volunteering categories are invalid.',
            'organization_interests.*.exists' => 'One or more selected organization categories are invalid.',

            // Terms
            'terms_accepted.required' => 'You must accept the terms and conditions to proceed.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions to proceed.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'password_confirmation' => 'password confirmation',
            'phone_number' => 'phone number',
            'date_of_birth' => 'date of birth',
            'city_id' => 'city',
            'country_id' => 'country',
            'preferred_language' => 'preferred language',
            'volunteer_mode' => 'volunteer mode',
            'time_commitment' => 'time commitment',
            'volunteering_interests' => 'volunteering interests',
            'organization_interests' => 'organization preferences',
            'terms_accepted' => 'terms and conditions',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate age if date of birth is provided
            if ($this->date_of_birth) {
                $age = \Carbon\Carbon::parse($this->date_of_birth)->age;
                if ($age < 16) {
                    $validator->errors()->add('date_of_birth', 'You must be at least 16 years old to register.');
                } elseif ($age > 100) {
                    $validator->errors()->add('date_of_birth', 'Please enter a valid date of birth.');
                }
            }

            // Validate that country and city match if both are provided
            if ($this->country_id && $this->city_id) {
                $city = \App\Models\City::find($this->city_id);
                if ($city && $city->country_id != $this->country_id) {
                    $validator->errors()->add('city_id', 'Selected city does not belong to the selected country.');
                }
            }

            // Validate at least one interest is selected (either volunteering or combined)
            $volunteeringInterests = $this->volunteering_interests ?? [];
            $combinedInterests = $this->interests ?? [];
            
            if (empty($volunteeringInterests) && empty($combinedInterests)) {
                $validator->errors()->add('volunteering_interests', 'Please select at least one area of interest.');
            }

            // Validate password strength beyond basic requirements
            if ($this->password) {
                // Check for common passwords
                $commonPasswords = [
                    'password', '12345678', 'qwerty123', 'abc123456', 
                    'password123', '123456789', 'welcome123'
                ];
                
                if (in_array(strtolower($this->password), $commonPasswords)) {
                    $validator->errors()->add('password', 'This password is too common. Please choose a more secure password.');
                }

                // Check if password contains personal information
                if ($this->first_name && stripos($this->password, $this->first_name) !== false) {
                    $validator->errors()->add('password', 'Password should not contain your first name.');
                }
                
                if ($this->last_name && stripos($this->password, $this->last_name) !== false) {
                    $validator->errors()->add('password', 'Password should not contain your last name.');
                }

                if ($this->email && stripos($this->password, explode('@', $this->email)[0]) !== false) {
                    $validator->errors()->add('password', 'Password should not contain parts of your email address.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'email' => strtolower(trim($this->email ?? '')),
            'first_name' => ucfirst(strtolower(trim($this->first_name ?? ''))),
            'last_name' => ucfirst(strtolower(trim($this->last_name ?? ''))),
            'phone_number' => $this->phone_number ? preg_replace('/\s+/', ' ', trim($this->phone_number)) : null,
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}