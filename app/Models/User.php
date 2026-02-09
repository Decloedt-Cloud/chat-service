<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_id',
        'avatar',
        'gender',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attributes to append to the array form.
     *
     * @var array
     */
    protected $appends = ['sexe'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Accessor for 'sexe' attribute to maintain compatibility with frontend.
     */
    public function getSexeAttribute()
    {
        // Normalisation pour le frontend (attendu: 'Femme' ou 'Homme')
        if (in_array(strtolower($this->gender), ['femme', 'female', 'f'])) {
            return 'Femme';
        }
        return 'Homme'; // Par défaut ou si 'homme', 'male', 'm'
    }

    /**
     * Relation: L'utilisateur est créateur de plusieurs conversations.
     */
    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by');
    }

    /**
     * Relation: L'utilisateur participe à plusieurs conversations.
     * NOTE: Cette relation retourne TOUTES les conversations sans filtre app_id.
     * Pour filtrer par application, utilisez conversationsForApp($appId).
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_participants',
            'user_id',
            'conversation_id'
        )->withPivot(['role', 'last_read_at', 'unread_count', 'joined_at'])
         ->withTimestamps()
         ->orderBy('updated_at', 'desc');
    }

    /**
     * Obtenir les conversations de l'utilisateur pour une application spécifique.
     * C'est la méthode CORRECTE à utiliser pour l'isolation multi-tenant.
     */
    public function conversationsForApp(string $appId = 'default'): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_participants',
            'user_id',
            'conversation_id'
        )->where('conversations.app_id', $appId)
         ->withPivot(['role', 'last_read_at', 'unread_count', 'joined_at'])
         ->withTimestamps()
         ->orderBy('conversations.updated_at', 'desc');
    }

    /**
     * Relation: L'utilisateur a envoyé plusieurs messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Obtenir toutes les conversations de l'utilisateur avec le dernier message.
     * DEPRECATED: Utilisez conversationsForApp($appId)->with('lastMessage.user', 'participants.user')->get() à la place.
     */
    public function conversationsWithLastMessage()
    {
        return $this->conversations()
            ->with('lastMessage.user', 'participants.user')
            ->get();
    }

    /**
     * Obtenir les conversations directes avec un autre utilisateur.
     */
    public function directConversationWith(User $otherUser, string $appId = 'default'): ?Conversation
    {
        return $this->conversationsForApp($appId)
            ->where('type', 'direct')
            ->whereHas('participants', function ($query) use ($otherUser) {
                $query->where('user_id', $otherUser->id);
            })
            ->first();
    }

    /**
     * Créer ou récupérer une conversation directe avec un autre utilisateur.
     */
    public function getOrCreateDirectConversationWith(User $otherUser, string $appId = 'default'): Conversation
    {
        // CORRECTION CRITIQUE: Passer le $appId pour éviter de récupérer une conversation d'une autre app
        $conversation = $this->directConversationWith($otherUser, $appId);

        if ($conversation) {
            return $conversation;
        }

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->id,
            'status' => 'active',
            'app_id' => $appId,
        ]);

        $conversation->participants()->createMany([
            [
                'user_id' => $this->id,
                'role' => 'owner',
            ],
            [
                'user_id' => $otherUser->id,
                'role' => 'member',
            ],
        ]);

        return $conversation->load('participants');
    }

    /**
     * Obtenir le nombre total de messages non lus.
     */
    public function getTotalUnreadCountAttribute(): int
    {
        return $this->conversations()->sum('unread_count') ?? 0;
    }

    /**
     * Scope: Rechercher par nom ou email.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
    }
}
