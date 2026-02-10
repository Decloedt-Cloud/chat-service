<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;

echo "=== DIAGNOSTIC COMPLET ===\n\n";

$user = User::find(1);
$appId = 'test-app-001';

echo "User ID: {$user->id}\n";
echo "App ID: $appId\n\n";

// 1. Test conversations()
echo "1. Test de conversations():\n";
try {
    $conversations = $user->conversations()
        ->where('app_id', $appId)
        ->with(['lastMessage.user', 'participants.user', 'creator'])
        ->orderBy('updated_at', 'desc')
        ->paginate(20);
    
    echo "   ✅ Pagination OK: {$conversations->total()} conversations\n";
    echo "   Page courante: {$conversations->currentPage()}\n";
    echo "   Par page: {$conversations->perPage()}\n";
    
    // 2. Test la transformation avec getUnreadCountForUser
    echo "\n2. Test de la transformation avec getUnreadCountForUser():\n";
    $conversations->getCollection()->each(function ($conv) use ($user) {
        echo "   Conversation {$conv->id}:\n";
        
        try {
            $unreadCount = $conv->getUnreadCountForUser($user);
            echo "      ✅ unread_count: $unreadCount\n";
        } catch (\Exception $e) {
            echo "      ❌ Erreur getUnreadCountForUser: " . $e->getMessage() . "\n";
            echo "      Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
        
        // 3. Vérifier lastMessage
        echo "      lastMessage: " . ($conv->lastMessage ? 'existe' : 'NULL') . "\n";
    });
    
    echo "\n✅ Toutes les tests réussis !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}



