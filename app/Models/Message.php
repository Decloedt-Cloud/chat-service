<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
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
        'content',
        'type',
        'file_url',
        'file_name',
        'file_size',
        'duration',
        'is_edited',
        'is_deleted',
        'edited_at',
        'app_id',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'file_size' => 'integer',
        'duration' => 'integer',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation: Le message appartient à une conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation: Le message appartient à un utilisateur (expéditeur).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: Réponses à ce message (si on implémente les threads).
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    /**
     * Relation: Message parent (si c'est une réponse).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    /**
     * Scope: Filtrer par type de message.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filtrer par app_id (multi-tenant).
     */
    public function scopeForApp($query, string $appId)
    {
        return $query->where('app_id', $appId);
    }

    /**
     * Scope: Exclure les messages supprimés.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope: Obtenir les messages récents.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Marquer le message comme supprimé (soft delete logique).
     */
    public function markAsDeleted(): void
    {
        $this->update([
            'is_deleted' => true,
            'content' => '[Message supprimé]',
            'file_url' => null,
            'file_name' => null,
            'file_size' => null,
        ]);
    }

    /**
     * Éditer le message.
     */
    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Obtenir l'URL complète du fichier joint.
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->attributes['file_url'] ? url($this->attributes['file_url']) : null;
    }

    /**
     * Vérifier si le message contient un fichier.
     */
    public function hasFile(): bool
    {
        return !empty($this->file_url);
    }

    /**
     * Obtenir la taille formatée du fichier.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < 3) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Scope: Rechercher dans le contenu des messages.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('content', 'like', "%{$term}%");
    }
}

