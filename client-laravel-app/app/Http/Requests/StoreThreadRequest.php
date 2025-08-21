<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreThreadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                'min:5'
            ],
            'content' => [
                'required',
                'string',
                'min:10',
                'max:10000'
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:5'
            ],
            'attachments.*' => [
                'file',
                'max:10240', // 10MB
                'mimes:jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z,html,css,js,json,xml'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your thread.',
            'title.min' => 'Thread title must be at least 5 characters long.',
            'title.max' => 'Thread title cannot exceed 255 characters.',
            'content.required' => 'Please provide content for your thread.',
            'content.min' => 'Thread content must be at least 10 characters long.',
            'content.max' => 'Thread content cannot exceed 10,000 characters.',
            'attachments.max' => 'You can upload a maximum of 5 files.',
            'attachments.*.max' => 'Each file must be smaller than 10MB.',
            'attachments.*.mimes' => 'File type not allowed. Supported types: images, documents, archives, and code files.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'thread title',
            'content' => 'thread content',
            'attachments' => 'attachments',
            'attachments.*' => 'attachment file'
        ];
    }
}