<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Message;

try {
    DB::beginTransaction();
    
    $message = Message::create([
        'conversation_id' => 2,
        'user_id' => 1,
        'content' => 'Test message',
        'type' => 'text',
        'app_id' => 'test-app-001',
        'is_edited' => false,
        'is_deleted' => false,
    ]);
    
    // Incrémenter les non lus pour les autres participants
    $message->conversation->participants()
        ->where('user_id', '!=', 1)
        ->increment('unread_count');
    
    DB::commit();
    
    echo "Message créé: {$message->id}\n";
    
    // Tenter de diffuser
    try {
        broadcast(new \App\Events\MessageSent($message))->toOthers();
        echo "Broadcast réussi\n";
    } catch (\Exception $e) {
        echo "Erreur de broadcast: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}



