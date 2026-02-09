# Guide Complet du Service de Chat Laravel

🚀 **Guide étape par étape pour créer un service de chat centralisé et prêt pour la production**

---

## 📋 Table des Matières

1. [Introduction](#introduction)
2. [ÉTAPE 1 — Initialisation du Projet](#étape-1-–-initialisation-du-projet)
3. [ÉTAPE 2 — Authentification (API)](#étape-2-–-authentification-api)
4. [ÉTAPE 3 — Modélisation Base de Données](#étape-3-–-modélisation-base-de-données)
5. [ÉTAPE 4 — API REST (Chat)](#étape-4-–-api-rest-chat)
6. [ÉTAPE 5 — Temps Réel (Laravel Reverb)](#étape-5-–-temps-réel-laravel-reverb)
7. [ÉTAPE 6 — Sécurité et Bonnes Pratiques](#étape-6-–-sécurité-et-bonnes-pratiques)
8. [Déploiement et Production](#déploiement-et-production)
9. [Annexes](#annexes)

---

## Introduction

### Contexte

Ce projet est un **service de chat centralisé** utilisant Laravel, conçu pour être intégré dans plusieurs applications frontend. Il offre:

- ✅ API REST complète pour toutes les opérations de chat
- ✅ Temps réel via Laravel Reverb (compatible Pusher protocol)
- ✅ Authentification via Laravel Sanctum
- ✅ Support multi-tenant (isolation par application)
- ✅ Conversations directes et de groupe
- ✅ Gestion des participants avec rôles (owner, admin, member)
- ✅ Messages avec fichiers, édition et suppression
- ✅ Compteurs de messages non lus

### Stack Technique

- **Backend**: Laravel 11 (dernière LTS)
- **Base de données**: MySQL (via XAMPP)
- **Temps réel**: Laravel Reverb
- **Authentification**: Laravel Sanctum
- **API**: RESTful versionnée (v1)

### Objectifs

Créer un service de chat **prêt pour la production** avec une logique métier solide, sans couvrir les aspects de déploiement infrastructure.

---

## ÉTAPE 1 — Initialisation du Projet

### 1.1 Créer un nouveau projet Laravel

```bash
# Installer Laravel via Composer
composer create-project laravel/laravel chat-service

cd chat-service
```

### 1.2 Configurer la base de données

#### Via XAMPP (MySQL)

1. Ouvrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Créer une nouvelle base de données: `chat_service`
3. Configurer le fichier `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_service
DB_USERNAME=root
DB_PASSWORD=
```

### 1.3 Activer le mode API

Laravel 11 utilise une nouvelle approche via `bootstrap/app.php`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configuration CORS
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->create();
```

### 1.4 Activer CORS pour l'accès multi-applications

Configurer `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

'allowed_origins' => [
    'http://localhost:3000',
    'http://localhost:5173',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:5173',
    'http://localhost:8000',
    'https://your-production-app1.com',
    'https://your-production-app2.com',
],

'allowed_headers' => [
    'Content-Type',
    'Authorization',
    'X-Requested-With',
    'X-Application-ID',
    'Accept',
    'X-CSRF-TOKEN'
],

'supports_credentials' => true,
```

### 1.5 Structure des Dossiers

```
chat-service/
├── app/
│   ├── Events/              # Événements de broadcasting (MessageSent)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── V1/    # Controllers API versionnées
│   │   │   │       ├── ConversationController.php
│   │   │   │       └── MessageController.php
│   │   │   └── Auth/
│   │   │       └── AuthController.php
│   │   ├── Middleware/     # Middleware personnalisés
│   │   └── Requests/       # Form requests de validation
│   ├── Models/             # Modèles Eloquent
│   │   ├── User.php
│   │   ├── Conversation.php
│   │   ├── Message.php
│   │   └── ConversationParticipant.php
│   └── Providers/          # Service providers
├── bootstrap/
│   └── app.php            # Configuration de l'application
├── config/
│   ├── cors.php           # Configuration CORS
│   ├── sanctum.php        # Configuration Sanctum
│   ├── reverb.php         # Configuration Reverb
│   └── broadcasting.php    # Configuration broadcasting
├── database/
│   ├── factories/         # Factories pour tests
│   ├── migrations/        # Migrations de base de données
│   └── seeders/          # Seeders pour données de test
├── public/               # Point d'entrée public
├── resources/            # Vues Blade, assets frontend
├── routes/
│   ├── api.php          # Routes API REST
│   ├── channels.php     # Channels WebSocket
│   ├── console.php      # Commands artisan
│   └── web.php         # Routes web (si nécessaire)
├── tests/               # Tests unitaires et feature
└── vendor/              # Dépendances Composer
```

---

## ÉTAPE 2 — Authentification (API)

### 2.1 Installer et configurer Laravel Sanctum

```bash
composer require laravel/sanctum

php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

php artisan migrate
```

### 2.2 Créer l'endpoint de login

#### Controller: `app/Http/Controllers/Auth/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $deviceName = $request->input('device_name', 'default');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Les identifiants fournis sont incorrects',
            ], 401);
        }

        $user = Auth::user();

        // Révoquer les tokens existants pour ce device
        $user->tokens()->where('name', $deviceName)->delete();

        // Créer un nouveau token (30 jours)
        $token = $user->createToken($deviceName, ['*'], now()->addDays(30));

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at,
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ], 200);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ], 200);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion de tous les appareils réussie',
        ], 200);
    }
}
```

#### Form Request: `app/Http/Requests/LoginRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

### 2.3 Protéger les routes avec le middleware auth

Dans `routes/api.php`:

```php
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Routes de chat...
});
```

### 2.4 Exemple de requête/réponse

#### Requête:
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "device_name": "web"
}
```

#### Réponse:
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "created_at": "2026-01-01T00:00:00.000000Z"
    },
    "token": "2|X7zK2LmN8oP9qR3sT4uV5wX6yZ7aB8cD9eF0gH1",
    "token_type": "Bearer",
    "expires_at": "2026-01-31T00:00:00.000000Z"
  }
}
```

#### Utilisation du token:
```http
GET /api/auth/user
Authorization: Bearer 2|X7zK2LmN8oP9qR3sT4uV5wX6yZ7aB8cD9eF0gH1
```

---

## ÉTAPE 3 — Modélisation Base de Données

### 3.1 Relation entre les entités

```
┌─────────────────┐
│     Users       │
├─────────────────┤
│ id             │◄──────┐
│ name           │       │
│ email          │       │
│ password       │       │
│ created_at     │       │
└─────────────────┘       │
         │                │
         │ created_by     │
         │                │
┌─────────────────┐       │     ┌─────────────────────┐
│  Conversations │       │     │ConversationParticipants│
├─────────────────┤       │     ├─────────────────────┤
│ id             │◄──────┼─────│ id                  │
│ type           │       │     │ conversation_id      │
│ name           │       │     │ user_id             │
│ created_by     │───────┼─────│ role                │
│ avatar         │       │     │ last_read_at        │
│ description    │       │     │ unread_count        │
│ status         │       │     │ joined_at           │
│ app_id         │       │     └─────────────────────┘
└─────────────────┘       │           │
         │                │           │
         │                │           │ user_id
         │                │           │
         │ conversation_id◄┼───────────┘
         │                │
┌─────────────────┐       │
│    Messages     │       │
├─────────────────┤       │
│ id             │       │
│ conversation_id│───────┘
│ user_id        │◄──────┐
│ content        │       │
│ type           │       │
│ file_url       │       │
│ file_name      │       │
│ file_size      │       │
│ is_edited      │       │
│ is_deleted     │       │
│ edited_at      │       │
│ app_id         │       │
│ created_at     │       │
└─────────────────┘       │
         │                │
         │ user_id       │
         └────────────────┘
```

### 3.2 Migrations

#### Migration Users (par défaut Laravel)
```bash
php artisan make:migration create_users_table
```

#### Migration Conversations: `2026_01_07_115326_create_conversations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['direct', 'group'])->default('direct');
            $table->string('name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('avatar')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->string('app_id')->default('default');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['type', 'status']);
            $table->index('app_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
```

#### Migration ConversationParticipants: `2026_01_07_115336_create_conversation_participants_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'member'])->default('member');
            $table->timestamp('last_read_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id'], 'unique_conversation_user');
            $table->index(['user_id', 'joined_at']);
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
```

#### Migration Messages: `2026_01_07_115342_create_messages_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('content');
            $table->enum('type', ['text', 'image', 'file', 'audio', 'video', 'system'])
                ->default('text');
            $table->string('file_url')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->string('app_id')->default('default');
            $table->index(['conversation_id', 'created_at']);
            $table->index('user_id');
            $table->index('app_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
```

#### Exécuter les migrations:
```bash
php artisan migrate
```

### 3.3 Modèles Eloquent

#### Modèle User: `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_participants',
            'user_id',
            'conversation_id'
        )->withPivot(['role', 'last_read_at', 'unread_count', 'joined_at'])
         ->withTimestamps()
         ->orderBy('updated_at', 'desc');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function directConversationWith(User $otherUser): ?Conversation
    {
        return $this->conversations()
            ->where('type', 'direct')
            ->whereHas('participants', function ($query) use ($otherUser) {
                $query->where('user_id', $otherUser->id);
            })
            ->first();
    }

    public function getOrCreateDirectConversationWith(User $otherUser): Conversation
    {
        $conversation = $this->directConversationWith($otherUser);

        if ($conversation) {
            return $conversation;
        }

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->id,
            'status' => 'active',
        ]);

        $conversation->participants()->createMany([
            ['user_id' => $this->id, 'role' => 'owner'],
            ['user_id' => $otherUser->id, 'role' => 'member'],
        ]);

        return $conversation->load('participants');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
    }
}
```

#### Modèle Conversation: `app/Models/Conversation.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'created_by',
        'avatar',
        'description',
        'status',
        'app_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForApp($query, string $appId)
    {
        return $query->where('app_id', $appId);
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function getUnreadCountForUser(User $user): int
    {
        $participant = $this->participants()->where('user_id', $user->id)->first();

        if (!$participant) {
            return 0;
        }

        return $this->messages()
            ->where('created_at', '>', $participant->last_read_at)
            ->where('user_id', '!=', $user->id)
            ->count();
    }
}
```

#### Modèle Message: `app/Models/Message.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'file_url',
        'file_name',
        'file_size',
        'is_edited',
        'is_deleted',
        'edited_at',
        'app_id',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'file_size' => 'integer',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForApp($query, string $appId)
    {
        return $query->where('app_id', $appId);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function markAsDeleted(): void
    {
        $this->update([
            'is_deleted' => true,
            'content' => '[Message supprimé]',
            'file_url' => null,
            'file_name' => null,
            'file_size' => null,
        ]);
    }

    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('content', 'like', "%{$term}%");
    }
}
```

#### Modèle ConversationParticipant: `app/Models/ConversationParticipant.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_read_at',
        'unread_count',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'unread_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function markAsRead(): void
    {
        $this->update([
            'last_read_at' => now(),
            'unread_count' => 0,
        ]);
    }
}
```

---

## ÉTAPE 4 — API REST (Chat)

### 4.1 Routes API versionnées

Dans `routes/api.php`:

```php
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\MessageController;

Route::prefix('v1')->group(function () {
    // Conversations
    Route::apiResource('conversations', ConversationController::class);

    // Gestion des participants
    Route::post('/conversations/{conversation}/participants',
        [ConversationController::class, 'addParticipants']);
    Route::delete('/conversations/{conversation}/participants/{user}',
        [ConversationController::class, 'removeParticipant']);
    Route::post('/conversations/{conversation}/leave',
        [ConversationController::class, 'leave']);

    // Messages
    Route::get('/conversations/{conversation}/messages',
        [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages',
        [MessageController::class, 'store']);
    Route::get('/conversations/{conversation}/messages/{message}',
        [MessageController::class, 'show']);
    Route::put('/conversations/{conversation}/messages/{message}',
        [MessageController::class, 'update']);
    Route::delete('/conversations/{conversation}/messages/{message}',
        [MessageController::class, 'destroy']);

    // Actions sur les messages
    Route::post('/conversations/{conversation}/read',
        [MessageController::class, 'markAsRead']);
    Route::get('/conversations/{conversation}/messages/search',
        [MessageController::class, 'search']);
});
```

### 4.2 Controllers

#### ConversationController

Le contrôleur gère toutes les opérations sur les conversations:
- Lister les conversations de l'utilisateur
- Créer des conversations directes ou de groupe
- Ajouter/supprimer des participants
- Gérer les rôles (owner, admin, member)
- Marquer les messages comme lus

**Endpoints clés:**
- `GET /api/v1/conversations` - Lister les conversations
- `POST /api/v1/conversations` - Créer une conversation
- `GET /api/v1/conversations/{id}` - Détails d'une conversation
- `PUT /api/v1/conversations/{id}` - Mettre à jour (admin/owner)
- `DELETE /api/v1/conversations/{id}` - Supprimer (owner)
- `POST /api/v1/conversations/{id}/participants` - Ajouter participants
- `DELETE /api/v1/conversations/{id}/participants/{userId}` - Retirer participant

Voir le fichier complet: `app/Http/Controllers/Api/V1/ConversationController.php`

#### MessageController

Le contrôleur gère tous les messages:
- Lister les messages d'une conversation (pagination)
- Envoyer des messages (avec diffusion WebSocket)
- Éditer et supprimer des messages
- Marquer les messages comme lus
- Rechercher dans les messages

**Endpoints clés:**
- `GET /api/v1/conversations/{conversationId}/messages` - Lister les messages
- `POST /api/v1/conversations/{conversationId}/messages` - Envoyer un message
- `PUT /api/v1/conversations/{conversationId}/messages/{messageId}` - Modifier un message
- `DELETE /api/v1/conversations/{conversationId}/messages/{messageId}` - Supprimer un message
- `POST /api/v1/conversations/{conversationId}/read` - Marquer comme lus

Voir le fichier complet: `app/Http/Controllers/Api/V1/MessageController.php`

### 4.3 Validations

Toutes les requêtes utilisent le validator de Laravel avec des règles personnalisées:

```php
$validator = Validator::make($request->all(), [
    'type' => ['required', Rule::in(['direct', 'group'])],
    'name' => ['required_if:type,group', 'string', 'max:255'],
    'participant_ids' => ['required', 'array', 'min:1'],
    'participant_ids.*' => ['exists:users,id'],
], [
    'type.required' => 'Le type de conversation est requis',
    'name.required_if' => 'Le nom est requis pour les groupes',
    'participant_ids.exists' => 'Participant(s) invalide(s)',
]);
```

---

## ÉTAPE 5 — Temps Réel (Laravel Reverb)

### 5.1 Installer et configurer Reverb

Laravel 11 inclut Reverb par défaut. Sinon:

```bash
composer require laravel/reverb

php artisan reverb:install
```

### 5.2 Configuration .env

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=chat-service
REVERB_APP_KEY=your-reverb-key-here
REVERB_APP_SECRET=your-reverb-secret-here
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 5.3 Créer l'événement MessageSent

Fichier: `app/Events/MessageSent.php`

```php
<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public Message $message;
    public User $sender;
    public string $appId;

    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->message->load('user');
        $this->sender = $message->user;
        $this->appId = $message->app_id;
    }

    /**
     * Channel privé pour l'isolement par conversation et application
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(
            'private-conversation.' . $this->message->conversation_id . '.' . $this->appId
        );
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'user_id' => $this->message->user_id,
                'content' => $this->message->content,
                'type' => $this->message->type,
                'file_url' => $this->message->file_url,
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'email' => $this->sender->email,
            ],
            'app_id' => $this->appId,
        ];
    }

    public function shouldQueue(): bool
    {
        return true;
    }
}
```

### 5.4 Configurer les channels privés

Dans `routes/channels.php`:

```php
<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

/**
 * Channel pour les notifications utilisateur
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Channel privé pour les conversations
 *
 * Format: private-conversation.{conversationId}.{app_id}
 *
 * Autorisation:
 * - L'utilisateur doit être authentifié
 * - L'utilisateur doit être participant de la conversation
 * - L'app_id doit correspondre (multi-tenant)
 */
Broadcast::channel('private-conversation.{conversationId}.{appId}', function ($user, $conversationId, $appId) {
    $conversation = Conversation::where('id', $conversationId)
        ->where('app_id', $appId)
        ->first();

    if (!$conversation) {
        return false;
    }

    return $conversation->hasParticipant($user);
});
```

### 5.5 Intégrer l'événement dans le Controller

Dans `MessageController@store`:

```php
use App\Events\MessageSent;

// Après création du message
$message = Message::create([...]);

// Charger les relations
$message->load('user');

// Diffuser l'événement WebSocket via Reverb
broadcast(new MessageSent($message))->toOthers();

return response()->json([
    'success' => true,
    'message' => 'Message envoyé',
    'data' => $message,
], 201);
```

### 5.6 Lancer le serveur Reverb

```bash
php artisan reverb:start
```

Le serveur démarre sur `ws://localhost:8080` par défaut.

### 5.7 Client WebSocket (Frontend)

#### Installation Pusher JS:

```bash
npm install pusher-js
```

#### Configuration JavaScript:

```javascript
import Pusher from 'pusher-js';

const pusher = new Pusher('your-reverb-key', {
  cluster: 'mt1',
  wsHost: 'localhost',
  wsPort: 8080,
  wssPort: 8080,
  forceTLS: false,
  enabledTransports: ['ws', 'wss'],
  authEndpoint: 'http://localhost:8000/broadcasting/auth',
  auth: {
    headers: {
      'Authorization': `Bearer ${yourToken}`,
      'X-Application-ID': 'my-app-001'
    }
  }
});

// Se connecter au channel
const conversationId = 1;
const appId = 'my-app-001';
const channel = pusher.subscribe(
  `private-conversation.${conversationId}.${appId}`
);

// Écouter les nouveaux messages
channel.bind('message.sent', function(data) {
  console.log('Nouveau message:', data);
  // data.message, data.sender, data.app_id
});
```

---

## ÉTAPE 6 — Sécurité et Bonnes Pratiques

### 6.1 Autorisation des channels privés

**Pourquoi les channels privés ?**

Les channels privés nécessitent une authentification avant de s'y connecter. Cela empêche:
- Les utilisateurs non autorisés d'écouter les conversations
- Les attaques d'écoute passive
- L'accès aux messages d'autres applications (multi-tenant)

**Mécanisme d'autorisation:**

1. Le client tente de se connecter: `pusher.subscribe('private-conversation.1.my-app')`
2. Reverb envoie une requête POST à `/broadcasting/auth`
3. Laravel vérifie:
   - Le token Bearer est valide
   - L'utilisateur est participant de la conversation 1
   - L'app_id correspond
4. Si autorisé, Reverb retourne une signature authentifiée
5. Le client peut maintenant écouter les événements

```php
// Dans routes/channels.php
Broadcast::channel('private-conversation.{conversationId}.{appId}', function ($user, $conversationId, $appId) {
    $conversation = Conversation::where('id', $conversationId)
        ->where('app_id', $appId)
        ->first();

    return $conversation && $conversation->hasParticipant($user);
});
```

### 6.2 Limitation des requêtes API (Rate Limiting)

**Pourquoi limiter ?**

- Protéger contre les attaques DDoS
- Prévenir l'abus de l'API
- Garantir une performance optimale

**Configuration dans `config/cache.php`:**

```php
'limiters' => [
    'api' => [
        'throttle:api', // 60 requêtes/minute par IP
    ],
],
```

**Usage dans `routes/api.php`:**

```php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Routes limitées
});
```

**Response en cas de dépassement:**

```json
{
  "success": false,
  "message": "Trop de requêtes. Veuillez réessayer plus tard."
}
```

### 6.3 Protection XSS

**Validation des entrées:**

```php
$validator = Validator::make($request->all(), [
    'content' => ['required', 'string', 'max:10000'],
    'type' => ['nullable', 'in:text,image,file,audio,video,system'],
]);
```

**Échappement automatique (Blade templates):**

```blade
{{ $message->content }} <!-- Échappé automatiquement -->
{!! $message->content !!} <!-- Non échappé (dangerux) -->
```

**Sanitisation côté client:**

```javascript
// Utiliser DOMPurify pour nettoyer les entrées utilisateur
import DOMPurify from 'dompurify';

const cleanContent = DOMPurify.sanitize(userInput);
```

### 6.4 Validation et règles d'autorisation

**Validation robuste:**

```php
public function store(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'content' => ['required', 'string', 'max:10000'],
        'type' => ['nullable', 'in:text,image,file,audio,video,system'],
        'file_url' => ['nullable', 'url'],
        'file_name' => ['nullable', 'string', 'max:255'],
        'file_size' => ['nullable', 'integer', 'min:0', 'max:10485760'], // Max 10MB
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $validator->errors(),
        ], 422);
    }
}
```

**Autorisation par rôle:**

```php
public function update(Request $request, $id): JsonResponse
{
    $conversation = Conversation::findOrFail($id);

    // Vérifier que l'utilisateur est participant
    if (!$conversation->hasParticipant($user)) {
        return response()->json([
            'success' => false,
            'message' => 'Non autorisé',
        ], 403);
    }

    // Vérifier le rôle (admin ou owner)
    $participant = $conversation->participants()
        ->where('user_id', $user->id)
        ->first();

    if (!$participant->isAdmin()) {
        return response()->json([
            'success' => false,
            'message' => 'Droits insuffisants',
        ], 403);
    }
}
```

### 6.5 Pourquoi séparer REST et WebSocket ?

**Avantages de cette architecture:**

1. **Séparation des préoccupations:**
   - REST = Opérations synchrones (CRUD)
   - WebSocket = Notifications temps réel

2. **Scalabilité:**
   - REST: Stateless, facile à scaler horizontalement
   - WebSocket: Gestion des connexions persistantes

3. **Compatibilité:**
   - REST fonctionne partout (browsers, mobiles, scripts)
   - WebSocket pour les interactions utilisateur en temps réel

4. **Redondance:**
   - Si WebSocket échoue, REST permet toujours d'envoyer des messages
   - Les clients peuvent utiliser un polling de secours

5. **Debugging:**
   - REST facilement testable avec Postman/cURL
   - WebSocket peut être désactivé sans casser l'API

**Flux typique:**

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       ├──────────────────────┐
       │                      │
       ↓                      ↓
┌──────────────┐      ┌──────────────┐
│   REST API    │      │  WebSocket   │
│              │      │   (Reverb)   │
│ - Send       │      │              │
│ - Load       │      │ - Listen     │
│ - Search     │      │ - Notify     │
│ - Update     │      │              │
└──────┬───────┘      └──────┬───────┘
       │                      │
       └──────────┬───────────┘
                  ↓
          ┌──────────────┐
          │   Laravel    │
          │  Backend    │
          └──────────────┘
                  ↓
          ┌──────────────┐
          │   MySQL DB   │
          └──────────────┘
```

**Exemple de scénario:**

1. **Envoyer un message:**
   ```
   POST /api/v1/conversations/1/messages (REST)
   → Enregistre en base de données
   → Diffuse événement WebSocket
   ```

2. **Recevoir un message:**
   ```
   WebSocket: message.sent event
   → Met à jour l'UI instantanément
   ```

3. **Rafraîchir l'historique:**
   ```
   GET /api/v1/conversations/1/messages (REST)
   → Charge les messages précédents
   ```

---

## Déploiement et Production

### Checklist avant mise en production

1. **Configuration .env:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   BROADCAST_CONNECTION=reverb
   REVERB_SCHEME=https
   ```

2. **Migrations:**
   ```bash
   php artisan migrate --force
   ```

3. **Optimisation:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Permissions:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

5. **HTTPS:**
   - Activer TLS pour l'API
   - Activer TLS pour Reverb (wss://)

6. **Queue (optionnel):**
   ```bash
   php artisan queue:work
   ```

---

## Annexes

### A. Commandes Artisan Utiles

```bash
# Démarrer le serveur de développement
php artisan serve

# Démarrer Reverb
php artisan reverb:start

# Migrations
php artisan make:migration create_table_name
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh

# Créer des contrôleurs
php artisan make:controller Api/V1/ChatController

# Créer des modèles
php artisan make:model Message

# Créer des événements
php artisan make:event MessageSent

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Tests
php artisan test
php artisan test --filter ConversationTest
```

### B. Exemples cURL

#### Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'
```

#### Créer conversation:
```bash
curl -X POST http://localhost:8000/api/v1/conversations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Application-ID: my-app" \
  -d '{"type":"direct","participant_ids":[2]}'
```

#### Envoyer message:
```bash
curl -X POST http://localhost:8000/api/v1/conversations/1/messages \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Application-ID: my-app" \
  -d '{"content":"Hello world!","type":"text"}'
```

### C. Tests Postman

Importez la collection depuis:
- `POSTMAN_EXAMPLES_COMPLETE.md`
- Exemple de collection JSON fourni séparément

### D. Ressources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Reverb](https://laravel.com/docs/reverb)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Pusher JS Documentation](https://pusher.com/docs/channels/library_auth_reference/rest-api)

---

**🎉 Félicitations ! Vous avez maintenant un service de chat complet, sécurisé et prêt pour la production !**

---

*Document version 1.0 - Dernière mise à jour: 7 janvier 2026*

















