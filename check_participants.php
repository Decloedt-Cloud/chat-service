<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Conversation;
use App\Models\User;
use App\Models\Message;

echo "=== DIAGNOSTIC DES CONVERSATIONS ET PARTICIPANTS ===\n\n";

// Récupérer toutes les conversations actives
$conversations = Conversation::with(['participants.user', 'messages'])
    ->where('status', 'active')
    ->get();

echo "Nombre total de conversations actives: " . $conversations->count() . "\n\n";

foreach ($conversations as $conv) {
    echo "--------------------------------------------------\n";
    echo "Conversation ID: {$conv->id}\n";
    echo "Type: {$conv->type}\n";
    echo "App ID: {$conv->app_id}\n";
    echo "Créée par: {$conv->created_by}\n";
    echo "Status: {$conv->status}\n";
    echo "Mise à jour: {$conv->updated_at}\n\n";

    echo "Participants (" . $conv->participants->count() . "):\n";
    foreach ($conv->participants as $participant) {
        echo "  - User ID: {$participant->user_id} ({$participant->user->name})\n";
        echo "    Role: {$participant->role}\n";
        echo "    Joined at: {$participant->joined_at}\n";
        echo "    Last read: {$participant->last_read_at}\n";
        echo "    Unread count: {$participant->unread_count}\n";
    }

    echo "\nMessages (" . $conv->messages->count() . "):\n";
    $lastFive = $conv->messages->sortByDesc('created_at')->take(5);
    foreach ($lastFive as $msg) {
        echo "  - Message ID: {$msg->id}\n";
        echo "    Sender: {$msg->user_id} ({$msg->user->name})\n";
        echo "    Content: " . substr($msg->content, 0, 50) . "...\n";
        echo "    Created: {$msg->created_at}\n";
    }
    echo "\n";
}

// Vérifier les conversations spécifiques mentionnées dans les logs
echo "\n=== CONVERSATION 6 (abb client -> Maski) ===\n";
$conv6 = Conversation::where('id', 6)
    ->with(['participants.user', 'messages.user'])
    ->first();

if ($conv6) {
    echo "Type: {$conv6->type}\n";
    echo "App ID: {$conv6->app_id}\n";
    echo "Participants:\n";
    foreach ($conv6->participants as $p) {
        echo "  - User {$p->user_id} ({$p->user->name}): {$p->role}\n";
        echo "    Last read: {$p->last_read_at}\n";
        echo "    Unread: {$p->unread_count}\n";
    }
    echo "\nDerniers messages:\n";
    foreach ($conv6->messages->sortByDesc('created_at')->take(10) as $m) {
        echo "  [{$m->created_at}] {$m->user->name}: {$m->content}\n";
    }
} else {
    echo "Conversation 6 introuvable !\n";
}

echo "\n=== VÉRIFICATION DES UTILISATEURS ===\n";
$users = User::whereIn('id', [30])->get();
foreach ($users as $u) {
    echo "User {$u->id} ({$u->name}):\n";
    echo "  Email: {$u->email}\n";
    echo "  Conversations (default app): " . $u->conversationsForApp('default')->count() . "\n";
    echo "  Toutes les conversations: " . $u->conversations()->count() . "\n";
    echo "  Messages envoyés: " . $u->messages()->count() . "\n";
}

