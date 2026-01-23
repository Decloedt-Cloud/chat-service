<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller pour l'authentification croisée avec le backend WAP
 * Permet aux utilisateurs authentifiés sur WAP d'accéder au chat-service
 */
class CrossAuthController extends Controller
{
    /**
     * URL du backend WAP
     */
    private string $wapBackendUrl;

    public function __construct()
    {
        $this->wapBackendUrl = env('WAP_BACKEND_URL', 'https://wapback.hellowap.com');
    }

    /**
     * Authentification croisée avec le token WAP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticateWithWapToken(Request $request): JsonResponse
    {
        $wapToken = $request->input('wap_token') ?? $request->bearerToken();

        if (!$wapToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token WAP requis',
            ], 400);
        }

        try {
            // Vérifier le token auprès du backend WAP
            $response = Http::withToken($wapToken)
                ->timeout(10)
                ->get("{$this->wapBackendUrl}/api/user");

            if (!$response->successful()) {
                Log::warning('Cross-auth: Token WAP invalide', [
                    'status' => $response->status(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Token WAP invalide ou expiré',
                ], 401);
            }

            $wapUserData = $response->json();
            
            // Extraire les données utilisateur (gérer différents formats de réponse)
            $userData = $wapUserData['data'] ?? $wapUserData['user'] ?? $wapUserData;
            
            Log::info('Cross-auth: Données reçues de WAP', [
                'has_intervenant' => isset($userData['intervenant']),
                'has_client' => isset($userData['client']),
                'keys' => array_keys($userData),
                'intervenant_data' => $userData['intervenant'] ?? 'null',
                'client_data' => $userData['client'] ?? 'null',
            ]);

            if (!isset($userData['id']) || !isset($userData['email'])) {
                Log::error('Cross-auth: Format de réponse WAP invalide', [
                    'response' => $wapUserData,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Format de réponse WAP invalide',
                ], 500);
            }

            // Déterminer les informations supplémentaires (avatar, genre) depuis le profil (Intervenant ou Client)
            $profile = $userData['intervenant'] ?? $userData['client'] ?? null;
            $avatar = $profile['profile_photo_url'] ?? null;
            $gender = $profile['sexe'] ?? null;

            // Fallback: Si pas d'avatar dans le profil, essayer de le construire depuis le user
            if (!$avatar && !empty($userData['photo_profil'])) {
                $storageUrl = $this->wapBackendUrl . '/storage/';
                // Si le chemin commence déjà par http, l'utiliser tel quel, sinon préfixer
                if (str_starts_with($userData['photo_profil'], 'http')) {
                    $avatar = $userData['photo_profil'];
                } else {
                    $avatar = $storageUrl . ltrim($userData['photo_profil'], '/');
                    // Gestion spécifique pour les attachments intervenant/client si nécessaire
                    // Mais généralement photo_profil dans User est le chemin relatif
                }
            }
            
            Log::info('Cross-auth: Données extraites', [
                'avatar' => $avatar,
                'gender' => $gender,
                'email' => $userData['email']
            ]);

            // Trouver ou créer l'utilisateur dans le chat-service
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'] ?? $userData['prenom'] ?? $userData['email'],
                    'wap_user_id' => $userData['id'],
                    'password' => bcrypt(Str::random(32)), // Password aléatoire (non utilisé)
                    'avatar' => $avatar,
                    'gender' => $gender,
                ]
            );

            Log::info('Cross-auth: Utilisateur mis à jour', $user->toArray());

            // Supprimer les anciens tokens de cet appareil
            $deviceName = $request->input('device_name', 'wap-frontend');
            $user->tokens()->where('name', $deviceName)->delete();

            // Créer un nouveau token pour le chat-service
            $token = $user->createToken($deviceName, ['*'], now()->addDays(7));

            Log::info('Cross-auth: Authentification réussie', [
                'wap_user_id' => $userData['id'],
                'chat_user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Authentification croisée réussie',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'wap_user_id' => $user->wap_user_id,
                        'avatar' => $user->avatar,
                        'sexe' => $user->sexe,
                    ],
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Cross-auth: Erreur lors de la vérification du token WAP', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du token WAP',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Vérifie si un token chat-service est valide
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token valide',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'wap_user_id' => $user->wap_user_id,
                ],
            ],
        ], 200);
    }
}

