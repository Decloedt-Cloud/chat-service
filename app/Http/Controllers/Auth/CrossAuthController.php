<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
            Log::info('Cross-auth: HIT authenticateWithWapToken', [
                'has_bearer' => (bool) $request->bearerToken(),
                'has_wap_token_input' => $request->filled('wap_token'),
            ]);

            $response = Http::withToken($wapToken)
                ->timeout(10)
                ->get("{$this->wapBackendUrl}/api/user");

            if (!$response->successful()) {
                Log::warning('Cross-auth: Token WAP invalide', [
                    'status' => $response->status(),
                    'body' => $response->body(),
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

            $wapId = (int) $userData['id'];
            $email = (string) $userData['email'];

            // Profil (intervenant/client) depuis API
            $profile = $userData['intervenant'] ?? $userData['client'] ?? null;

            // Avatar / Gender depuis profil API
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
             * ✅ NAME RESOLUTION
             * 1) API user.name
             * 2) API user.prenom + user.nom
             * 3) API profile.prenom + profile.nom (intervenant/client)
             * 4) DB wapp.users.name (source fiable)
             * 5) DB wapp.intervenants / wapp.clients prenom+nom
             * 6) email prefix
             * 7) Utilisateur {wapId}
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

            // ✅ FALLBACK DB (wapp) si toujours vide
            if ($name === '') {
                $dbName = $this->resolveNameFromWappDb($wapId, $email);
                if ($dbName) {
                    $name = $dbName;
                }
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

            // ✅ Ne pas écraser un "vrai nom" par un fallback faible
            $finalName = $name;
            if ($localUser && trim((string)$localUser->name) !== '') {
                $emailPrefix = $email ? explode('@', $email)[0] : null;
                if ($emailPrefix && $finalName === $emailPrefix) {
                    $finalName = $localUser->name;
                }
            }

            if ($localUser) {
                $localUser->email = $email;
                $localUser->wap_user_id = $wapId;
                $localUser->name = $finalName;
                $localUser->avatar = $avatar;
                $localUser->gender = $gender;

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

    /**
     * Résout le nom depuis la base WAPP (connexion mysql_wapp).
     */
    private function resolveNameFromWappDb(int $wapId, string $email): ?string
    {
        try {
            // 1) wapp.users.name
            $u = DB::connection('mysql_wapp')
                ->table('users')
                ->select('id', 'name', 'email')
                ->where('id', $wapId)
                ->orWhere('email', $email)
                ->first();

            if ($u && !empty($u->name)) {
                return trim((string) $u->name);
            }

            // 2) intervenants prenom + nom (si user_id = wapId)
            $i = DB::connection('mysql_wapp')
                ->table('intervenants')
                ->select('prenom', 'nom')
                ->where('user_id', $wapId)
                ->first();

            if ($i) {
                $full = trim(trim((string)$i->prenom) . ' ' . trim((string)$i->nom));
                if ($full !== '') return $full;
            }

            // 3) clients prenom + nom (si table existe)
            // ⚠️ si ta table s'appelle autrement, change ici
            if (Schema::connection('mysql_wapp')->hasTable('clients')) {
                $c = DB::connection('mysql_wapp')
                    ->table('clients')
                    ->select('prenom', 'nom')
                    ->where('user_id', $wapId)
                    ->first();

                if ($c) {
                    $full = trim(trim((string)$c->prenom) . ' ' . trim((string)$c->nom));
                    if ($full !== '') return $full;
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('Cross-auth: resolveNameFromWappDb failed', [
                'wap_user_id' => $wapId,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
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
