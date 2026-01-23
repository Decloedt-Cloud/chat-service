<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Events\MessageRead;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    /**
     * Display a listing of the conversations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Récupérer le app_id depuis le header ou utiliser 'default'
        $appId = $request->header('X-Application-ID', 'default');

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
                    }
                } else {
                    $conversation->display_name = $conversation->name;
                    $conversation->display_avatar = $conversation->avatar;
                }

                // Add participants count
                $conversation->participants_count = $conversation->participants->count();

                return $conversation;
            })
            ->sortByDesc('updated_at')
            ->values()
            ->slice(0, 20);

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ], 200);
    }

    /**
     * Store a newly created conversation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        // Pré-traitement des participants : Création à la volée des utilisateurs manquants
        if ($request->has('participant_ids') && is_array($request->input('participant_ids'))) {
            foreach ($request->input('participant_ids') as $wapId) {
                // On vérifie si l'ID est numérique (ID WAP)
                if (is_numeric($wapId)) {
                    // Vérifier si l'utilisateur existe déjà (par ID local ou WAP ID)
                    $exists = User::where('id', $wapId)
                        ->orWhere('wap_user_id', $wapId)
                        ->exists();

                    if (!$exists) {
                        // Création d'un utilisateur placeholder pour permettre la conversation
                        // Il sera mis à jour lors de sa première connexion via CrossAuth
                        try {
                            User::create([
                                'wap_user_id' => $wapId,
                                'name' => 'Utilisateur ' . $wapId,
                                'email' => 'wap_user_' . $wapId . '@temp.local',
                                'password' => bcrypt(Str::random(32)),
                                'avatar' => null, // Sera mis à jour plus tard
                                'gender' => 'Homme', // Valeur par défaut
                            ]);
                        } catch (\Exception $e) {
                            // Ignorer l'erreur si création concurrente (race condition)
                        }
                    }
                }
            }
        }

        // Validation personnalisée pour accepter les ID WAP ou les ID locaux
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(['direct', 'group'])],
            'name' => ['required_if:type,group', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => [
                function ($attribute, $value, $fail) {
                    // Rechercher par ID local ou par wap_user_id
                    $exists = User::where('id', $value)
                        ->orWhere('wap_user_id', $value)
                        ->exists();
                   
                    if (!$exists) {
                        $fail("Le participant avec l'ID {$value} n'existe pas.");
                    }
                },
            ],
        ], [
            'type.required' => 'Le type de conversation est requis',
            'type.in' => 'Le type doit être "direct" ou "group"',
            'name.required_if' => 'Le nom est requis pour les conversations de groupe',
            'participant_ids.required' => 'Au moins un participant est requis',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Pour les conversations directes, vérifier si elle existe déjà
        if ($validated['type'] === 'direct' && count($validated['participant_ids']) === 1) {
            $otherUserIdValue = $validated['participant_ids'][0];

            // Rechercher l'utilisateur par ID local en priorité
            $otherUser = User::where('id', $otherUserIdValue)->first();
           
            // Si l'utilisateur trouvé est soi-même, vérifier si ce n'est pas plutôt un ID WAP destiné à un autre utilisateur
            // (Cas de collision : Mon ID local = 30, et je veux contacter le WAP ID 30 qui est quelqu'un d'autre)
            if ($otherUser && $otherUser->id == $user->id) {
                $otherUserByWap = User::where('wap_user_id', $otherUserIdValue)->first();
                if ($otherUserByWap && $otherUserByWap->id != $user->id) {
                    $otherUser = $otherUserByWap;
                }
            }
           
            // Sinon par wap_user_id si non trouvé par ID local
            if (!$otherUser) {
                $otherUser = User::where('wap_user_id', $otherUserIdValue)->first();
            }

            if (!$otherUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le participant spécifié n\'existe pas',
                ], 404);
            }

            // Ne pas créer de conversation avec soi-même
            if ($otherUser->id == $user->id || $otherUser->wap_user_id == $user->wap_user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de créer une conversation avec soi-même',
                ], 400);
            }

            $existingConversation = $user->directConversationWith(
                $otherUser,
                $appId
            );

            if ($existingConversation) {
                // Mettre à jour la date de modification pour que la conversation remonte en premier
                $existingConversation->touch();

                return response()->json([
                    'success' => true,
                    'message' => 'Conversation existante récupérée',
                    'data' => $existingConversation->load('participants.user', 'lastMessage'),
                ], 200);
            }

            $conversation = $user->getOrCreateDirectConversationWith(
                $otherUser,
                $appId
            );

            // Charger les relations nécessaires
            $conversation->load('participants.user', 'lastMessage');

            // Pour les conversations directes, définir display_name
            if ($conversation->type === 'direct') {
                $otherParticipant = $conversation->participants
                    ->firstWhere('user_id', '!=', $user->id);

                if ($otherParticipant && $otherParticipant->user) {
                    $conversation->display_name = $otherParticipant->user->name;
                    $conversation->display_avatar = $otherParticipant->user->avatar ?? null;
                }
            }

            // Compter les participants
            $conversation->participants_count = $conversation->participants->count();

            return response()->json([
                'success' => true,
                'message' => 'Conversation directe créée',
                'data' => $conversation,
            ], 201);
        }

        // Création d'un groupe
        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'avatar' => $validated['avatar'] ?? null,
            'created_by' => $user->id,
            'app_id' => $appId,
            'status' => 'active',
        ]);

        // Ajouter le créateur comme owner
        $conversation->participants()->create([
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        // Ajouter les autres participants
        foreach ($validated['participant_ids'] as $participantIdValue) {
            // Rechercher l'utilisateur par ID local en priorité
            $participant = User::where('id', $participantIdValue)->first();

            // Sinon par wap_user_id
            if (!$participant) {
                $participant = User::where('wap_user_id', $participantIdValue)->first();
            }

            if ($participant && $participant->id != $user->id) {
                $conversation->participants()->create([
                    'user_id' => $participant->id,
                    'role' => 'member',
                ]);
            }
        }

        $conversation->load('participants.user', 'creator');

        return response()->json([
            'success' => true,
            'message' => 'Conversation de groupe créée',
            'data' => $conversation,
        ], 201);
    }

    /**
     * Display the specified conversation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        $conversation = Conversation::where('app_id', $appId)
            ->where('id', $id)
            ->with(['participants.user', 'creator', 'lastMessage.user'])
            ->firstOrFail();

        // Vérifier que l'utilisateur est participant
        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette conversation',
            ], 403);
        }

        // Calculer les messages non lus
        $conversation->unread_count = $conversation->getUnreadCountForUser($user);

        // Pour les conversations directes, afficher l'autre utilisateur
        if ($conversation->type === 'direct') {
            $otherParticipant = $conversation->participants
                ->firstWhere('user_id', '!=', $user->id);

            if ($otherParticipant && $otherParticipant->user) {
                $conversation->display_name = $otherParticipant->user->name;
                $conversation->display_avatar = $otherParticipant->user->avatar ?? null;
            }
        } else {
            $conversation->display_name = $conversation->name;
            $conversation->display_avatar = $conversation->avatar;
        }

        // Ajouter le nombre de participants
        $conversation->participants_count = $conversation->participants->count();

        return response()->json([
            'success' => true,
            'data' => $conversation,
        ], 200);
    }

    /**
     * Mark a conversation as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        $conversation = Conversation::where('app_id', $appId)
            ->where('id', $id)
            ->firstOrFail();

        // Vérifier que l'utilisateur est participant
        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas participant de cette conversation',
            ], 403);
        }

        // Mettre à jour le last_read_at ET unread_count du participant
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            // ✅ CORRECTION: Utiliser markAsRead() pour reset unread_count à 0
            $participant->markAsRead();
           
            // Recharger pour avoir last_read_at à jour
            $participant->refresh();

            \Log::info('[ConversationController] Conversation marked as read', [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'read_at' => $participant->last_read_at,
                'unread_count' => $participant->unread_count,
            ]);

            // ✅ BROADCASTER l'événement MessageRead via Pusher SDK directement (plus fiable avec Reverb)
            try {
                $pusher = new \Pusher\Pusher(
                    config('broadcasting.connections.reverb.key'),
                    config('broadcasting.connections.reverb.secret'),
                    config('broadcasting.connections.reverb.app_id'),
                    [
                        'host' => config('broadcasting.connections.reverb.options.host'),
                        'port' => config('broadcasting.connections.reverb.options.port'),
                        'scheme' => config('broadcasting.connections.reverb.options.scheme'),
                        'encrypted' => config('broadcasting.connections.reverb.options.useTLS'),
                        'useTLS' => config('broadcasting.connections.reverb.options.useTLS'),
                    ]
                );

                $channelName = 'private-conversation.' . $conversation->id . '.' . $appId;
                $eventName = 'message.read';
                $eventData = [
                    'conversation_id' => $conversation->id,
                    'reader' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'read_at' => $participant->last_read_at->toIso8601String(),
                ];

                \Log::info('[ConversationController] Broadcasting MessageRead via Pusher SDK', [
                    'channel' => $channelName,
                    'event' => $eventName,
                    'data' => $eventData,
                    'pusher_config' => [
                        'key' => config('broadcasting.connections.reverb.key'),
                        'app_id' => config('broadcasting.connections.reverb.app_id'),
                        'host' => config('broadcasting.connections.reverb.options.host'),
                        'port' => config('broadcasting.connections.reverb.options.port'),
                    ],
                ]);

                $result = $pusher->trigger($channelName, $eventName, $eventData);
               
                \Log::info('[ConversationController] MessageRead event broadcasted successfully', [
                    'result' => $result ? 'success' : 'failed',
                ]);
            } catch (\Exception $broadcastError) {
                \Log::error('[ConversationController] Failed to broadcast MessageRead: ' . $broadcastError->getMessage(), [
                    'exception' => $broadcastError,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation marquée comme lue',
        ], 200);
    }
}