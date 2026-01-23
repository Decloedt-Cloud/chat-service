<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class EnsureApplicationIsValid
{
    /**
     * Liste des applications autorisées (app_id => nom)
     *
     * @var array<string, string>
     */
    protected $allowedApps = [
        'default' => 'Chat Service Default',
        'web-app' => 'Web Application',
        'mobile-app' => 'Mobile Application',
        'admin-panel' => 'Admin Panel',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Autoriser les requêtes OPTIONS (preflight CORS) sans vérification
        if ($request->method() === 'OPTIONS') {
            return $next($request);
        }

        // Récupérer l'application ID depuis le header ou utiliser 'default'
        $appId = $request->header('X-Application-ID', 'default');

        // Vérifier si l'application est autorisée
        if (!isset($this->allowedApps[$appId])) {
            return response()->json([
                'success' => false,
                'message' => 'Application non autorisée',
                'errors' => [
                    'application_id' => [
                        "L'application avec ID '{$appId}' n'est pas autorisée"
                    ]
                ]
            ], 403);
        }

        // Stocker l'app_id dans la requête pour utilisation ultérieure
        $request->merge(['app_id' => $appId]);

        return $next($request);
    }
}
