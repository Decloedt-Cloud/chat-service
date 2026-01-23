<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'created_by',
        'avatar',
        'description',
        'status',
        'app_id',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation: Une conversation a plusieurs participants.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Relation: Une conversation a plusieurs messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Relation: Le créateur de la conversation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir le dernier message de la conversation.
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('is_deleted', false);
        });
    }

    /**
     * Scope: Filtrer par type de conversation.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filtrer par statut.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filtrer par app_id (multi-tenant).
     */
    public function scopeForApp($query, string $appId)
    {
        return $query->where('app_id', $appId);
    }

    /**
     * Vérifier si un utilisateur est participant à la conversation.
     */
    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Vérifier si la conversation est une conversation directe.
     */
    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    /**
     * Vérifier si la conversation est un groupe.
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Obtenir le nombre total de participants.
     */
    public function getParticipantsCountAttribute(): int
    {
        return $this->participants()->count();
    }

    /**
     * Obtenir le nombre de messages non lus pour un utilisateur.
     */
    public function getUnreadCountForUser(User $user): int
    {
        $participant = $this->participants()->where('user_id', $user->id)->first();

        if (!$participant) {
            return 0;
        }

        $query = $this->messages()
            ->where('user_id', '!=', $user->id);

        // Ne filtrer par last_read_at que si ce n'est pas null
        if ($participant->last_read_at) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }
}
