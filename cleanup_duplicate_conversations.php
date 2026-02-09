<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

echo "=== NETTOYAGE DES CONVERSATIONS DUPLIQUÉES ===\n\n";

// Récupérer toutes les conversations directes
$conversations = Conversation::where('type', 'direct')
    ->with(['participants'])
    ->get();

echo "Nombre de conversations directes: " . $conversations->count() . "\n\n";

// Grouper les conversations par paire d'utilisateurs
$conversationGroups = [];

foreach ($conversations as $conv) {
    // Récupérer les IDs des participants triés
    $participants = $conv->participants->pluck('user_id')->sort()->values();
    $key = $participants->implode('-') . '|' . $conv->app_id;

    if (!isset($conversationGroups[$key])) {
        $conversationGroups[$key] = [];
    }
    $conversationGroups[$key][] = $conv;
}

echo "Analyse des groupes de conversations dupliquées...\n\n";

$duplicatesCount = 0;
$totalMessagesToMove = 0;

foreach ($conversationGroups as $key => $group) {
    if (count($group) > 1) {
        echo "🔴 DUPLONC DÉTECTÉ : {$key}\n";
        echo "   " . count($group) . " conversations trouvées\n";

        // Garder la conversation la plus récente (avec le plus de messages ou la plus récente)
        usort($group, function ($a, $b) {
            // Priorité 1: Plus de messages
            $msgA = $a->messages()->count();
            $msgB = $b->messages()->count();
            if ($msgA !== $msgB) {
                return $msgB - $msgA;
            }
            // Priorité 2: Plus récente
            return $b->updated_at <=> $a->updated_at;
        });

        $keep = $group[0];
        $toDelete = array_slice($group, 1);

        echo "   → Garder conversation {$keep->id} (" . $keep->messages()->count() . " messages, dernière activité: {$keep->updated_at})\n";

        foreach ($toDelete as $conv) {
            $msgCount = $conv->messages()->count();
            echo "   → Supprimer conversation {$conv->id} ({$msgCount} messages, dernière activité: {$conv->updated_at})\n";
            $duplicatesCount++;
            $totalMessagesToMove += $msgCount;

            // Déplacer les messages vers la conversation à garder
            DB::beginTransaction();
            try {
                Message::where('conversation_id', $conv->id)
                    ->update(['conversation_id' => $keep->id]);
                ConversationParticipant::where('conversation_id', $conv->id)->delete();
                $conv->delete();
                DB::commit();
                echo "     ✓ Messages déplacés et conversation supprimée\n";
            } catch (\Exception $e) {
                DB::rollBack();
                echo "     ✗ Erreur: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
}

if ($duplicatesCount === 0) {
    echo "✅ Aucun doublon détecté !\n";
} else {
    echo "📊 RÉSUMÉ:\n";
    echo "   - {$duplicatesCount} conversations dupliquées trouvées\n";
    echo "   - {$totalMessagesToMove} messages concernés\n";
    echo "\n";
    echo "⚠️  Pour effectuer le nettoyage, décommentez le code dans le script.\n";
    echo "⚠️  Faites une SAUVEGARDE de la base de données avant !\n";
}

echo "\n=== PRÉVENTION DES FUTURS DOUBLONS ===\n\n";

echo "Pour éviter les doublons à l'avenir, assurez-vous que:\n";
echo "1. La méthode directConversationWith() vérifie dans les DEUX sens\n";
echo "2. Lors de la création d'une conversation, vérifiez si elle existe déjà\n";
echo "3. Utilisez toujours conversationsForApp(\$appId) au lieu de conversations()\n";

