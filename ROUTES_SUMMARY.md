# 📍 Résumé des Routes Chat Service

## ✅ Routes Web (Pages)
```
GET  /                    → welcome.blade.php
GET  /chat-test            → chat-test.blade.php
GET  /up                  → Health check Laravel
```

## ✅ Routes API

### Santé
```
GET  /api/health          → Status du service
```

### Authentification
```
POST   /api/auth/login       → Connexion
POST   /api/auth/logout      → Déconnexion
POST   /api/auth/logout-all  → Déconnexion tous appareils
GET    /api/auth/user       → Utilisateur authentifié
```

### Conversations V1
```
GET    /api/v1/conversations                        → Liste conversations
POST   /api/v1/conversations                        → Créer conversation
GET    /api/v1/conversations/{conversation}           → Voir conversation
PUT    /api/v1/conversations/{conversation}           → Modifier conversation
DELETE /api/v1/conversations/{conversation}           → Supprimer conversation
POST   /api/v1/conversations/{conversation}/participants      → Ajouter participants
DELETE /api/v1/conversations/{conversation}/participants/{user} → Retirer participant
POST   /api/v1/conversations/{conversation}/leave              → Quitter conversation
```

### Messages V1
```
GET    /api/v1/conversations/{conversation}/messages                → Liste messages
POST   /api/v1/conversations/{conversation}/messages                → Envoyer message
GET    /api/v1/conversations/{conversation}/messages/{message}       → Voir message
PUT    /api/v1/conversations/{conversation}/messages/{message}       → Modifier message
DELETE /api/v1/conversations/{conversation}/messages/{message}       → Supprimer message
POST   /api/v1/conversations/{conversation}/read                     → Marquer comme lu
GET    /api/v1/conversations/{conversation}/messages/search          → Rechercher messages
GET    /api/v1/conversations/{conversation}/typing                  → Utilisateurs en train d'écrire
```

### Routes spéciales
```
GET/POST /broadcasting/auth → Authentification WebSocket (nécessite token)
GET      /api/user         → Raccourci utilisateur authentifié
```

---

## 🔗 Services actifs

| Service | URL | Port | Statut |
|---------|-----|------|--------|
| Laravel Web | http://localhost:8000 | 8000 | ✅ Actif |
| Laravel API | http://localhost:8000/api | 8000 | ✅ Actif |
| Reverb WebSocket | ws://localhost:8080 | 8080 | ✅ Actif |

---

## 📝 Utilisateurs de test

| Nom | Email | Mot de passe |
|-----|-------|--------------|
| Alice | alice@test.com | password123 |
| Bob | bob@test.com | password123 |

---

## 🧪 Tests rapides

### Test santé API
```powershell
curl http://localhost:8000/api/health
```

### Test page d'accueil
```powershell
curl http://localhost:8000/
```

### Test page chat-test
```powershell
curl http://localhost:8000/chat-test
```

### Test connexion
```powershell
$body = @{email="alice@test.com";password="password123"} | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Body $body -ContentType "application/json"
```

---

## 🎯 Points d'entrée principaux

1. **Page d'accueil** : http://localhost:8000/
2. **API de santé** : http://localhost:8000/api/health
3. **Interface de test** : http://localhost:8000/chat-test
4. **WebSocket Reverb** : ws://localhost:8080/app/iuvcjjlml7xkwbdfaxo3

---

## 🔧 Correction appliquée

Ajouté dans `bootstrap/app.php` :
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',  // ← Ajouté
    api: __DIR__.'/../routes/api.php',
    // ...
)
```

Cela a corrigé le problème de 404 sur `/`.

















