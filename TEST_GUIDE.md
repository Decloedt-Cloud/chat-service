# 📚 Guide de Test - Chat Service avec Reverb

## ✅ Prérequis - Services actifs

✅ Laravel API : `http://127.0.0.1:8000`
✅ Reverb WebSocket : `ws://localhost:8080`
✅ Broadcast Driver : `reverb`

---

## 🧪 TEST 1 : API Health Check

### PowerShell
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/health" -Method GET | ConvertTo-Json
```

### Attendu
```json
{
  "status": "ok",
  "service": "Chat Service API",
  "version": "1.0.0"
}
```

---

## 🧪 TEST 2 : Créer des utilisateurs de test

### PowerShell
```powershell
# Créer le premier utilisateur
$body1 = @{
    name = "Alice"
    email = "alice@example.com"
    password = "password123"
    password_confirmation = "password123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/auth/register" -Method POST -Body $body1 -ContentType "application/json"

# Créer le deuxième utilisateur
$body2 = @{
    name = "Bob"
    email = "bob@example.com"
    password = "password123"
    password_confirmation = "password123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/auth/register" -Method POST -Body $body2 -ContentType "application/json"
```

---

## 🧪 TEST 3 : Se connecter et obtenir un token

### PowerShell
```powershell
# Connexion Alice
$loginBody = @{
    email = "alice@example.com"
    password = "password123"
} | ConvertTo-Json

$alice = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/auth/login" -Method POST -Body $loginBody -ContentType "application/json"

$aliceToken = $alice.data.token
Write-Host "Alice Token: $aliceToken"
```

### Attendu
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": { ... }
  }
}
```

---

## 🧪 TEST 4 : Créer une conversation

### PowerShell
```powershell
$headers = @{
    "Authorization" = "Bearer $aliceToken"
    "Content-Type" = "application/json"
}

$convBody = @{
    app_id = "test-app-001"
    name = "Conversation Test"
    type = "private"
    participant_ids = @(2)  # ID de Bob
} | ConvertTo-Json

$conversation = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations" -Method POST -Body $convBody -Headers $headers -ContentType "application/json"

$conversationId = $conversation.data.id
Write-Host "Conversation ID: $conversationId"
```

---

## 🧪 TEST 5 : Envoyer un message

### PowerShell
```powershell
$msgBody = @{
    content = "Bonjour Bob ! Comment ça va ?"
    type = "text"
} | ConvertTo-Json

$message = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations/$conversationId/messages" -Method POST -Body $msgBody -Headers $headers -ContentType "application/json"

Write-Host "Message envoyé: $($message.data.content)"
```

---

## 🧪 TEST 6 : Tester l'authentification WebSocket

### PowerShell
```powershell
# Pour tester l'authentification WebSocket
$socketId = "123.456.789"
$channelName = "private-conversation.$conversationId.test-app-001"

$authBody = @{
    socket_id = $socketId
    channel_name = $channelName
} | ConvertTo-Json

$auth = Invoke-RestMethod -Uri "http://127.0.0.1:8000/broadcasting/auth" -Method POST -Body $authBody -Headers $headers -ContentType "application/json"

Write-Host "Auth réussie : $auth"
```

---

## 🧪 TEST 7 : Lister les messages

### PowerShell
```powershell
$messages = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations/$conversationId/messages" -Method GET -Headers $headers

$messages.data | ForEach-Object {
    Write-Host "[$($_.created_at)] $($_.user.name): $($_.content)"
}
```

---

## 🧪 TEST 8 : Marquer comme lu

### PowerShell
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations/$conversationId/read" -Method POST -Headers $headers
```

---

## 🧪 TEST 9 : WebSocket Client (Navigateur)

Créez un fichier `websocket-test.html` et ouvrez-le dans votre navigateur :

```html
<!DOCTYPE html>
<html>
<head>
    <title>Test WebSocket Reverb</title>
</head>
<body>
    <h1>Test WebSocket Reverb</h1>
    <div id="logs"></div>
    <script>
        const REVERB_KEY = 'iuvcjjlml7xkwbdfaxo3';
        const ws = new WebSocket(`ws://localhost:8080/app/${REVERB_KEY}`);

        function log(msg) {
            const div = document.createElement('div');
            div.textContent = new Date().toLocaleTimeString() + ' - ' + msg;
            document.getElementById('logs').appendChild(div);
        }

        ws.onopen = () => log('✅ Connexion WebSocket établie');
        ws.onmessage = (e) => log('📩 Message: ' + e.data);
        ws.onerror = (e) => log('❌ Erreur: ' + e);
        ws.onclose = (e) => log('📡 Fermé: ' + e.code);
    </script>
</body>
</html>
```

---

## 🧪 TEST 10 : Test complet avec Postman

### Importez cette collection dans Postman :

```json
{
  "info": {
    "name": "Chat Service API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://127.0.0.1:8000/api"
    },
    {
      "key": "token",
      "value": ""
    },
    {
      "key": "conversation_id",
      "value": ""
    }
  ],
  "item": [
    {
      "name": "1. Health Check",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/health"
      }
    },
    {
      "name": "2. Login",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/auth/login",
        "body": {
          "mode": "raw",
          "raw": "{\"email\":\"alice@example.com\",\"password\":\"password123\"}",
          "options": { "raw": { "language": "json" } }
        }
      },
      "postmanTest": "if (pm.response.json().success) { pm.variables.set('token', pm.response.json().data.token); }"
    },
    {
      "name": "3. Create Conversation",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/v1/conversations",
        "header": [{ "key": "Authorization", "value": "Bearer {{token}}" }],
        "body": {
          "mode": "raw",
          "raw": "{\"app_id\":\"test-app-001\",\"name\":\"Conversation Test\",\"type\":\"private\",\"participant_ids\":[2]}",
          "options": { "raw": { "language": "json" } }
        }
      },
      "postmanTest": "if (pm.response.json().success) { pm.variables.set('conversation_id', pm.response.json().data.id); }"
    },
    {
      "name": "4. Send Message",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/v1/conversations/{{conversation_id}}/messages",
        "header": [{ "key": "Authorization", "value": "Bearer {{token}}" }],
        "body": {
          "mode": "raw",
          "raw": "{\"content\":\"Hello from Postman!\",\"type\":\"text\"}",
          "options": { "raw": { "language": "json" } }
        }
      }
    }
  ]
}
```

---

## 📊 Vérification de l'état

### Vérifier les ports actifs
```powershell
netstat -ano | findstr "8000 8080"
```

### Vérifier la configuration
```powershell
php artisan tinker --execute="echo 'Broadcast Driver: ' . config('broadcasting.default'); echo PHP_EOL; echo 'Reverb Host: ' . env('REVERB_HOST');"
```

### Vérifier les logs
```powershell
Get-Content storage\logs\laravel.log -Tail 20
```

---

## 🔧 Dépannage

### Si le serveur ne répond pas

```powershell
# Redémarrer Laravel
Stop-Process -Name php -Force
php artisan serve --host=127.0.0.1 --port=8000

# Redémarrer Reverb
php artisan reverb:start
```

### Si Broadcasting ne fonctionne pas

```powershell
# Vider le cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Vérifier la configuration
php artisan tinker --execute="print_r(config('broadcasting.connections.reverb'))"
```

---

## ✅ Checklist de validation

- [ ] Health check retourne 200 OK
- [ ] Login retourne un token valide
- [ ] Conversation créée avec succès
- [ ] Message envoyé et enregistré
- [ ] Auth WebSocket fonctionne (200 OK)
- [ ] WebSocket connecté sur ws://localhost:8080
- [ ] Messages reçus en temps réel via WebSocket

---

## 🎯 Tests de performance

### Temps de réponse API
```powershell
$sw = [System.Diagnostics.Stopwatch]::StartNew()
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/health" -Method GET
$sw.Stop()
Write-Host "Temps de réponse: $($sw.ElapsedMilliseconds)ms"
```

### Charge de messages (100 messages)
```powershell
1..100 | ForEach-Object {
    $msgBody = @{ content = "Message $_"; type = "text" } | ConvertTo-Json
    Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations/$conversationId/messages" -Method POST -Body $msgBody -Headers $headers -ContentType "application/json"
}
Write-Host "100 messages envoyés !"
```

---

## 📞 Support

En cas de problème, consultez les logs :
```powershell
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

Bon test ! 🚀

















