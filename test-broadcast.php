<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test Broadcast ===\n\n";

// Trouver le dernier message
$message = App\Models\Message::latest()->first();

if (!$message) {
    echo "❌ Aucun message trouvé\n";
    exit(1);
}

echo "Message trouvé:\n";
echo "  - ID: {$message->id}\n";
echo "  - Conversation: {$message->conversation_id}\n";
echo "  - Sender: {$message->user_id}\n";
echo "  - App ID: {$message->app_id}\n";
echo "  - Content: {$message->content}\n\n";

// Créer l'événement
$event = new App\Events\MessageSent($message);

echo "Event créé:\n";
echo "  - Channel: " . $event->broadcastOn()->name . "\n";
echo "  - Event name: " . $event->broadcastAs() . "\n\n";

// Envoyer le broadcast
echo "Envoi du broadcast...\n";

try {
    broadcast($event);
    echo "✅ Broadcast envoyé avec succès!\n";
} catch (Exception $e) {
    echo "❌ Erreur broadcast: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

