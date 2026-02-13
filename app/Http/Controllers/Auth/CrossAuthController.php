<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CrossAuthController extends Controller
{
    private string $wapBackendUrl;

    public function __construct()
    {
        $this->wapBackendUrl = env('WAP_BACKEND_URL', 'https://wapback.hellowap.com');
    }

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
            $userData = $wapUserData['data'] ?? $wapUserData['user'] ?? $wapUserData;

            if (!is_array($userData) || !isset($userData['id']) || !isset($userData['email'])) {
                Log::error('Cross-auth: Format de réponse WAP invalide', [
                    'response' => $wapUserData,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Format de réponse WAP invalide',
                ], 500);
            }

            $wapId = $userData['id'];
            $email = (string) $userData['email'];

            // Profil (intervenant/client)
            $profile = $userData['intervenant'] ?? $userData['client'] ?? null;

            // Avatar / Gender depuis profil
            $avatar = is_array($profile) ? ($profile['profile_photo_url'] ?? null) : null;
            $gender = is_array($profile) ? ($profile['sexe'] ?? null) : null;

            // Fallback avatar depuis photo_profil root
            if (!$avatar && !empty($userData['photo_profil'])) {
                $storageUrl = $this->wapBackendUrl . '/storage/';
                $photo = (string) $userData['photo_profil'];

                if (str_starts_with($photo, 'http')) {
                    $avatar = $photo;
                } else {
                    $avatar = $storageUrl . ltrim($photo, '/');
                }
            }

            /**
             * ✅ NAME RESOLUTION (ROBUSTE)
             * 1) userData.name si non vide
             * 2) userData.prenom + userData.nom si non vide
             * 3) profile.prenom + profile.nom si non vide (intervenant/client)
             * 4) email prefix
             * 5) Utilisateur {wapId}
             */
            $name = trim((string)($userData['name'] ?? ''));

            if ($name === '') {
                $prenom = trim((string)($userData['prenom'] ?? ''));
                $nom    = trim((string)($userData['nom'] ?? ''));
                $name = trim($prenom . ' ' . $nom);
            }

            if ($name === '' && is_array($profile)) {
                $prenom = trim((string)($profile['prenom'] ?? ''));
                $nom    = trim((string)($profile['nom'] ?? ''));
                $name = trim($prenom . ' ' . $nom);
            }

            if ($name === '' && $email) {
                $name = explode('@', $email)[0];
            }

            if ($name === '') {
                $name = 'Utilisateur ' . $wapId;
            }

            Log::info('Cross-auth: Données extraites', [
                'wap_user_id' => $wapId,
                'email' => $email,
                'name_resolved' => $name,
                'name_raw' => $userData['name'] ?? null,
                'profile_keys' => is_array($profile) ? array_keys($profile) : null,
                'avatar' => $avatar,
                'gender' => $gender,
            ]);

            /**
             * ✅ MATCH USER: d'abord par wap_user_id (source de vérité)
             * fallback par email si besoin
             */
            $localUser = User::where('wap_user_id', $wapId)->first();
            if (!$localUser) {
                $localUser = User::where('email', $email)->first();
            }

            // ✅ Ne pas écraser un name déjà correct par un name fallback "email prefix"
            $finalName = $name;
            if ($localUser && !empty($localUser->name)) {
                // si le nouveau nom est juste un prefix email, on évite de remplacer un vrai nom existant
                $emailPrefix = $email ? explode('@', $email)[0] : null;
                if ($emailPrefix && $finalName === $emailPrefix && trim($localUser->name) !== '') {
                    $finalName = $localUser->name;
                }
            }

            if ($localUser) {
                $localUser->email = $email;
                $localUser->wap_user_id = $wapId;
                $localUser->name = $finalName;
                $localUser->avatar = $avatar;
                $localUser->gender = $gender;

                // password random si vide uniquement
                if (empty($localUser->password)) {
                    $localUser->password = bcrypt(Str::random(32));
                }

                $localUser->save();
                $user = $localUser;
            } else {
                $user = User::create([
                    'email' => $email,
                    'wap_user_id' => $wapId,
                    'name' => $finalName,
                    'password' => bcrypt(Str::random(32)),
                    'avatar' => $avatar,
                    'gender' => $gender,
                ]);
            }

            Log::info('Cross-auth: Utilisateur mis à jour', [
                'id' => $user->id,
                'wap_user_id' => $user->wap_user_id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
            ]);

            // Tokens
            $deviceName = $request->input('device_name', 'wap-frontend');
            $user->tokens()->where('name', $deviceName)->delete();

            $token = $user->createToken($deviceName, ['*'], now()->addDays(7));

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
                        // compat front
                        'gender' => $user->gender,
                        'sexe' => $user->gender,
                    ],
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $token->accessToken->expires_at,
                ],
            ], 200);

        } catch (\Throwable $e) {
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
