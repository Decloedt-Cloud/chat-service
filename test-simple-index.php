<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;

echo "=== Test Simple de l'index ===\n\n";

try {
    // 1. Test simple de la requête
    $user = User::find(1);
    $appId = 'test-app-001';
    
    echo "User ID: {$user->id}\n";
    echo "App ID: $appId\n";
    
    // 2. Test de la méthode conversations() directement
    $conversations = $user->conversations()
        ->where('app_id', $appId)
        ->with(['lastMessage.user', 'participants.user', 'creator'])
        ->orderBy('updated_at', 'desc')
        ->paginate(20);
    
    echo "Nombre total: {$conversations->total()}\n";
    echo "Items par page: {$conversations->perPage()}\n";
    echo "Page courante: {$conversations->currentPage()}\n";
    
    // 3. Essayer d'accéder au premier item
    if ($conversations->total() > 0) {
        $firstConversation = $conversations->items()[0];
        echo "Première conversation ID: {$firstConversation->id}\n";
        echo "Type: {$firstConversation->type}\n";
        echo "Last message: " . ($firstConversation->lastMessage ? 'Oui (ID: ' . $firstConversation->lastMessage->id . ')' : 'Non') . "\n";
        echo "Participants: {$firstConversation->participants->count()}\n";
    }
    
    echo "\n✅ Test réussi !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}



