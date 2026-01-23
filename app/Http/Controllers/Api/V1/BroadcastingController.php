<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConversationParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BroadcastingController extends Controller
{
    /**
     * Authenticate request for channel access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request): JsonResponse
    {
        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');
        $user = $request->user();

        Log::info('[Broadcasting Auth] Request received', [
            'channel_name' => $channelName,
            'socket_id' => $socketId,
            'user_id' => $user ? $user->id : null,
        ]);

        if (!$channelName || !$socketId) {
            Log::warning('[Broadcasting Auth] Missing channel_name or socket_id');
            return response()->json([
                'error' => 'Missing channel_name or socket_id',
            ], 400);
        }

        if (!$user) {
            Log::warning('[Broadcasting Auth] User not authenticated');
            return response()->json([
                'error' => 'User not authenticated',
            ], 401);
        }

        $channelData = null;

        // Parse channel name: private-conversation.{conversationId}.{appId}
        if (preg_match('/^private-conversation\.(\d+)\.(.+)$/', $channelName, $matches)) {
            $conversationId = (int) $matches[1];
            $appId = $matches[2];

            Log::info('[Broadcasting Auth] Parsed channel', [
                'conversation_id' => $conversationId,
                'app_id' => $appId,
            ]);

            // Check if user is participant of this conversation
            $isParticipant = ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$isParticipant) {
                Log::warning('[Broadcasting Auth] User is not participant', [
                    'user_id' => $user->id,
                    'conversation_id' => $conversationId,
                ]);
                return response()->json([
                    'error' => 'You are not authorized to access this channel',
                ], 403);
            }

            Log::info('[Broadcasting Auth] User is authorized');
        } 
        // Parse presence channel: presence-chat.{appId}
        else if (preg_match('/^presence-chat\.(.+)$/', $channelName, $matches)) {
            $appId = $matches[1];
            
            // Channel data for presence channel (must be JSON string)
            $channelData = json_encode([
                'user_id' => (string) $user->id,
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'app_id' => $appId
                ]
            ]);

            Log::info('[Broadcasting Auth] Presence channel authorized', [
                'app_id' => $appId,
                'user_id' => $user->id
            ]);
        }
        else if (preg_match('/^private-user\.(.+)\.(.+)$/', $channelName, $matches)) {
             // Basic user channel authorization
             $targetUserId = $matches[1];
             if ((string)$user->id !== (string)$targetUserId) {
                 return response()->json(['error' => 'Unauthorized'], 403);
             }
        }

        // Generate Pusher-compatible auth signature
        try {
            $key = config('reverb.apps.apps.0.key', env('REVERB_APP_KEY', 'iuvcjjlml7xkwbdfaxo3'));
            $secret = config('reverb.apps.apps.0.secret', env('REVERB_APP_SECRET', 'iuvcjjlml7xkwbdfaxo3'));

            // For private channels, we need to generate a signature
            $stringToSign = $socketId . ':' . $channelName;
            
            // If presence channel, append channel data to signature string
            if ($channelData) {
                $stringToSign .= ':' . $channelData;
            }

            $signature = hash_hmac('sha256', $stringToSign, $secret);
            $auth = $key . ':' . $signature;

            Log::info('[Broadcasting Auth] Auth signature generated successfully');

            $response = ['auth' => $auth];
            if ($channelData) {
                $response['channel_data'] = $channelData;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('[Broadcasting Auth] Error generating signature', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Authentication failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
