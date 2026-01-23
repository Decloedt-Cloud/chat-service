<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\CrossAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Public
|--------------------------------------------------------------------------
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Chat Service API',
        'version' => '1.0.0',
    ]);
});

// Health check avec prefix v1 (pour compatibilité)
Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'Chat Service API',
            'version' => '1.0.0',
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| API Routes - Authentication
|--------------------------------------------------------------------------
*/
Route::post('/auth/login', [AuthController::class, 'login']);

// Authentification croisée avec le token WAP
Route::post('/auth/cross-auth', [CrossAuthController::class, 'authenticateWithWapToken']);

/*
|--------------------------------------------------------------------------
| API Routes V1 - Public / Internal
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Webhook pour la synchronisation depuis Wapback (Internal Use)
    Route::post('/users/{id}/sync-event', [\App\Http\Controllers\Api\V1\SyncController::class, 'syncEvent']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Protected (requires Bearer Token)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentication endpoints
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // User info shortcut
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

/*
|--------------------------------------------------------------------------
| API Routes V1 - Chat Service
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Conversations
    Route::apiResource('conversations', 'App\Http\Controllers\Api\V1\ConversationController');

    // Conversation management
    Route::post('/conversations/{conversation}/participants', 'App\Http\Controllers\Api\V1\ConversationController@addParticipants');
    Route::delete('/conversations/{conversation}/participants/{user}', 'App\Http\Controllers\Api\V1\ConversationController@removeParticipant');
    Route::post('/conversations/{conversation}/leave', 'App\Http\Controllers\Api\V1\ConversationController@leave');

    // Messages
    Route::get('/conversations/{conversation}/messages', 'App\Http\Controllers\Api\V1\MessageController@index');
    Route::post('/conversations/{conversation}/messages', 'App\Http\Controllers\Api\V1\MessageController@store');
    Route::get('/conversations/{conversation}/messages/{message}', 'App\Http\Controllers\Api\V1\MessageController@show');
    Route::put('/conversations/{conversation}/messages/{message}', 'App\Http\Controllers\Api\V1\MessageController@update');
    Route::delete('/conversations/{conversation}/messages/{message}', 'App\Http\Controllers\Api\V1\MessageController@destroy');

    // Message actions
Route::post('/conversations/{conversation}/read', 'App\Http\Controllers\Api\V1\ConversationController@read');
Route::get('/conversations/{conversation}/messages/search', 'App\Http\Controllers\Api\V1\MessageController@search');
Route::get('/conversations/{conversation}/typing', 'App\Http\Controllers\Api\V1\MessageController@typingUsers');
Route::post('/conversations/{conversation}/typing', 'App\Http\Controllers\Api\V1\MessageController@typing');

    // Users
    Route::get('/users', 'App\Http\Controllers\Api\V1\UserController@index');
    Route::get('/users/{user}', 'App\Http\Controllers\Api\V1\UserController@show');

    // Broadcasting authentication (for WebSocket)
    Route::post('/broadcasting/auth', 'App\Http\Controllers\Api\V1\BroadcastingController@authenticate');
});
});

