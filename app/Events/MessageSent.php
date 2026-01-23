<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Le message envoyé.
     *
     * @var \App\Models\Message
     */
    public Message $message;

    /**
     * L'utilisateur qui a envoyé le message.
     *
     * @var \App\Models\User
     */
    public User $sender;

    /**
     * L'identifiant de l'application.
     *
     * @var string
     */
    public string $appId;

    /**
     * Créer une nouvelle instance de l'événement.
     *
     * @param  \App\Models\Message  $message
     * @return void
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
        // Charger les relations nécessaires : user (expéditeur) et conversation avec participants
        $this->message->load(['user', 'conversation.participants']);
        
        $this->sender = $message->user;
        $this->appId = $message->app_id;

        \Log::info('[MessageSent Event] Created', [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->sender->id,
            'app_id' => $this->appId,
            'channels_count' => 1 + ($this->message->conversation ? $this->message->conversation->participants->count() : 0),
        ]);
    }

    /**
     * Définir le channel sur lequel l'événement sera diffusé.
     *
     * Format: conversation.{conversationId}.{app_id}
     * Note: PrivateChannel ajoute automatiquement le préfixe "private-"
     * Le channel final sera: private-conversation.{conversationId}.{app_id}
     *
     * Ce format permet l'isolement par:
     * 1. Conversation spécifique
     * 2. Application cliente spécifique (multi-tenant)
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // 1. Channel de la conversation (pour le chat actif)
        // NE PAS mettre "private-" car PrivateChannel l'ajoute automatiquement
        $channels[] = new PrivateChannel(
            'conversation.' . $this->message->conversation_id . '.' . $this->appId
        );

        // 2. Channels utilisateurs (pour les notifications globales / badges)
        if ($this->message->conversation && $this->message->conversation->participants) {
            foreach ($this->message->conversation->participants as $participant) {
                // On notifie tous les participants, SAUF l'expéditeur (qui a déjà l'info)
                if ($participant->user_id !== $this->sender->id) {
                    $channels[] = new PrivateChannel(
                        'user.' . $participant->user_id . '.' . $this->appId
                    );
                }
            }
        }

        return $channels;
    }

    /**
     * Définir le nom de l'événement à côté du client.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Définir les données à envoyer avec l'événement.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'user_id' => $this->message->user_id,
                'content' => $this->message->content,
                'type' => $this->message->type,
                'file_url' => $this->message->file_url,
                'file_name' => $this->message->file_name,
                'file_size' => $this->message->file_size,
                'is_edited' => $this->message->is_edited,
                'edited_at' => $this->message->edited_at ? $this->message->edited_at->toIso8601String() : null,
                'created_at' => $this->message->created_at->toIso8601String(),
                'updated_at' => $this->message->updated_at->toIso8601String(),
            ],
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'email' => $this->sender->email,
            ],
            'app_id' => $this->appId,
        ];
    }

    /**
     * Déterminer si l'événement doit être mis en queue.
     *
     * @return bool
     */
    public function shouldQueue(): bool
    {
        // Mettre en file pour éviter de bloquer la réponse HTTP
        return false;
    }

    /**
     * Définir le délai de conservation de l'événement dans la queue.
     *
     * @return int|null
     */
    public function queueConnection(): ?string
    {
        return 'sync'; // Utiliser la queue sync pour le développement
    }
}

