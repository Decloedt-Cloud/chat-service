# Chat Service API - Exemples de Requêtes Complets

🚀 **Guide complet pour tester le service de chat avec REST API et WebSocket (Reverb)**

---

## 🔐 Authentification

### 1. Login (POST /api/auth/login)

**Headers:**
```http
Content-Type: application/json
X-Application-ID: your-app-id (optionnel pour l'auth)
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "device_name": "web"
}
```

**Response 200 (Success):**
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

**Response 401 (Invalid Credentials):**
```json
{
  "success": false,
  "message": "Les identifiants fournis sont incorrects"
}
```

**Response 422 (Validation Error):**
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "email": ["L'adresse email est requise"],
    "password": ["Le mot de passe doit contenir au moins 8 caractères"]
  }
}
```

---

### 2. Get User Info (GET /api/auth/user)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token_from_login}
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

**Response 401 (Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

### 3. Logout (POST /api/auth/logout)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token_from_login}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

---

### 4. Logout All Devices (POST /api/auth/logout-all)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token_from_login}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Déconnexion de tous les appareils réussie"
}
```

---

## 📝 Comment Utiliser le Token Bearer

### Postman:
1. Cliquez sur l'onglet **Authorization**
2. Sélectionnez **Type**: Bearer Token
3. Dans le champ **Token**, collez le token reçu du login

### cURL:
```bash
curl -X GET http://localhost:8000/api/auth/user \
  -H "Authorization: Bearer 2|X7zK2LmN8oP9qR3sT4uV5wX6yZ7aB8cD9eF0gH1" \
  -H "Content-Type: application/json"
```

### JavaScript (fetch):
```javascript
fetch('http://localhost:8000/api/auth/user', {
  headers: {
    'Authorization': 'Bearer 2|X7zK2LmN8oP9qR3sT4uV5wX6yZ7aB8cD9eF0gH1',
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

---

## 💬 Conversations

### 1. Get All Conversations (GET /api/v1/conversations)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Query Parameters:**
- `page` (optional, default: 1)
- `per_page` (optional, default: 20, max: 100)

**Response 200:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "type": "direct",
        "name": null,
        "created_by": 1,
        "avatar": null,
        "description": null,
        "status": "active",
        "app_id": "my-app-001",
        "display_name": "Jane Smith",
        "display_avatar": null,
        "unread_count": 3,
        "created_at": "2026-01-07T12:00:00.000000Z",
        "updated_at": "2026-01-07T14:30:00.000000Z",
        "last_message": {
          "id": 42,
          "content": "Salut ! Comment ça va ?",
          "type": "text",
          "user": {
            "id": 2,
            "name": "Jane Smith"
          },
          "created_at": "2026-01-07T14:30:00.000000Z"
        },
        "participants": [...]
      }
    ],
    "total": 15,
    "per_page": 20,
    "last_page": 1
  }
}
```

---

### 2. Create Conversation (POST /api/v1/conversations)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

#### Create Direct Conversation:
**Request Body:**
```json
{
  "type": "direct",
  "participant_ids": [2]
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Conversation directe créée",
  "data": {
    "id": 1,
    "type": "direct",
    "created_by": 1,
    "status": "active",
    "app_id": "my-app-001",
    "participants": [
      {
        "user_id": 1,
        "role": "owner",
        "user": { "id": 1, "name": "John Doe" }
      },
      {
        "user_id": 2,
        "role": "member",
        "user": { "id": 2, "name": "Jane Smith" }
      }
    ]
  }
}
```

#### Create Group Conversation:
**Request Body:**
```json
{
  "type": "group",
  "name": "Équipe Marketing",
  "description": "Discussions marketing",
  "avatar": "https://example.com/avatar.jpg",
  "participant_ids": [2, 3, 4]
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Conversation de groupe créée",
  "data": {
    "id": 5,
    "type": "group",
    "name": "Équipe Marketing",
    "description": "Discussions marketing",
    "avatar": "https://example.com/avatar.jpg",
    "created_by": 1,
    "status": "active",
    "app_id": "my-app-001",
    "participants": [...]
  }
}
```

---

### 3. Get Conversation Details (GET /api/v1/conversations/{id})

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "type": "group",
    "name": "Équipe Marketing",
    "description": "Discussions marketing",
    "created_by": 1,
    "avatar": null,
    "status": "active",
    "app_id": "my-app-001",
    "unread_count": 5,
    "participants_count": 8,
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-07T14:30:00.000000Z",
    "participants": [...],
    "creator": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "last_message": {...}
  }
}
```

---

### 4. Update Conversation (PUT /api/v1/conversations/{id})

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Request Body:**
```json
{
  "name": "Nouveau nom du groupe",
  "description": "Nouvelle description",
  "avatar": "https://example.com/new-avatar.jpg",
  "status": "active"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Conversation mise à jour",
  "data": {
    "id": 1,
    "name": "Nouveau nom du groupe",
    "description": "Nouvelle description",
    "avatar": "https://example.com/new-avatar.jpg",
    ...
  }
}
```

---

### 5. Delete Conversation (DELETE /api/v1/conversations/{id})

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Response 200:**
```json
{
  "success": true,
  "message": "Conversation supprimée"
}
```

---

### 6. Add Participants (POST /api/v1/conversations/{id}/participants)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Request Body:**
```json
{
  "participant_ids": [5, 6, 7]
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "3 participant(s) ajouté(s)",
  "data": {
    "id": 1,
    "participants": [...]
  }
}
```

---

### 7. Remove Participant (DELETE /api/v1/conversations/{id}/participants/{userId})

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Response 200:**
```json
{
  "success": true,
  "message": "Participant retiré"
}
```

---

### 8. Leave Conversation (POST /api/v1/conversations/{id}/leave)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Response 200:**
```json
{
  "success": true,
  "message": "Vous avez quitté la conversation"
}
```

---

## 📨 Messages

### 1. Get Messages (GET /api/v1/conversations/{conversationId}/messages)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Query Parameters:**
- `page` (optional, default: 1)
- `per_page` (optional, default: 20, max: 100)
- `before` (optional, ISO 8601 date) - Load messages before this timestamp

**Response 200:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "conversation_id": 1,
        "user_id": 1,
        "content": "Salut tout le monde !",
        "type": "text",
        "file_url": null,
        "file_name": null,
        "file_size": null,
        "is_edited": false,
        "is_deleted": false,
        "edited_at": null,
        "app_id": "my-app-001",
        "created_at": "2026-01-07T12:00:00.000000Z",
        "updated_at": "2026-01-07T12:00:00.000000Z",
        "user": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com"
        }
      }
    ],
    "total": 50,
    "per_page": 20,
    "last_page": 3
  }
}
```

---

### 2. Send Message (POST /api/v1/conversations/{conversationId}/messages)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

#### Send Text Message:
**Request Body:**
```json
{
  "content": "Ceci est un message de test",
  "type": "text"
}
```

#### Send File Message:
**Request Body:**
```json
{
  "content": "Voici le document demandé",
  "type": "file",
  "file_url": "https://example.com/uploads/document.pdf",
  "file_name": "document.pdf",
  "file_size": 2458912
}
```

#### Send Image Message:
**Request Body:**
```json
{
  "content": "Check this photo!",
  "type": "image",
  "file_url": "https://example.com/uploads/photo.jpg",
  "file_name": "photo.jpg",
  "file_size": 1024576
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Message envoyé",
  "data": {
    "id": 51,
    "conversation_id": 1,
    "user_id": 1,
    "content": "Ceci est un message de test",
    "type": "text",
    "file_url": null,
    "file_name": null,
    "file_size": null,
    "is_edited": false,
    "is_deleted": false,
    "edited_at": null,
    "app_id": "my-app-001",
    "created_at": "2026-01-07T14:35:00.000000Z",
    "updated_at": "2026-01-07T14:35:00.000000Z",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### 3. Get Message (GET /api/v1/conversations/{conversationId}/messages/{messageId})

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 51,
    "conversation_id": 1,
    "user_id": 1,
    "content": "Ceci est un message de test",
    "type": "text",
    "is_edited": false,
    "is_deleted": false,
    "app_id": "my-app-001",
    "created_at": "2026-01-07T14:35:00.000000Z",
    "user": {
      "id": 1,
      "name": "John Doe"
    }
  }
}
```

---

### 4. Update Message (PUT /api/v1/conversations/{conversationId}/messages/{messageId})

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Request Body:**
```json
{
  "content": "Message modifié"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Message modifié",
  "data": {
    "id": 51,
    "content": "Message modifié",
    "is_edited": true,
    "edited_at": "2026-01-07T14:40:00.000000Z",
    ...
  }
}
```

---

### 5. Delete Message (DELETE /api/v1/conversations/{conversationId}/messages/{messageId})

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Response 200:**
```json
{
  "success": true,
  "message": "Message supprimé"
}
```

---

### 6. Mark Messages as Read (POST /api/v1/conversations/{conversationId}/read)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Response 200:**
```json
{
  "success": true,
  "message": "Messages marqués comme lus"
}
```

---

### 7. Search Messages (GET /api/v1/conversations/{conversationId}/messages/search)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Query Parameters:**
- `q` (required) - Search term
- `page` (optional, default: 1)
- `per_page` (optional, default: 20)

**Request:**
```bash
GET /api/v1/conversations/1/messages/search?q=important&page=1
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 42,
        "content": "C'est un point important à retenir",
        "type": "text",
        "created_at": "2026-01-07T14:30:00.000000Z",
        "user": {...}
      }
    ],
    "total": 5,
    "per_page": 20,
    "last_page": 1
  }
}
```

---

### 8. Get Typing Users (GET /api/v1/conversations/{conversationId}/typing)

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Application-ID: my-app-001
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "typing": [
      {
        "user_id": 2,
        "name": "Jane Smith"
      }
    ],
    "expires_in": 5
  }
}
```

---

## 📡 WebSocket avec Laravel Reverb

### Configuration Reverb

Avant de vous connecter, assurez-vous que Reverb est configuré dans votre fichier `.env`:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Lancer le serveur Reverb

```bash
php artisan reverb:start
```

Le serveur Reverb démarrera sur `ws://localhost:8080` par défaut.

---

### Client WebSocket avec Pusher SDK

#### Installation du SDK Pusher

```bash
# Pour Node.js
npm install pusher-js

# Pour CDN
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
```

#### Configuration JavaScript (Frontend)

```javascript
import Pusher from 'pusher-js';

// Initialiser Pusher avec configuration Reverb
const pusher = new Pusher('your-app-key', {
  cluster: 'mt1', // Non utilisé avec Reverb, mais requis par le SDK
  wsHost: 'localhost',
  wsPort: 8080,
  wssPort: 8080,
  forceTLS: false,
  enabledTransports: ['ws', 'wss'],
  authEndpoint: 'http://localhost:8000/broadcasting/auth',
  auth: {
    headers: {
      'Authorization': 'Bearer ' + yourBearerToken,
      'X-Application-ID': 'my-app-001'
    }
  }
});

// Se connecter au channel privé d'une conversation
const conversationId = 1;
const appId = 'my-app-001';
const channelName = `private-conversation.${conversationId}.${appId}`;

const channel = pusher.subscribe(channelName);

// Écouter l'événement de message envoyé
channel.bind('message.sent', function(data) {
  console.log('Nouveau message reçu:', data);

  // data contient:
  // {
  //   message: {
  //     id: 51,
  //     conversation_id: 1,
  //     content: "Ceci est un message de test",
  //     type: "text",
  //     user_id: 1,
  //     created_at: "2026-01-07T14:35:00.000000Z",
  //     ...
  //   },
  //   sender: {
  //     id: 1,
  //     name: "John Doe",
  //     email: "john@example.com"
  //   },
  //   app_id: "my-app-001"
  // }

  // Ajouter le message à votre UI
  addMessageToChat(data.message, data.sender);
});

// Événements de connexion
pusher.connection.bind('connected', () => {
  console.log('Connecté à Reverb!');
});

pusher.connection.bind('disconnected', () => {
  console.log('Déconnecté de Reverb');
});

pusher.connection.bind('error', (err) => {
  console.error('Erreur de connexion Reverb:', err);
});

// Se déconnecter d'une conversation
pusher.unsubscribe(channelName);
```

---

### Client WebSocket avec Pusher (CDN)

```html
<!DOCTYPE html>
<html>
<head>
  <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
</head>
<body>
  <div id="messages"></div>

  <script>
    const pusher = new Pusher('your-app-key', {
      cluster: 'mt1',
      wsHost: 'localhost',
      wsPort: 8080,
      wssPort: 8080,
      forceTLS: false,
      enabledTransports: ['ws', 'wss'],
      authEndpoint: 'http://localhost:8000/broadcasting/auth',
      auth: {
        headers: {
          'Authorization': 'Bearer ' + yourBearerToken,
          'X-Application-ID': 'my-app-001'
        }
      }
    });

    const conversationId = 1;
    const appId = 'my-app-001';

    const channel = pusher.subscribe(
      `private-conversation.${conversationId}.${appId}`
    );

    channel.bind('message.sent', function(data) {
      const messagesDiv = document.getElementById('messages');
      const messageDiv = document.createElement('div');
      messageDiv.innerHTML = `
        <strong>${data.sender.name}:</strong>
        ${data.message.content}
        <small>${new Date(data.message.created_at).toLocaleString()}</small>
      `;
      messagesDiv.appendChild(messageDiv);
    });
  </script>
</body>
</html>
```

---

### Client WebSocket avec Postman

Postman ne supporte pas nativement WebSocket, mais vous pouvez:

1. Utiliser l'application de bureau Postman (supporte WebSocket)
2. Utiliser un outil comme `wscat`:
   ```bash
   npm install -g wscat
   wscat -c ws://localhost:8080/app/your-app-key?protocol=7
   ```

---

### Exemple React Hook pour WebSocket

```javascript
import { useEffect, useCallback } from 'react';
import Pusher from 'pusher-js';

function useConversationChannel(conversationId, appId, token, onMessage) {
  useEffect(() => {
    if (!conversationId || !appId) return;

    const pusher = new Pusher('your-app-key', {
      cluster: 'mt1',
      wsHost: 'localhost',
      wsPort: 8080,
      wssPort: 8080,
      forceTLS: false,
      enabledTransports: ['ws', 'wss'],
      authEndpoint: 'http://localhost:8000/broadcasting/auth',
      auth: {
        headers: {
          'Authorization': `Bearer ${token}`,
          'X-Application-ID': appId
        }
      }
    });

    const channel = pusher.subscribe(
      `private-conversation.${conversationId}.${appId}`
    );

    channel.bind('message.sent', (data) => {
      onMessage(data);
    });

    return () => {
      pusher.unsubscribe(`private-conversation.${conversationId}.${appId}`);
      pusher.disconnect();
    };
  }, [conversationId, appId, token, onMessage]);
}

// Utilisation
function ChatRoom({ conversationId, token }) {
  const handleNewMessage = useCallback((data) => {
    console.log('Nouveau message:', data);
    // Mettre à jour l'état de l'UI
  }, []);

  useConversationChannel(conversationId, 'my-app-001', token, handleNewMessage);

  return (
    <div>
      {/* UI de chat */}
    </div>
  );
}
```

---

## 🚀 Rate Limiting

Toutes les routes protégées sont limitées à **60 requêtes par minute** par IP.

**Response 429 (Too Many Requests):**
```json
{
  "success": false,
  "message": "Trop de requêtes. Veuillez réessayer plus tard."
}
```

**Headers:**
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1609459200
```

---

## 🔒 Points Importants

### Sécurité REST API:
1. **Token Expiration**: Les tokens expirent après 30 jours par défaut
2. **Token Storage**: Stockez le token de manière sécurisée (localStorage, sessionStorage, ou secure cookies en production)
3. **Device Management**: Chaque appareil obtient un token séparé
4. **Multi-device Logout**: Utilisez `logout-all` pour déconnecter tous les appareils
5. **Protection CORS**: Assurez-vous que l'origine de votre frontend est dans la configuration CORS
6. **Multi-tenant**: Utilisez le header `X-Application-ID` pour isoler les données par application

### Sécurité WebSocket:
1. **Private Channels**: Tous les channels de conversation sont privés et nécessitent une authentification
2. **Autorisation**: L'utilisateur doit être participant de la conversation pour se connecter
3. **Isolement Multi-tenant**: Le nom du channel inclut `app_id` pour isoler les conversations par application
4. **Header Authorization**: La connexion WebSocket doit inclure le header `Authorization` avec le token Bearer

### Bonnes Pratiques:
1. **Pagination**: Utilisez la pagination pour les grandes listes de messages
2. **Lazy Loading**: Chargez les messages progressivement avec le paramètre `before`
3. **Mark as Read**: Marquez les messages comme lus pour maintenir le compteur à jour
4. **Error Handling**: Implémentez une gestion d'erreurs robuste côté client
5. **Reconnection**: Gérez les déconnexions WebSocket et reconnectez-vous automatiquement

---

## 📚 Flux de Complet d'Utilisation

### Scénario: Créer et utiliser une conversation

1. **Connexion**:
   ```bash
   POST /api/auth/login
   ```

2. **Créer une conversation**:
   ```bash
   POST /api/v1/conversations
   Content-Type: application/json
   Authorization: Bearer {token}
   X-Application-ID: my-app-001

   {
     "type": "direct",
     "participant_ids": [2]
   }
   ```

3. **Se connecter au WebSocket**:
   ```javascript
   const pusher = new Pusher('your-app-key', {...});
   const channel = pusher.subscribe(`private-conversation.1.my-app-001`);
   channel.bind('message.sent', (data) => { ... });
   ```

4. **Envoyer un message**:
   ```bash
   POST /api/v1/conversations/1/messages
   Content-Type: application/json
   Authorization: Bearer {token}
   X-Application-ID: my-app-001

   {
     "content": "Salut !",
     "type": "text"
   }
   ```

5. **Recevoir le message en temps réel** via WebSocket

6. **Marquer comme lu**:
   ```bash
   POST /api/v1/conversations/1/read
   Authorization: Bearer {token}
   X-Application-ID: my-app-001
   ```

7. **Charger l'historique**:
   ```bash
   GET /api/v1/conversations/1/messages?per_page=20
   Authorization: Bearer {token}
   X-Application-ID: my-app-001
   ```

---

## 🎯 Notes de Développement

### Configuration Environnement (.env)

```env
# Application
APP_NAME="Chat Service"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (MySQL via XAMPP)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_service
DB_USERNAME=root
DB_PASSWORD=

# Broadcasting (Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=chat-service
REVERB_APP_KEY=your-reverb-key
REVERB_APP_SECRET=your-reverb-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

# Queue (optionnel pour production)
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=file

# Session
SESSION_DRIVER=file
```

### Commandes Artisan Utiles

```bash
# Démarrer le serveur Reverb
php artisan reverb:start

# Démarrer le serveur de développement
php artisan serve

# Exécuter les migrations
php artisan migrate

# Vider le cache
php artisan cache:clear
php artisan config:clear

# Générer un nouveau token Reverb
php artisan reverb:install

# Créer des utilisateurs de test
php artisan tinker
>>> $user = \App\Models\User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'password' => bcrypt('password123')]);
```

---

**🎉 Félicitations ! Vous avez maintenant un service de chat complet avec API REST et temps réel via Laravel Reverb !**

















