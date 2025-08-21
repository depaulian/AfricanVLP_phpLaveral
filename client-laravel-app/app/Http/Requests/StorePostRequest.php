<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
            'content' => [
                'required',
                'string',
                'min:5',
                'max:5000'
            ],
            'parent_post_id' => [
                'nullable',
                'exists:forum_posts,id'
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
            'content.required' => 'Please provide content for your post.',
            'content.min' => 'Post content must be at least 5 characters long.',
            'content.max' => 'Post content cannot exceed 5,000 characters.',
            'parent_post_id.exists' => 'The post you are replying to does not exist.',
            'attachments.max' => 'You can upload a maximum of 5 files per post.',
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
            'content' => 'post content',
            'parent_post_id' => 'parent post',
            'attachments' => 'attachments',
            'attachments.*' => 'attachment file'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate parent post belongs to the same thread
            if ($this->parent_post_id) {
                $parentPost = \App\Models\ForumPost::find($this->parent_post_id);
                $currentThread = $this->route('thread');
                
                if ($parentPost && $parentPost->thread_id !== $currentThread->id) {
                    $validator->errors()->add('parent_post_id', 'Invalid parent post for this thread.');
                }
            }
        });
    }
}