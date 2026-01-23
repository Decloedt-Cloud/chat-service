<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ConversationParticipant extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_read_at',
        'unread_count',
        'joined_at',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'unread_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation: Le participant appartient à une conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation: Le participant appartient à un utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: Messages envoyés par ce participant.
     */
    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(
            Message::class,
            Conversation::class,
            'id',
            'conversation_id',
            'conversation_id',
            'id'
        )->where('messages.user_id', $this->user_id);
    }

    /**
     * Scope: Filtrer par rôle.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: Filtrer par conversation.
     */
    public function scopeForConversation($query, int $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Scope: Filtrer par utilisateur.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Vérifier si le participant est le propriétaire.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Vérifier si le participant est administrateur.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    /**
     * Marquer les messages comme lus.
     */
    public function markAsRead(): void
    {
        $this->update([
            'last_read_at' => now(),
            'unread_count' => 0,
        ]);
    }

    /**
     * Incrémenter le compteur de messages non lus.
     */
    public function incrementUnreadCount(): void
    {
        $this->increment('unread_count');
    }
}

