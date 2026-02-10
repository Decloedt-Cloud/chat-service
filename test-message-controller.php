<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;

try {
    // Simuler la requête API
    $user = User::find(1);
    $conversation = Conversation::find(2);
    $appId = 'test-app-001';
    
    echo "User: {$user->id} - {$user->name}\n";
    echo "Conversation: {$conversation->id}\n";
    echo "App ID: $appId\n";
    echo "Vérifier si participant: " . ($conversation->hasParticipant($user) ? 'Oui' : 'Non') . "\n";
    
    // Simuler création de message
    $data = [
        'content' => 'Test via script',
        'type' => 'text',
    ];
    
    $validated = $data;
    
    DB::beginTransaction();
    
    $message = \App\Models\Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => $validated['content'],
        'type' => $validated['type'],
        'file_url' => null,
        'file_name' => null,
        'file_size' => null,
        'app_id' => $appId,
        'is_edited' => false,
        'is_deleted' => false,
    ]);
    
    echo "Message créé: {$message->id}\n";
    
    // Mettre à jour le compteur de messages non lus pour les autres participants
    $conversation->participants()
        ->where('user_id', '!=', $user->id)
        ->increment('unread_count');
    
    echo "Compteurs incrémentés\n";
    
    DB::commit();
    
    echo "Transaction validée\n";
    
    // Charger les relations
    $message->load('user');
    
    // Tenter de diffuser
    echo "Tentative de broadcast...\n";
    try {
        $event = new \App\Events\MessageSent($message);
        echo "Événement créé\n";
        
        broadcast($event)->toOthers();
        echo "Broadcast réussi (toOthers)\n";
    } catch (\Exception $e) {
        echo "Erreur de broadcast: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "Erreur générale: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}



