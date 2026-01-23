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

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * L'ID du message supprimé.
     *
     * @var int
     */
    public int $messageId;

    /**
     * L'ID de la conversation.
     *
     * @var int
     */
    public int $conversationId;

    /**
     * L'utilisateur qui a supprimé le message.
     *
     * @var \App\Models\User
     */
    public User $deletedBy;

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
     * @param  \App\Models\User  $deletedBy
     * @return void
     */
    public function __construct(Message $message, User $deletedBy)
    {
        $this->messageId = $message->id;
        $this->conversationId = $message->conversation_id;
        $this->deletedBy = $deletedBy;
        $this->appId = $message->app_id;

        \Log::info('[MessageDeleted Event] Created', [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'deleted_by' => $this->deletedBy->id,
            'app_id' => $this->appId,
            'channel' => 'private-conversation.' . $this->conversationId . '.' . $this->appId,
        ]);
    }

    /**
     * Définir le channel sur lequel l'événement sera diffusé.
     *
     * Format: conversation.{conversationId}.{app_id}
     * Note: PrivateChannel ajoute automatiquement le préfixe "private-"
     * Le channel final sera: private-conversation.{conversationId}.{app_id}
     *
     * @return \Illuminate\Broadcasting\PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(
            'conversation.' . $this->conversationId . '.' . $this->appId
        );
    }

    /**
     * Définir le nom de l'événement côté client.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    /**
     * Définir les données à envoyer avec l'événement.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'deleted_by' => [
                'id' => $this->deletedBy->id,
                'name' => $this->deletedBy->name,
            ],
            'deleted_at' => now()->toIso8601String(),
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
        return false;
    }

    /**
     * Définir le délai de conservation de l'événement dans la queue.
     *
     * @return int|null
     */
    public function queueConnection(): ?string
    {
        return 'sync';
    }
}

