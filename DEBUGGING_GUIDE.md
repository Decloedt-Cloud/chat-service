# Guide de Débogage - Problème "Erreur de chargement"

## Problème
Après un login réussi, vous voyez "Erreur de chargement" dans l'interface de chat.

## Solution Immédiate

### 1. **Ouvrez les outils de développement du navigateur**
   - Appuyez sur **F12** ou cliquez droit → "Inspecter"
   - Allez dans l'onglet **"Console"**
   - Regardez les messages d'erreur

### 2. **Consultez l'onglet "Network" (Réseau)**
   - Dans les DevTools (F12), cliquez sur l'onglet "Network"
   - Faites un nouveau login
   - Recherchez la requête `conversations`
   - Cliquez dessus pour voir:
     - Le code de réponse (200, 500, 401, etc.)
     - Les en-têtes (Headers)
     - La réponse (Response)

## Causes Possibles

### 1. **Erreur 500 - Erreur serveur**
   **Symptôme**: Status code 500 dans l'onglet Network
   **Cause**: Erreur dans le contrôleur ou la base de données
   **Solution**: Vérifiez les logs Laravel:
   ```bash
   # Vérifiez les 50 dernières lignes du log
   Get-Content "C:\xampp\htdocs\chat-service\storage\logs\laravel.log" -Tail 50
   ```

### 2. **Erreur 401 - Non autorisé**
   **Symptôme**: Status code 401
   **Cause**: Token invalide ou expiré
   **Solution**: Déconnectez-vous et reconnectez-vous

### 3. **Erreur 403 - Accès refusé**
   **Symptôme**: Status code 403
   **Cause**: L'utilisateur n'est pas participant aux conversations
   **Solution**: Vérifiez que l'utilisateur a des conversations

### 4. **Erreur CORS**
   **Symptôme**: Erreur dans la console du navigateur:
   ```
   Access to XMLHttpRequest has been blocked by CORS policy
   ```
   **Cause**: Configuration CORS incorrecte
   **Solution**: Vérifiez que l'origine est autorisée dans `config/cors.php`

### 5. **Erreur JSON**
   **Symptôme**: Erreur dans la console:
   ```
   SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
   ```
   **Cause**: Le serveur retourne du HTML au lieu du JSON
   **Solution**: Vérifiez que l'API retourne bien du JSON

## Correction Appliquée

J'ai modifié le fichier `resources/views/chat-test.blade.php` pour ajouter plus de logs:

**Avant**:
```javascript
conversations = data.data;
```

**Après**:
```javascript
console.log('=== Chargement des conversations ===');
console.log('URL:', `${config.apiBaseUrl}/api/v1/conversations`);
console.log('Token:', token ? `${token.substring(0, 20)}...` : 'NON');
console.log('Response status:', response.status, response.statusText);
console.log('Response data:', data);
```

Ces logs vous aideront à identifier exactement où se trouve le problème.

## Tests à Effectuer

### Test 1: Via navigateur
1. Ouvrez: `http://localhost:8000/test-browser-api.html`
2. Cliquez sur "Login"
3. Cliquez sur "Load Conversations"
4. Observez les résultats

### Test 2: Via PHP
```bash
cd "C:\xampp\htdocs\chat-service"
php test-conversations-with-token.php
```

### Test 3: Via l'interface principale
1. Ouvrez: `http://localhost:8000/chat-test`
2. Faites un login avec:
   - Email: `alice@example.com`
   - Password: `password123`
3. Ouvrez la console (F12)
4. Observez les logs

## Vérifications Rapides

### ✅ Vérifiez que les serveurs sont en marche
```bash
# Laravel API
netstat -ano | findstr ":8000"

# Reverb WebSocket
netstat -ano | findstr ":8080"
```

Les deux devraient afficher `LISTENING`.

### ✅ Vérifiez la configuration
```bash
cd "C:\xampp\htdocs\chat-service"
php artisan config:clear
php artisan cache:clear
```

### ✅ Vérifiez les migrations
```bash
cd "C:\xampp\htdocs\chat-service"
php artisan migrate:status
```

Toutes les migrations devraient être "Ran".

## Réponses Attendues

### ✅ Réponse API correcte (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "type": "direct",
      "display_name": "Bob Smith",
      "unread_count": 0,
      "participants_count": 2,
      "last_message": { ... }
    }
  ]
}
```

### ❌ Réponse API incorrecte
```json
{
  "success": false,
  "message": "Erreur..."
}
```

## Si le Problème Persiste

1. **Prenez des captures d'écran** de:
   - La console du navigateur (F12 → Console)
   - L'onglet Network (F12 → Network → requête conversations)

2. **Vérifiez les logs**:
   ```bash
   Get-Content "C:\xampp\htdocs\chat-service\storage\logs\laravel.log" -Tail 100
   ```

3. **Testez manuellement**:
   ```bash
   curl -X GET "http://localhost:8000/api/v1/conversations" ^
        -H "Authorization: Bearer YOUR_TOKEN" ^
        -H "X-Application-ID: test-app-001"
   ```

4. **Contactez-moi** avec:
   - Les captures d'écran
   - Les logs d'erreur
   - Le code de réponse HTTP

## Actions Préalables

Avant de tester, assurez-vous d'avoir:

1. ✅ Laravel API en cours d'exécution (port 8000)
2. ✅ Reverb WebSocket en cours d'exécution (port 8080)
3. ✅ Configuration de CORS mise à jour
4. ✅ Cache de configuration nettoyé
5. ✅ Utilisateurs de test créés:
   - Alice Johnson: `alice@example.com` / `password123`
   - Bob Smith: `bob@example.com` / `password123`

















