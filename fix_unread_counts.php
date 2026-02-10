<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;

echo "=== RÉCUPÉRATION DES COMPTEURS DE MESSAGES NON LUS ===\n\n";

$conversations = Conversation::with(['participants', 'messages'])
    ->where('type', 'direct')
    ->get();

foreach ($conversations as $conv) {
    echo "Conversation {$conv->id} ({$conv->app_id}):\n";

    foreach ($conv->participants as $participant) {
        $userId = $participant->user_id;

        // Compter les messages envoyés par d'autres utilisateurs depuis le dernier lecture
        $lastReadAt = $participant->last_read_at;

        $query = $conv->messages()->where('user_id', '!=', $userId);

        if ($lastReadAt) {
            $query->where('created_at', '>', $lastReadAt);
        }

        $actualUnreadCount = $query->count();
        $currentUnreadCount = $participant->unread_count;

        echo "  User {$userId} ({$participant->user->name}):\n";
        echo "    last_read_at: {$lastReadAt}\n";
        echo "    Unread actuel: {$currentUnreadCount}\n";
        echo "    Unread réel: {$actualUnreadCount}\n";

        if ($actualUnreadCount !== $currentUnreadCount) {
            echo "    ⚠️  Correction nécessaire !\n";

            try {
                $participant->unread_count = $actualUnreadCount;
                $participant->save();
                echo "    ✅ Mis à jour vers {$actualUnreadCount}\n";
            } catch (\Exception $e) {
                echo "    ❌ Erreur: " . $e->getMessage() . "\n";
            }
        } else {
            echo "    ✅ Correct\n";
        }
        echo "\n";
    }
}

echo "=== TERMINÉ ===\n";

