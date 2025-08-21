<?php

namespace App\Http\Requests;

use App\Services\InputSanitizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class SecureFormRequest extends FormRequest
{
    protected InputSanitizationService $sanitizer;

    public function __construct()
    {
        parent::__construct();
        $this->sanitizer = app(InputSanitizationService::class);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'regex' => 'The :attribute format is invalid.',
            'url' => 'The :attribute must be a valid URL.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'boolean' => 'The :attribute field must be true or false.',
            'date' => 'The :attribute is not a valid date.',
            'file' => 'The :attribute must be a file.',
            'image' => 'The :attribute must be an image.',
            'mimes' => 'The :attribute must be a file of type: :values.',
            'max_file_size' => 'The :attribute may not be greater than :max kilobytes.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeInput();
        $this->checkForSecurityThreats();
    }

    /**
     * Sanitize input data before validation.
     */
    protected function sanitizeInput(): void
    {
        $sanitized = [];
        
        foreach ($this->all() as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeField($key, $value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizer->sanitizeArray($value, $this->getFieldType($key));
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        $this->replace($sanitized);
    }

    /**
     * Sanitize individual field based on its type.
     */
    protected function sanitizeField(string $key, string $value): string
    {
        $fieldType = $this->getFieldType($key);
        return $this->sanitizer->sanitizeByType($value, $fieldType);
    }

    /**
     * Get the expected type for a field (override in child classes).
     */
    protected function getFieldType(string $key): string
    {
        // Common field type mappings
        $typeMap = [
            'email' => 'email',
            'website' => 'url',
            'url' => 'url',
            'phone' => 'phone',
            'search' => 'search',
            'content' => 'html',
            'description' => 'html',
            'bio' => 'html',
            'message' => 'html',
        ];

        foreach ($typeMap as $pattern => $type) {
            if (str_contains($key, $pattern)) {
                return $type;
            }
        }

        return 'text';
    }

    /**
     * Check for security threats in input data.
     */
    protected function checkForSecurityThreats(): void
    {
        foreach ($this->all() as $key => $value) {
            if (is_string($value)) {
                // Check for SQL injection
                if ($this->sanitizer->detectSqlInjection($value)) {
                    $this->sanitizer->logSecurityViolation('SQL Injection Attempt', $value, ['field' => $key]);
                    throw new HttpResponseException(
                        response()->json(['error' => 'Invalid input detected'], 400)
                    );
                }

                // Check for XSS
                if ($this->sanitizer->detectXss($value)) {
                    $this->sanitizer->logSecurityViolation('XSS Attempt', $value, ['field' => $key]);
                    throw new HttpResponseException(
                        response()->json(['error' => 'Invalid input detected'], 400)
                    );
                }
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        // Log validation failures for security monitoring
        logger()->info('Form validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['password', 'password_confirmation']),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'url' => $this->fullUrl(),
        ]);

        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get the validated data from the request with additional security checks.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();
        
        // Additional security validation on validated data
        foreach ($validated as $field => $value) {
            if (is_string($value) && strlen($value) > 10000) {
                logger()->warning('Unusually large input detected', [
                    'field' => $field,
                    'length' => strlen($value),
                    'ip' => $this->ip()
                ]);
            }
        }
        
        return $validated;
    }

    /**
     * Common validation rules for security.
     */
    protected function securityRules(): array
    {
        return [
            'honeypot' => 'prohibited', // Honeypot field should be empty
            'timestamp' => 'sometimes|integer|min:' . (time() - 3600), // Form should be submitted within 1 hour
        ];
    }

    /**
     * Common file validation rules.
     */
    protected function fileRules(array $allowedMimes = [], int $maxSize = 10240): array
    {
        $rules = ['file'];
        
        if (!empty($allowedMimes)) {
            $rules[] = 'mimes:' . implode(',', $allowedMimes);
        }
        
        $rules[] = 'max:' . $maxSize;
        
        return $rules;
    }

    /**
     * Common image validation rules.
     */
    protected function imageRules(int $maxSize = 5120): array
    {
        return [
            'image',
            'mimes:jpeg,png,jpg,gif,webp',
            'max:' . $maxSize,
            'dimensions:max_width=2000,max_height=2000'
        ];
    }
}