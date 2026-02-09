<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

echo "=== DEBUG DES CONVERSATIONS POUR MASKI AYMEN ===\n\n";

// Récupérer Maski AYMEN
$maski = User::where('id', 19)->first();
if (!$maski) {
    echo "❌ Utilisateur maski AYMEN introuvable !\n";
    exit;
}

echo "Utilisateur trouvé: {$maski->name} (ID: {$maski->id})\n\n";

// Récupérer les conversations de Maski
$conversations = $maski->conversationsForApp('default')->with(['participants.user', 'lastMessage.user'])->get();

echo "Conversations de {$maski->name}:\n";
foreach ($conversations as $conv) {
    echo "--------------------------------------------------\n";
    echo "Conversation {$conv->id} ({$conv->type})\n";
    echo "Mise à jour: {$conv->updated_at}\n";
    
    echo "Participants:\n";
    foreach ($conv->participants as $p) {
        echo "  - {$p->user->name} ({$p->user_id}) - Role: {$p->role}\n";
    }
    
    echo "Dernier message:\n";
    if ($conv->lastMessage) {
        echo "  - Expéditeur: {$conv->lastMessage->user->name}\n";
        echo "  - Contenu: {$conv->lastMessage->content}\n";
        echo "  - Date: {$conv->lastMessage->created_at}\n";
    } else {
        echo "  - Aucun message\n";
    }
    
    echo "Unread count: {$conv->unread_count_for_user}\n";
    echo "\n";
}

echo "=== ANALYSE DES PROBLÈMES POSSIBLES ===\n";
echo "1. Vérifier si getChatUserId() retourne le bon ID pour Maski\n";
echo "2. Vérifier si la logique de getOtherParticipant() filtre correctement\n";
echo "3. Vérifier si les données sont bien retournées par l'API\n";

echo "\n=== TEST DE LA LOGIQUE getOtherParticipant ===\n";

// Simuler la logique de getOtherParticipant
foreach ($conversations as $conv) {
    echo "Conversation {$conv->id}:\n";
    
    $chatUserId = $maski->id; // Simuler getChatUserId()
    $currentUser = $maski;
    
    if (!$conv->participants) {
        echo "  - Aucun participant\n";
        continue;
    }
    
    $other = $conv->participants->firstWhere('user_id', '!=', $chatUserId);
    
    echo "  - Autre participant: {$other->user->name} ({$other->user_id})\n";
    echo "  - Est-ce abb Client? " . ($other->user_id == 30 ? "OUI" : "NON") . "\n";
}

echo "\n=== RÉCAPITULATIF ===\n";
echo "✅ Si abb Client est bien identifié comme autre participant, le problème est dans le frontend\n";
echo "✅ Vérifiez que getChatUserId() retourne bien 19 pour Maski\n";
echo "✅ Vérifiez que la logique de filtrage fonctionne correctement\n";



