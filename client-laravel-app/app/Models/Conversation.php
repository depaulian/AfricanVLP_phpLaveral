<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'title',
        'type',
        'status',
        'created_by',
        'organization_id',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the user who created the conversation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the organization this conversation belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the participants of this conversation.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withPivot('joined_at', 'left_at', 'role')
                    ->withTimestamps();
    }

    /**
     * Get the messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    /**
     * Get the latest message in this conversation.
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->latest();
    }

    /**
     * Check if the conversation is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if a user is a participant in this conversation.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->participants()
                    ->where('user_id', $userId)
                    ->whereNull('left_at')
                    ->exists();
    }

    /**
     * Add a participant to the conversation.
     */
    public function addParticipant(int $userId, string $role = 'member'): void
    {
        $this->participants()->attach($userId, [
            'joined_at' => now(),
            'role' => $role,
        ]);
    }

    /**
     * Remove a participant from the conversation.
     */
    public function removeParticipant(int $userId): void
    {
        $this->participants()->updateExistingPivot($userId, [
            'left_at' => now(),
        ]);
    }

    /**
     * Get unread messages count for a specific user.
     */
    public function getUnreadMessagesCount(int $userId): int
    {
        return $this->messages()
                    ->where('user_id', '!=', $userId)
                    ->where('created', '>', function($query) use ($userId) {
                        $query->select('last_read_at')
                              ->from('conversation_participants')
                              ->where('conversation_id', $this->id)
                              ->where('user_id', $userId);
                    })
                    ->count();
    }

    /**
     * Check if conversation has unread messages for a user.
     */
    public function hasUnreadMessages($userId)
    {
        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->whereNull('read_at')
            ->exists();
    }

    /**
     * Get the last message in the conversation.
     */
    public function lastMessage()
    {
        return $this->hasOne(ConversationMessage::class)->latest();
    }

    /**
     * Mark all messages as read for a user.
     */
    public function markAsReadForUser($userId)
    {
        $this->messages()
            ->where('user_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}