<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "Start script...\n";

// Maski AYMEN (WAP ID 20)
$user = User::where('wap_user_id', 20)->first();

if (!$user) {
    echo "User maski (wap_id 20) not found. Trying first user.\n";
    $user = User::first();
}

if (!$user) {
    die("No users found in DB.\n");
}

echo "Testing for user: {$user->name} (ID: {$user->id}, WAP ID: {$user->wap_user_id})\n";

$appId = 'default';

$conversations = $user->conversationsForApp($appId)
    ->with(['lastMessage.user', 'participants.user', 'creator'])
    ->get()
    ->map(function ($conversation) use ($user) {
        // Calculate unread count
        $conversation->unread_count = $conversation->getUnreadCountForUser($user);

        // Set display name and avatar for direct conversations
        if ($conversation->type === 'direct') {
            $otherParticipant = $conversation->participants
                ->firstWhere('user_id', '!=', $user->id);

            if ($otherParticipant && $otherParticipant->user) {
                $conversation->display_name = $otherParticipant->user->name;
                $conversation->display_avatar = $otherParticipant->user->avatar ?? null;
                // J'ajoute ceci pour voir si on peut récupérer le genre
                $conversation->debug_other_user_gender = $otherParticipant->user->gender;
                $conversation->debug_other_user_sexe = $otherParticipant->user->sexe; // Accessor
            }
        } else {
            $conversation->display_name = $conversation->name;
            $conversation->display_avatar = $conversation->avatar;
        }

        return $conversation;
    })
    ->sortByDesc('updated_at')
    ->values()
    ->slice(0, 5); // Just first 5

foreach ($conversations as $conv) {
    echo "Conv ID: {$conv->id} ({$conv->type})\n";
    echo "  Display Name: {$conv->display_name}\n";
    echo "  Display Avatar: {$conv->display_avatar}\n";
    
    if ($conv->type === 'direct') {
        $other = $conv->participants->firstWhere('user_id', '!=', $user->id);
        if ($other && $other->user) {
            echo "  Other User ID: {$other->user->id}\n";
            echo "  Other User Gender (DB): {$other->user->gender}\n";
            echo "  Other User Sexe (Accessor): {$other->user->sexe}\n";
        }
    }
    echo "------------------------------------------------\n";
}
