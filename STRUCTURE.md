# Chat Service - Structure du Projet Laravel

## 📁 Structure des Dossiers

```
chat-service/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php           # Authentification API
│   │   │   ├── Api/V1/
│   │   │   │   ├── ConversationController.php # Gestion des conversations
│   │   │   │   └── MessageController.php      # Gestion des messages
│   │   ├── Middleware/
│   │   │   └── EnsureApplicationIsValid.php  # Vérification X-Application-ID
│   │   └── Requests/
│   │       ├── StoreConversationRequest.php
│   │       ├── StoreMessageRequest.php
│   │       └── LoginRequest.php
│   ├── Models/
│   │   ├── User.php                          # Utilisateurs du système
│   │   ├── Conversation.php                  # Conversations (directes & groupes)
│   │   ├── Message.php                       # Messages
│   │   └── ConversationParticipant.php       # Participants aux conversations
│   ├── Events/
│   │   └── MessageSent.php                   # Event de diffusion Reverb
│   └── Providers/
│       ├── BroadcastServiceProvider.php     # Configuration des channels
│       └── EventServiceProvider.php         # Écouteurs d'événements
├── config/
│   ├── broadcasting.php                      # Configuration Reverb
│   ├── cors.php                             # Configuration CORS
│   └── sanctum.php                          # Configuration Sanctum
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   ├── 2024_01_01_000002_create_conversations_table.php
│   │   ├── 2024_01_01_000003_create_conversation_participants_table.php
│   │   └── 2024_01_01_000004_create_messages_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── routes/
│   ├── api.php                              # Routes API versionnées
│   └── channels.php                         # Routes Broadcast Reverb
├── resources/
│   └── postman/                             # Collection Postman
└── .env                                     # Configuration de l'environnement
```

## 📊 Description des Composants Principaux

### Modèles (Eloquent Models)

1. **User**: Utilisateurs du système de chat
2. **Conversation**: Conversations (directes entre 2 users ou groupes)
3. **ConversationParticipant**: Lien Users-Conversations avec rôles
4. **Message**: Messages dans les conversations

### Controllers API

- **AuthController**: Login, Logout, User info
- **ConversationController**: CRUD des conversations
- **MessageController**: CRUD des messages, pagination

### Events

- **MessageSent**: Diffusion en temps réel via Reverb

## 🔐 Flux d'Authentification

1. Client envoie credentials à `/api/v1/auth/login`
2. Vérification credentials et création token Sanctum
3. Client utilise token Bearer dans Authorization header
4. Middleware `auth:sanctum` protège les routes

## 🔄 Flux en Temps Réel

1. Client POST message → API
2. API sauvegarde en base
3. Event `MessageSent` diffusé sur `private-conversation.{id}.{app_id}`
4. Clients connectés reçoivent message en temps réel

## 🛡️ Sécurité

- **Rate Limiting**: 60 requêtes/minute par IP
- **CORS**: Origines whitelistées
- **Sanctum**: Tokens Bearer avec expiration
- **Channel Authorization**: Vérification app_id et participation
- **Validation**: Form Requests pour toutes les entrées

## 🚀 Points d'Entrée API

```
POST   /api/v1/auth/login          → Authentification
POST   /api/v1/auth/logout         → Déconnexion

GET    /api/v1/conversations       → Liste des conversations
POST   /api/v1/conversations       → Créer conversation
GET    /api/v1/conversations/{id}  → Détails conversation
DELETE /api/v1/conversations/{id}  → Supprimer conversation

GET    /api/v1/conversations/{id}/messages    → Liste messages (pagination)
POST   /api/v1/conversations/{id}/messages    → Envoyer message
POST   /api/v1/conversations/{id}/read        → Marquer comme lu
```

## 📡 Channels Reverb

```
private-conversation.{conversationId}.{appId}
```

- **conversationId**: ID de la conversation
- **appId**: Identifiant unique de l'application cliente (pour multi-tenant)

