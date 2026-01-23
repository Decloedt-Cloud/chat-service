<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * La conversation lue.
     *
     * @var \App\Models\Conversation
     */
    public Conversation $conversation;

    /**
     * L'utilisateur qui a lu les messages.
     *
     * @var \App\Models\User
     */
    public User $reader;

    /**
     * L'identifiant de l'application.
     *
     * @var string
     */
    public string $appId;

    /**
     * Créer une nouvelle instance de l'événement.
     *
     * @param  \App\Models\Conversation  $conversation
     * @param  \App\Models\User  $reader
     * @return void
     */
    public function __construct(Conversation $conversation, User $reader)
    {
        $this->conversation = $conversation;
        $this->reader = $reader;
        $this->appId = $conversation->app_id;

        \Log::info('[MessageRead Event] Created', [
            'conversation_id' => $this->conversation->id,
            'reader_id' => $this->reader->id,
            'reader_name' => $this->reader->name,
            'app_id' => $this->appId,
            'channel' => 'private-conversation.' . $this->conversation->id . '.' . $this->appId,
        ]);
    }

    /**
     * Définir le channel sur lequel l'événement sera diffusé.
     *
     * @return \Illuminate\Broadcasting\PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        // NE PAS mettre "private-" car PrivateChannel l'ajoute automatiquement
        return new PrivateChannel(
            'conversation.' . $this->conversation->id . '.' . $this->appId
        );
    }

    /**
     * Définir le nom de l'événement à écouter.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * Définir les données à envoyer avec l'événement.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        // Récupérer l'heure de lecture du participant
        $participant = $this->conversation->participants()
            ->where('user_id', $this->reader->id)
            ->first();

        return [
            'conversation_id' => $this->conversation->id,
            'reader' => [
                'id' => $this->reader->id,
                'name' => $this->reader->name,
            ],
            'read_at' => $participant && $participant->last_read_at
                ? $participant->last_read_at->toIso8601String()
                : now()->toIso8601String(),
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
}








