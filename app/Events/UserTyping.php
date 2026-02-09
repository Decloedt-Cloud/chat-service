<?php

namespace App\Events;

use App\Models\User;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * L'utilisateur qui tape.
     *
     * @var \App\Models\User
     */
    public User $user;

    /**
     * L'ID de la conversation.
     *
     * @var int
     */
    public int $conversationId;

    /**
     * L'identifiant de l'application.
     *
     * @var string
     */
    public string $appId;

    /**
     * Indique si l'utilisateur est en train de taper ou a arrêté.
     *
     * @var bool
     */
    public bool $isTyping;

    /**
     * Créer une nouvelle instance de l'événement.
     *
     * @param  \App\Models\User  $user
     * @param  int  $conversationId
     * @param  string  $appId
     * @param  bool  $isTyping
     * @return void
     */
    public function __construct(User $user, int $conversationId, string $appId, bool $isTyping)
    {
        $this->user = $user;
        $this->conversationId = $conversationId;
        $this->appId = $appId;
        $this->isTyping = $isTyping;

        \Log::info('[UserTyping Event] Created', [
            'user_id' => $this->user->id,
            'conversation_id' => $this->conversationId,
            'app_id' => $this->appId,
            'is_typing' => $this->isTyping,
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
     * Définir le nom de l'événement à côté du client.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    /**
     * Définir les données à envoyer avec l'événement.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'conversation_id' => $this->conversationId,
            'is_typing' => $this->isTyping,
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
        // Ne pas mettre en file pour un événement en temps réel comme la frappe
        return false;
    }
}

