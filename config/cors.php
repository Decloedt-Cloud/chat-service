<?php

return [

    /*

    |--------------------------------------------------------------------------

    | Cross-Origin Resource Sharing (CORS) Configuration

    |--------------------------------------------------------------------------

    */

    /*

     * Les routes concernées par le CORS

     * - api/* : API REST

     * - sanctum/csrf-cookie : si auth cookie

     * - broadcasting/auth : Reverb / WebSocket auth

     */

    'paths' => [

        'api/*',

        'sanctum/csrf-cookie',

        'broadcasting/auth',

    ],

    /*

     * Méthodes HTTP autorisées

     */

    'allowed_methods' => [

        'GET',

        'POST',

        'PUT',

        'PATCH',

        'DELETE',

        'OPTIONS',

    ],

    /*

     * ORIGINES AUTORISÉES (⚠️ explicites car credentials = true)

     */

    'allowed_origins' => [

        // Local

        'http://localhost:3000',

        'http://localhost:5173',

        'http://localhost:5174',

        'http://127.0.0.1:3000',

        'http://127.0.0.1:5173',

        'http://127.0.0.1:5174',

        'http://localhost:8000',

        'http://localhost:8001',

        // DEV / PREPROD

        'https://dev.hellowap.com',

        'https://preprod.hellowap.com',

        'https://wapback.com', // Backend

    ],

    /*

     * Pas besoin ici (on reste strict)

     */

    'allowed_origins_patterns' => [],

    /*

     * Headers autorisés depuis le frontend

     */

    'allowed_headers' => [

        'Content-Type',

        'Authorization',

        'X-Requested-With',

        'Accept',

        'Origin',

        'X-CSRF-TOKEN',

        'X-Application-ID',

        'X-Socket-ID',

    ],

    /*

     * Headers exposés au frontend (optionnel)

     */

    'exposed_headers' => [

        'X-Socket-ID',

    ],

    /*

     * Cache du preflight (OPTIONS) — 24h

     */

    'max_age' => 86400,

    /*

     * OBLIGATOIRE pour cookies / auth cross-domain

     */

    'supports_credentials' => true,

];
 