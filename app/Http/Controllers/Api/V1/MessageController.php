<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Conversation;
use App\Http\Requests\StoreMessageRequest;
use App\Events\MessageDeleted;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Display a listing of messages for a conversation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $conversationId): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        $conversation = Conversation::where('app_id', $appId)
            ->where('id', $conversationId)
            ->firstOrFail();

        // Vérifier que l'utilisateur est participant
        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette conversation',
            ], 403);
        }

        // Validation des paramètres de pagination
        $validator = Validator::make($request->all(), [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'before' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $perPage = $request->input('per_page', 20);

        $query = $conversation->messages()
            ->where('app_id', $appId)
            ->with(['user'])
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc');

        // Filtrer avant une certaine date (pour le chargement infini)
        if ($request->has('before')) {
            $query->where('created_at', '<', $request->input('before'));
        }

        $messages = $query->paginate($perPage);

        // Inverser l'ordre pour avoir les messages du plus ancien au plus récent
        $messages->getCollection()->reverse();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ], 200);
    }

    /**
     * Store a newly created message.
     *
     * @param  \App\Http\Requests\StoreMessageRequest  $request
     * @param  int  $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreMessageRequest $request, $conversationId): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        Log::info('[MessageController] store() START', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
        ]);

        try {
            // Valider et récupérer la conversation
            $conversation = Conversation::where('app_id', $appId)
                ->where('id', $conversationId)
                ->firstOrFail();

            Log::info('[MessageController] Conversation validated', [
                'conversation_id' => $conversation->id,
            ]);

            // Vérifier que l'utilisateur est participant
            if (!$conversation->hasParticipant($user)) {
                Log::warning('[MessageController] User not participant', [
                    'user_id' => $user->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à envoyer des messages dans cette conversation',
                ], 403);
            }

            // Gérer l'upload de fichier si présent
            $fileUrl = null;
            $fileName = null;
            $fileSize = null;
            $duration = $request->input('duration');
            $messageType = $request->input('type', 'text');

            Log::info('[MessageController] File upload check', [
                'messageType' => $messageType,
                'hasFile' => $request->hasFile('file'),
                'content' => $request->input('content'),
                'content_length' => strlen($request->input('content')),
                'duration' => $duration,
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $mimeType = $file->getMimeType();
                $clientMimeType = $file->getClientMimeType();
                $extension = strtolower($file->getClientOriginalExtension());
                $requestType = $request->input('type'); // Type envoyé par le formulaire

                // Détecter si le fichier a été compressé côté frontend (nom contient '_compressed')
                $isCompressed = str_contains($file->getClientOriginalName(), '_compressed');

                Log::info('[MessageController] File received', [
                    'file' => $file ? 'exists' : 'null',
                    'original_name' => $file ? $file->getClientOriginalName() : 'N/A',
                    'size' => $file ? $file->getSize() : 'N/A',
                    'size_formatted' => $file ? round($file->getSize() / 1024 / 1024, 2) . ' Mo' : 'N/A',
                    'mime' => $mimeType,
                    'client_mime' => $clientMimeType,
                    'extension' => $extension,
                    'request_type' => $requestType,
                    'was_compressed' => $isCompressed,
                ]);

                // Déterminer le type de fichier et les règles de validation appropriées
                // Note: WebM audio peut être détecté comme video/webm par PHP Fileinfo
                // On utilise plusieurs critères: type demandé, extension, et MIME client
                $audioExtensions = ['webm', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'mp4'];
                $isAudio = $requestType === 'audio' 
                    || str_starts_with($clientMimeType, 'audio/') 
                    || (in_array($extension, $audioExtensions) && $requestType === 'audio');
                $isImage = str_starts_with($mimeType, 'image/') || str_starts_with($clientMimeType, 'image/');

                if ($isAudio) {
                    // Validation pour les fichiers audio
                    // Note: Le MediaRecorder génère audio/webm mais PHP Fileinfo peut le détecter comme video/webm
                    // On valide la taille uniquement car le type MIME n'est pas fiable pour WebM audio
                    $maxSize = 5 * 1024 * 1024; // 5MB en octets
                    
                    if ($file->getSize() > $maxSize) {
                        Log::warning('[MessageController] Audio file too large', [
                            'size' => $file->getSize(),
                            'max_size' => $maxSize,
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Erreur de validation du fichier audio',
                            'errors' => ['file' => ['Le fichier audio ne doit pas dépasser 5Mo']],
                        ], 422);
                    }

                    // Valider l'extension du fichier audio
                    $allowedAudioExtensions = ['webm', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'mp4'];
                    if (!in_array($extension, $allowedAudioExtensions)) {
                        Log::warning('[MessageController] Audio file extension not allowed', [
                            'extension' => $extension,
                            'allowed' => $allowedAudioExtensions,
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Erreur de validation du fichier audio',
                            'errors' => ['file' => ['Le fichier doit être un audio (mp3, wav, ogg, m4a, webm)']],
                        ], 422);
                    }
                    Log::info('Audio file info', [
                        'clientMimeType' => $file->getClientMimeType(),
                        'mimeType' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'size' => $file->getSize(),
                    ]);

                    Log::info('[MessageController] Audio file validated successfully', [
                        'extension' => $extension,
                        'size' => $file->getSize(),
                    ]);

                    $messageType = 'audio';
                } elseif ($isImage) {
                    // Validation pour les fichiers image
                    $validator = Validator::make(['file' => $file], [
                        'file' => [
                            'required',
                            'file',
                            'mimes:jpeg,jpg,png,gif,webp',
                            'max:5120', // 5MB max
                        ],
                    ], [
                        'file.mimes' => 'Le fichier doit être une image (jpeg, jpg, png, gif, webp)',
                        'file.max' => 'Le fichier ne doit pas dépasser 5Mo',
                    ]);

                    if ($validator->fails()) {
                        Log::warning('[MessageController] Image file validation failed', [
                            'errors' => $validator->errors()->toArray(),
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Erreur de validation du fichier image',
                            'errors' => $validator->errors(),
                        ], 422);
                    }

                    $messageType = 'image';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Type de fichier non supporté',
                    ], 422);
                }

                try {
                    // Déterminer le répertoire de stockage selon le type
                    if ($isAudio) {
                        $path = $file->store('chat-audios/' . date('Y/m'), 'public');
                    } else {
                        $path = $file->store('chat-images/' . date('Y/m'), 'public');
                    }
                    
                    Log::info('[MessageController] File stored', [
                        'path' => $path,
                        'disk' => 'public',
                        'type' => $isAudio ? 'audio' : 'image',
                    ]);

                    $fileUrl = Storage::url($path);
                    
                    Log::info('[MessageController] File URL generated', [
                        'url' => $fileUrl,
                    ]);

                    $fileName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                } catch (\Exception $storageError) {
                    Log::error('[MessageController] File storage EXCEPTION', [
                        'message' => $storageError->getMessage(),
                        'trace' => $storageError->getTraceAsString(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors du stockage du fichier',
                        'error' => config('app.debug') ? $storageError->getMessage() : null,
                    ], 500);
                }
            }

            Log::info('[MessageController] Starting database transaction', []);

            DB::beginTransaction();

            try {
                // Récupérer le contenu et le gérer (peut être null si image seule)
                $content = $request->input('content');
                if (empty($content) && !$fileUrl) {
                    Log::warning('[MessageController] No content and no file', []);
                    return response()->json([
                        'success' => false,
                        'message' => 'Veuillez saisir un message ou ajouter une image',
                    ], 422);
                }

                Log::info('[MessageController] Creating message', [
                    'content' => $content,
                    'type' => $messageType,
                    'file_url' => $fileUrl,
                    'user_id' => $user->id,
                ]);

                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                    'content' => $content ?? '', // Utiliser chaîne vide si null (pour images sans texte)
                    'type' => $messageType,
                    'file_url' => $fileUrl,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'duration' => $messageType === 'audio' ? $duration : null,
                    'app_id' => $appId,
                    'is_edited' => false,
                    'is_deleted' => false,
                ]);

                Log::info('[MessageController] Message created successfully', [
                    'message_id' => $message->id,
                    'content_length' => strlen($content),
                    'type' => $message->type,
                    'file_url' => $message->file_url,
                ]);

                // Mettre à jour le timestamp de la conversation
                $conversation->touch();

                // Mettre à jour le compteur de messages non lus pour les autres participants
                $conversation->participants()
                    ->where('user_id', '<>', $user->id)
                    ->increment('unread_count');

                DB::commit();

                Log::info('[MessageController] Database committed', [
                    'message_id' => $message->id,
                ]);

                // Charger les relations
                $message->load('user');

                // Diffuser l'événement WebSocket via Reverb (utilise SDK Pusher direct)
                try {
                    Log::info('[MessageController] Broadcasting message', [
                        'message_id' => $message->id,
                        'conversation_id' => $message->conversation_id,
                        'socket_id' => request()->header('X-Socket-ID'),
                    ]);

                    // Utiliser le SDK Pusher directement pour contourner Laravel broadcast()
                    $config = config('broadcasting.connections.reverb');
                    $pusher = new \Pusher\Pusher(
                        $config['key'],
                        $config['secret'],
                        $config['app_id'],
                        [
                            'host' => $config['options']['host'],
                            'port' => $config['options']['port'],
                            'scheme' => $config['options']['scheme'],
                            'useTLS' => $config['options']['scheme'] === 'https',
                        ]
                    );

                    $socketId = request()->header('X-Socket-ID');
                    
                    // Canaux à notifier : conversation + channel utilisateur pour chaque participant
                    $channels = ['private-conversation.' . $message->conversation_id . '.' . $appId];
                    
                    // Récupérer les IDs des participants pour notifier leurs channels personnels (pour la liste des conversations)
                    $participantIds = $conversation->participants()->pluck('user_id')->toArray();
                    foreach ($participantIds as $participantId) {
                        $channels[] = 'private-user.' . $participantId . '.' . $appId;
                    }
                    
                    $data = [
                        'message' => [
                            'id' => $message->id,
                            'conversation_id' => $message->conversation_id,
                            'user_id' => $message->user_id,
                            'content' => $message->content,
                            'type' => $message->type,
                            'file_url' => $message->file_url,
                            'file_name' => $message->file_name,
                            'file_size' => $message->file_size,
                            'duration' => $message->duration,
                            'is_edited' => $message->is_edited,
                            'edited_at' => $message->edited_at ? $message->edited_at->toIso8601String() : null,
                            'created_at' => $message->created_at->toIso8601String(),
                            'updated_at' => $message->updated_at->toIso8601String(),
                        ],
                        'sender' => [
                            'id' => $message->user->id,
                            'name' => $message->user->name,
                            'email' => $message->user->email,
                        ],
                    ];

                    $params = [];
                    if ($socketId) {
                        $params['socket_id'] = $socketId;
                    }

                    $pusher->trigger($channels, 'message.sent', $data, $params);

                    Log::info('[MessageController] Pusher broadcast sent successfully', [
                        'socket_id_used' => $socketId ?: 'none',
                        'channels' => $channels,
                    ]);
                } catch (\Exception $broadcastError) {
                    Log::error('[MessageController] Broadcast EXCEPTION', [
                        'message' => $broadcastError->getMessage(),
                        'trace' => $broadcastError->getTraceAsString(),
                    ]);
                    // Ne pas échouer la réponse si le broadcast échoue
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Message envoyé',
                    'data' => $message,
                ], 201);
            } catch (\Exception $dbError) {
                DB::rollBack();
                Log::error('[MessageController] Database EXCEPTION', [
                    'message' => $dbError->getMessage(),
                    'trace' => $dbError->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du message en base de données',
                    'error' => config('app.debug') ? $dbError->getMessage() : null,
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('[MessageController] Global EXCEPTION', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display specified message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @param  int  $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $conversationId, $messageId): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        $message = Message::where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->where('app_id', $appId)
            ->with(['user'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $message,
        ], 200);
    }

    /**
     * Update specified message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @param  int  $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $conversationId, $messageId): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        $message = Message::where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->where('app_id', $appId)
            ->firstOrFail();

        // Vérifier que l'utilisateur est l'auteur du message
        if ($message->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier ce message',
            ], 403);
        }

        // Vérifier que le message n'est PAS un message vocal (les messages audio ne peuvent pas être modifiés)
        if ($message->type === 'audio') {
            Log::warning('[MessageController] Attempt to edit audio message', [
                'message_id' => $message->id,
                'type' => $message->type,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Les messages vocaux ne peuvent pas être modifiés',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'content' => ['nullable', 'string', 'max:10000'],
            'file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $content = $validated['content'] ?? $message->content;

        // Vérifier qu'il y a au moins du contenu ou un fichier
        if (empty($content) && !$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez saisir un message ou ajouter une image',
            ], 422);
        }

        // Gérer l'upload d'un nouveau fichier si fourni
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            try {
                // Supprimer l'ancien fichier si existant
                if ($message->file_url) {
                    $oldFilePath = str_replace('/storage/', '', $message->file_url);
                    if (Storage::disk('public')->exists($oldFilePath)) {
                        Storage::disk('public')->delete($oldFilePath);
                        Log::info('[MessageController::update] Old file deleted', ['path' => $oldFilePath]);
                    }
                }

                // Stocker le nouveau fichier
                $path = Storage::disk('public')->putFile('chat-images/' . date('Y/m'), $file);
                $fileUrl = Storage::url($path);
                $fileName = $file->getClientOriginalName();
                $fileSize = $file->getSize();

                Log::info('[MessageController::update] New file uploaded', [
                    'path' => $path,
                    'url' => $fileUrl,
                    'size' => $fileSize,
                ]);

                // Mettre à jour le message
                $message->update([
                    'content' => $content,
                    'is_edited' => true,
                    'edited_at' => now(),
                    'file_url' => $fileUrl,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'type' => 'image',
                ]);
            } catch (\Exception $storageError) {
                Log::error('[MessageController::update] File storage EXCEPTION', [
                    'message' => $storageError->getMessage(),
                    'trace' => $storageError->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du stockage du fichier',
                ], 500);
            }
        } else {
            // Mise à jour sans fichier
            $message->update([
                'content' => $content,
                'is_edited' => true,
                'edited_at' => now(),
            ]);
        }

        $message->load('user');

        // Diffuser l'événement WebSocket message.edited
        try {
            $config = config('broadcasting.connections.reverb');
            $pusher = new \Pusher\Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                [
                    'host' => $config['options']['host'],
                    'port' => $config['options']['port'],
                    'scheme' => $config['options']['scheme'],
                    'useTLS' => $config['options']['scheme'] === 'https',
                ]
            );

            $socketId = request()->header('X-Socket-ID');
            $channelName = 'private-conversation.' . $conversationId . '.' . $appId;
            
            $data = [
                'message' => [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'user_id' => $message->user_id,
                    'content' => $message->content,
                    'type' => $message->type,
                    'file_url' => $message->file_url,
                    'file_name' => $message->file_name,
                    'file_size' => $message->file_size,
                    'is_edited' => $message->is_edited,
                    'edited_at' => $message->edited_at ? $message->edited_at->toIso8601String() : null,
                    'created_at' => $message->created_at->toIso8601String(),
                    'updated_at' => $message->updated_at->toIso8601String(),
                ],
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'app_id' => $appId,
            ];

            $params = [];
            if ($socketId) {
                $params['socket_id'] = $socketId;
            }

            $pusher->trigger($channelName, 'message.edited', $data, $params);

            Log::info('[MessageController::update] Broadcast message.edited', [
                'message_id' => $message->id,
                'channel' => $channelName
            ]);
        } catch (\Exception $broadcastError) {
            Log::error('[MessageController::update] Broadcast EXCEPTION', [
                'message' => $broadcastError->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message modifié',
            'data' => $message,
        ], 200);
    }

    /**
     * Remove specified message (soft delete).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @param  int  $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $conversationId, $messageId): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        Log::info('[MessageController] destroy() START', [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'user_id' => $user->id,
        ]);

        $message = Message::where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->where('app_id', $appId)
            ->firstOrFail();

        // Vérifier que l'utilisateur est l'auteur du message
        if ($message->user_id !== $user->id) {
            Log::warning('[MessageController] User not author of message', [
                'user_id' => $user->id,
                'message_user_id' => $message->user_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce message',
            ], 403);
        }

        // Marquer le message comme supprimé (soft delete)
        $message->markAsDeleted();

        Log::info('[MessageController] Message marked as deleted', [
            'message_id' => $message->id,
        ]);

        // Diffuser l'événement de suppression via WebSocket
        try {
            Log::info('[MessageController] Broadcasting message.deleted event', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'socket_id' => request()->header('X-Socket-ID'),
            ]);

            // Utiliser le SDK Pusher directement pour contourner Laravel broadcast()
            $config = config('broadcasting.connections.reverb');
            $pusher = new \Pusher\Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                [
                    'host' => $config['options']['host'],
                    'port' => $config['options']['port'],
                    'scheme' => $config['options']['scheme'],
                    'useTLS' => $config['options']['scheme'] === 'https',
                ]
            );

            $socketId = request()->header('X-Socket-ID');
            $channelName = 'private-conversation.' . $message->conversation_id . '.' . $appId;
            $data = [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'deleted_by' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'deleted_at' => now()->toIso8601String(),
            ];

            $params = [];
            if ($socketId) {
                $params['socket_id'] = $socketId;
            }

            $pusher->trigger($channelName, 'message.deleted', $data, $params);

            Log::info('[MessageController] message.deleted event broadcast successfully', [
                'socket_id_used' => $socketId ?: 'none',
                'channel' => $channelName,
            ]);
        } catch (\Exception $broadcastError) {
            Log::error('[MessageController] Broadcast delete event EXCEPTION', [
                'message' => $broadcastError->getMessage(),
                'trace' => $broadcastError->getTraceAsString(),
            ]);
            // Ne pas échouer la réponse si le broadcast échoue
        }

        return response()->json([
            'success' => true,
            'message' => 'Message supprimé',
        ], 200);
    }

    /**
     * Mark messages as read for conversation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $conversationId): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        $conversation = Conversation::where('app_id', $appId)
            ->where('id', $conversationId)
            ->firstOrFail();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette conversation',
            ], 403);
        }

        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages marqués comme lus',
        ], 200);
    }

    /**
     * Search messages within a conversation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, $conversationId): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        $conversation = Conversation::where('app_id', $appId)
            ->where('id', $conversationId)
            ->firstOrFail();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette conversation',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'q' => ['required', 'string', 'min:2'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $messages = $conversation->messages()
            ->where('app_id', $appId)
            ->where('is_deleted', false)
            ->search($request->input('q'))
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ], 200);
    }

    /**
     * Get typing users in a conversation (placeholder for real-time typing indicator).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function typingUsers(Request $request, $conversationId): JsonResponse
    {
        // Cette méthode peut être utilisée avec Reverb pour les indicateurs de frappe
        // Pour l'instant, elle retourne une liste vide
        return response()->json([
            'success' => true,
            'data' => [
                'typing' => [],
                'expires_in' => 5, // secondes
            ],
        ], 200);
    }

    /**
     * Handle typing indicator for a conversation.
     * Broadcasts the UserTyping event to notify other participants.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function typing(Request $request, $conversationId): JsonResponse
    {
        $user = $request->user();
        $appId = $request->header('X-Application-ID', 'default');

        // Récupérer is_typing et le convertir en boolean
        // Accepter: true, false, "true", "false", 1, 0, "1", "0"
        $isTypingInput = $request->input('is_typing');
        
        // Convertir en boolean de manière permissive
        if (is_bool($isTypingInput)) {
            $isTyping = $isTypingInput;
        } elseif (is_string($isTypingInput)) {
            $isTyping = in_array(strtolower($isTypingInput), ['true', '1', 'yes'], true);
        } elseif (is_numeric($isTypingInput)) {
            $isTyping = (bool) $isTypingInput;
        } else {
            $isTyping = false;
        }

        $conversation = Conversation::where('app_id', $appId)
            ->where('id', $conversationId)
            ->firstOrFail();

        // Vérifier que l'utilisateur est participant
        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette conversation',
            ], 403);
        }

        Log::info('[MessageController] typing() START', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'is_typing' => $isTyping,
        ]);

        // Diffuser l'événement de frappe via WebSocket
        try {
            Log::info('[MessageController] Broadcasting typing event', [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'is_typing' => $isTyping,
                'socket_id' => request()->header('X-Socket-ID'),
            ]);

            // Utiliser le SDK Pusher directement pour contourner Laravel broadcast()
            $config = config('broadcasting.connections.reverb');
            $pusher = new \Pusher\Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                [
                    'host' => $config['options']['host'],
                    'port' => $config['options']['port'],
                    'scheme' => $config['options']['scheme'],
                    'useTLS' => $config['options']['scheme'] === 'https',
                ]
            );

            $socketId = request()->header('X-Socket-ID');
            $channelName = 'private-conversation.' . $conversationId . '.' . $appId;
            $data = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'conversation_id' => $conversationId,
                'is_typing' => $isTyping,
                'app_id' => $appId,
            ];

            $params = [];
            if ($socketId) {
                $params['socket_id'] = $socketId;
            }

            $pusher->trigger($channelName, 'user.typing', $data, $params);

            Log::info('[MessageController] Typing event broadcast successfully', [
                'socket_id_used' => $socketId ?: 'none',
                'channel' => $channelName,
                'is_typing' => $isTyping,
            ]);
        } catch (\Exception $broadcastError) {
            Log::error('[MessageController] Broadcast typing event EXCEPTION', [
                'message' => $broadcastError->getMessage(),
                'trace' => $broadcastError->getTraceAsString(),
            ]);
            // Ne pas échouer la réponse si le broadcast échoue
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut de frappe mis à jour',
        ], 200);
    }
}
