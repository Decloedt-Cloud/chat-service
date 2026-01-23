<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;

echo "=== Test de l'index des conversations ===\n\n";

try {
    // Simuler l'utilisateur authentifié
    $user = User::find(1);
    $appId = 'test-app-001';
    
    echo "User ID: {$user->id}\n";
    echo "App ID: $appId\n\n";
    
    // Appeler la méthode index du ConversationController
    echo "1. Test de la méthode conversations():\n";
    $conversations = $user->conversations()
        ->where('app_id', $appId)
        ->with(['lastMessage.user', 'participants.user', 'creator'])
        ->orderBy('updated_at', 'desc')
        ->paginate(20);
    
    echo "   Nombre de conversations: {$conversations->total()}\n";
    
    // Afficher les conversations
    foreach ($conversations as $conv) {
        echo "   - Conversation {$conv->id} (type: {$conv->type})\n";
        
        // Vérifier les relations
        echo "     lastMessage: " . ($conv->lastMessage ? 'Oui' : 'Non') . "\n";
        echo "     participants: " . $conv->participants->count() . "\n";
        
        if ($conv->lastMessage) {
            echo "       lastMessage user: " . ($conv->lastMessage->user ? 'Oui' : 'Non') . "\n";
        }
        
        if ($conv->type === 'direct') {
            $otherParticipant = $conv->participants
                ->firstWhere('user_id', '!=', $user->id);
            
            echo "     Autre participant: " . ($otherParticipant ? $otherParticipant->user->name : 'Aucun') . "\n";
        }
    }
    
    echo "\n✅ conversations() fonctionne !\n\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}



