<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

/**
 * Channel pour les notifications utilisateur privées
 *
 * Permet à un utilisateur d'écouter ses propres notifications
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Channel de présence global par application
 * 
 * Permet de suivre les utilisateurs en ligne en temps réel.
 * 
 * @param  \App\Models\User  $user
 * @param  string  $appId
 * @return array|bool
 */
Broadcast::channel('presence-global.{appId}', function ($user, $appId) {
    return [
        'id' => $user->id,
        'user_id' => $user->user_id,
        'name' => $user->name,
        'avatar' => $user->avatar
    ];
});

Broadcast::channel('user.{userId}.{appId}', function ($user, $userId, $appId) {
    return (int) $user->id === (int) $userId;
});

/**
 * Channel privé pour les conversations
 *
 * Format: private-conversation.{conversationId}.{app_id}
 *
 * Autorisation:
 * - L'utilisateur doit être authentifié
 * - L'utilisateur doit être participant de la conversation
 * - L'application ID doit correspondre (pour le multi-tenant)
 *
 * @param  \App\Models\User  $user
 * @param  int  $conversationId
 * @param  string  $appId
 * @return bool
 */
// NOTE: "private-" est inclus ici pour correspondre au nom utilisé dans MessageController
Broadcast::channel('private-conversation.{conversationId}.{appId}', function ($user, $conversationId, $appId) {
    try {
        $conversation = Conversation::where('id', $conversationId)
            ->where('app_id', $appId)
            ->first();

        if (!$conversation) {
            Log::warning("Channel authorization failed: Conversation not found", [
                'conversation_id' => $conversationId,
                'app_id' => $appId,
                'user_id' => $user->id,
            ]);
            return false;
        }

        $isParticipant = $conversation->hasParticipant($user);

        if (!$isParticipant) {
            Log::warning("Channel authorization failed: User not participant", [
                'conversation_id' => $conversationId,
                'app_id' => $appId,
                'user_id' => $user->id,
            ]);
            return false;
        }

        return true;
    } catch (\Exception $e) {
        Log::error("Channel authorization error", [
            'error' => $e->getMessage(),
            'conversation_id' => $conversationId,
            'app_id' => $appId,
            'user_id' => $user->id,
        ]);
        return false;
    }
});


