<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'message',
        'message_type',
        'attachment_url',
        'attachment_type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the message has an attachment.
     */
    public function hasAttachment(): bool
    {
        return !empty($this->attachment_url);
    }

    /**
     * Get the attachment URL.
     */
    public function getAttachmentUrlAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Otherwise, prepend the storage URL
        return asset('storage/attachments/' . $value);
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get the message type as a formatted string.
     */
    public function getMessageTypeText(): string
    {
        return match($this->message_type) {
            'text' => 'Text',
            'image' => 'Image',
            'file' => 'File',
            'link' => 'Link',
            default => 'Unknown'
        };
    }

    /**
     * Check if the message is an image.
     */
    public function isImage(): bool
    {
        return $this->message_type === 'image' || 
               in_array($this->attachment_type, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Check if the message is a file.
     */
    public function isFile(): bool
    {
        return $this->message_type === 'file' && !$this->isImage();
    }
}