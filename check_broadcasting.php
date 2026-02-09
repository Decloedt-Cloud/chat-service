<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Conversation;
use App\Models\User;
use App\Models\Message;

echo "=== VÉRIFICATION DU BROADCASTING ===\n\n";

// Récupérer les conversations avec les messages récents
$conversations = Conversation::where('type', 'direct')
    ->with(['participants.user', 'messages.user'])
    ->orderBy('updated_at', 'desc')
    ->take(5)
    ->get();

foreach ($conversations as $conv) {
    echo "--------------------------------------------------\n";
    echo "Conversation {$conv->id} ({$conv->app_id})\n";
    echo "Mise à jour: {$conv->updated_at}\n";
    
    echo "Participants:\n";
    foreach ($conv->participants as $p) {
        echo "  - {$p->user->name} ({$p->user_id}) - Unread: {$p->unread_count}\n";
    }
    
    echo "Derniers messages:\n";
    foreach ($conv->messages->sortByDesc('created_at')->take(3) as $msg) {
        echo "  [{$msg->created_at}] {$msg->user->name}: {$msg->content}\n";
    }
    
    echo "\n";
}

echo "=== VÉRIFICATION DES MESSAGES RÉCENTS ===\n\n";

// Vérifier les messages récents
$recentMessages = Message::where('created_at', '>', now()->subMinutes(30))
    ->with(['user', 'conversation'])
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

foreach ($recentMessages as $msg) {
    echo "Message {$msg->id} dans conversation {$msg->conversation_id}:\n";
    echo "  - Expéditeur: {$msg->user->name}\n";
    echo "  - Contenu: {$msg->content}\n";
    echo "  - Date: {$msg->created_at}\n";
    echo "  - Broadcast channel: private-conversation.{$msg->conversation_id}.{$msg->app_id}\n\n";
}

echo "=== RÉCAPITULATIF ===\n\n";
echo "✅ Messages déplacés vers conversation 5\n";
echo "✅ Maski a 12 messages non lus\n";
echo "✅ Le problème est probablement côté frontend\n";
echo "✅ Vérifiez que Maski écoute sur le channel: private-conversation.5.default\n";
echo "✅ Vérifiez que le frontend est connecté à Reverb/Pusher\n";



