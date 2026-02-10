<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ContactUpdated;
use App\Events\UserUpdated;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Trigger a sync event for a user.
     * This is called by the Wapback service when a user profile is updated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncEvent(Request $request, $id)
    {
        Log::info("[SyncController] Received sync event for user ID: {$id}");

        $user = User::find($id);

        if (!$user) {
            Log::warning("[SyncController] User not found for sync event: {$id}");
            return response()->json(['message' => 'User not found'], 404);
        }

        // Broadcast to the user themselves
        broadcast(new UserUpdated($user));
        Log::info("[SyncController] Broadcasted UserUpdated event for user ID: {$id}");

        // Broadcast to all conversation participants
        $conversations = $user->conversations()->with('participants')->get();
        $notifiedUserIds = [];

        foreach ($conversations as $conversation) {
            foreach ($conversation->participants as $participant) {
                // Skip the user themselves and already notified users
                if ($participant->user_id == $user->id || in_array($participant->user_id, $notifiedUserIds)) {
                    continue;
                }
                
                broadcast(new ContactUpdated($user, $participant->user_id));
                $notifiedUserIds[] = $participant->user_id;
            }
        }
        
        Log::info("[SyncController] Broadcasted ContactUpdated event to " . count($notifiedUserIds) . " participants");

        return response()->json(['message' => 'Sync event broadcasted']);
    }
}
