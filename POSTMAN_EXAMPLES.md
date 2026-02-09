# Chat Service API - Exemples de Requêtes

## 🔐 Authentification

### 1. Login (POST /api/auth/login)

**Headers:**
```
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
      "created_at": "2024-01-01T00:00:00.000000Z"
    },
    "token": "2|X7zK2LmN8oP9qR3sT4uV5wX6yZ7aB8cD9eF0gH1",
    "token_type": "Bearer",
    "expires_at": "2024-01-31T00:00:00.000000Z"
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
    "email": [
      "L'adresse email est requise"
    ],
    "password": [
      "Le mot de passe doit contenir au moins 8 caractères"
    ]
  }
}
```

---

### 2. Get User Info (GET /api/auth/user)

**Headers:**
```
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
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
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
```
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
```
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

## 🚀 Rate Limiting

Toutes les routes protégées sont limitées à **60 requêtes par minute** par IP.

**Response 429 (Too Many Requests):**
```json
{
  "success": false,
  "message": "Trop de requêtes. Veuillez réessayer plus tard."
}
```

---

## 🔒 Points Importants

1. **Token Expiration**: Les tokens expirent après 30 jours par défaut
2. **Token Storage**: Stockez le token de manière sécurisée (localStorage, sessionStorage, ou secure cookies en production)
3. **Device Management**: Chaque appareil obtient un token séparé
4. **Multi-device Logout**: Utilisez `logout-all` pour déconnecter tous les appareils
5. **Protection CORS**: Assurez-vous que l'origine de votre frontend est dans la configuration CORS

