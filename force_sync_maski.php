<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Log;

echo "=== FORCER LA SYNCHRONISATION POUR MASKI AYMEN ===\n\n";

// Récupérer Maski AYMEN
$maski = User::where('id', 19)->first();
if (!$maski) {
    echo "❌ Utilisateur maski AYMEN introuvable !\n";
    exit;
}

echo "Utilisateur trouvé: {$maski->name} (ID: {$maski->id})\n\n";

// Récupérer toutes les conversations de Maski
$conversations = $maski->conversationsForApp('default')->with(['messages.user'])->get();

foreach ($conversations as $conv) {
    echo "--------------------------------------------------\n";
    echo "Conversation {$conv->id} ({$conv->type})\n";
    echo "Mise à jour: {$conv->updated_at}\n";
    
    // Forcer le chargement des messages pour Maski
    $messages = $conv->messages()->where('user_id', '!=', $maski->id)->get();
    
    echo "Messages de autres utilisateurs (" . $messages->count() . "):\n";
    foreach ($messages as $msg) {
        echo "  - [{$msg->created_at}] {$msg->user->name}: {$msg->content}\n";
    }
    
    // Mettre à jour le last_read_at pour forcer la synchronisation
    $participant = $conv->participants()->where('user_id', $maski->id)->first();
    if ($participant) {
        $participant->last_read_at = now();
        $participant->save();
        echo "✅ last_read_at mis à jour pour forcer la synchronisation\n";
    }
    
    echo "\n";
}

echo "=== SYNCHRONISATION FORCÉE ===\n";
echo "✅ Maski devrait maintenant voir tous les messages\n";
echo "✅ Actualisez la page de messagerie de Maski\n";



