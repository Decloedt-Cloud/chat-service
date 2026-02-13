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
                'keys' => is_array($userData) ? array_keys($userData) : [],
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

            // avatar: prioritaire depuis profile_photo_url
            $avatar = is_array($profile) ? ($profile['profile_photo_url'] ?? null) : null;

            // gender: le champ "sexe" (WAP) est stocké dans "gender" côté chat-service
            $gender = is_array($profile) ? ($profile['sexe'] ?? null) : null;

            // Fallback avatar: depuis photo_profil user
            if (!$avatar && !empty($userData['photo_profil'])) {
                $storageUrl = $this->wapBackendUrl . '/storage/';

                if (str_starts_with((string)$userData['photo_profil'], 'http')) {
                    $avatar = $userData['photo_profil'];
                } else {
                    $avatar = $storageUrl . ltrim((string)$userData['photo_profil'], '/');
                }
            }

            // --- Resolve name safely (handles empty string too) ---
            $name = trim((string)($userData['name'] ?? ''));

            // si name vide, fallback sur prenom/nom
            if ($name === '') {
                $prenom = trim((string)($userData['prenom'] ?? ''));
                $nom    = trim((string)($userData['nom'] ?? ''));
                $name = trim($prenom . ' ' . $nom);
            }

            // si toujours vide, fallback sur email prefix ou "Utilisateur {id}"
            if ($name === '') {
                $email = (string)($userData['email'] ?? '');
                $name = $email ? explode('@', $email)[0] : ('Utilisateur ' . ($userData['id'] ?? ''));
            }

            Log::info('Cross-auth: Données extraites', [
                'wap_user_id' => $userData['id'],
                'email' => $userData['email'],
                'name_resolved' => $name,
                'name_raw' => $userData['name'] ?? null,
                'avatar' => $avatar,
                'gender' => $gender,
            ]);

            // Trouver ou créer l'utilisateur dans le chat-service
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $name,
                    'wap_user_id' => $userData['id'],
                    'password' => bcrypt(Str::random(32)), // Password aléatoire (non utilisé)
                    'avatar' => $avatar,
                    'gender' => $gender,
                ]
            );

            Log::info('Cross-auth: Utilisateur mis à jour', [
                'id' => $user->id,
                'wap_user_id' => $user->wap_user_id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
            ]);

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
                        // On renvoie aussi "sexe" pour compatibilité front si besoin
                        'gender' => $user->gender,
                        'sexe' => $user->gender,
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
                    'avatar' => $user->avatar,
                    'gender' => $user->gender,
                ],
            ],
        ], 200);
    }
}
