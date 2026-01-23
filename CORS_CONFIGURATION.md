# Configuration CORS - Chat Service

## Problème

Lorsque le frontend React (localhost:5174) essaie de faire des requêtes vers le backend chat-service (localhost:8001), une erreur CORS est survenue :

```
Access to fetch at 'http://localhost:8001/chat-service/backend/api/v1/conversations' from origin 'http://localhost:5174' has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

## Solution

Les modifications suivantes ont été appliquées pour résoudre le problème CORS :

### 1. Configuration CORS (`config/cors.php`)

**Modifications :**
- Ajouté `http://localhost:5174` aux origines autorisées
- Ajouté `http://127.0.0.1:5174` aux origines autorisées
- Ajouté `http://localhost:8001` aux origines autorisées (port alternatif)
- Ajouté `broadcasting/auth` aux chemins CORS
- Activé `supports_credentials` pour autoriser les requêtes avec Bearer token

**Origines autorisées actuelles :**
```php
'allowed_origins' => [
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost:5174',      // ✅ Ajouté
    'http://127.0.0.1:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:5174',    // ✅ Ajouté
    'http://localhost:8000',
    'http://localhost:8001',         // ✅ Ajouté
],
```

### 2. Configuration Sanctum (`config/sanctum.php`)

**Modifications :**
- Ajouté les ports 5173 et 5174 aux domaines stateful
- Cela permet à Sanctum d'authentifier les requêtes depuis ces ports

**Domaines stateful actuels :**
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,localhost:5173,localhost:5174,127.0.0.1,127.0.0.1:3000,127.0.0.1:5173,127.0.0.1:5174,127.0.0.1:8000,::1',
    Sanctum::currentApplicationUrlWithPort(),
))),
```

### 3. Middleware EnsureApplicationIsValid

**Modifications :**
- Ajouté une exception pour les requêtes OPTIONS (preflight CORS)
- Cela permet aux navigateurs d'effectuer les requêtes pré-vol sans être bloqués

```php
public function handle(Request $request, Closure $next): Response
{
    // Autoriser les requêtes OPTIONS (preflight CORS) sans vérification
    if ($request->method() === 'OPTIONS') {
        return $next($request);
    }
    // ... reste du code
}
```

### 4. Frontend React (`wapfront/src/services/chatApi.js`)

**Modifications :**
- Changé l'URL par défaut de `localhost:8000` à `localhost:8001`
- Amélioré la fonction `broadcastingApi.authenticate()` pour utiliser l'URL correcte

```javascript
const CHAT_API_BASE_URL = import.meta.env.VITE_CHAT_API_URL || 'http://localhost:8001/chat-service/backend/api/v1';
```

## Tester la configuration CORS

### Méthode 1 : Page de test HTML

1. Ouvrez le fichier `wapfront/test-cors.html` dans votre navigateur
2. Cliquez sur les boutons de test dans l'ordre :
   - 1️⃣ Test Health Check
   - 2️⃣ Test CORS (OPTIONS)
   - 3️⃣ Test Login
   - 4️⃣ Test Conversations
   - 5️⃣ Test Users
3. Vérifiez que tous les tests passent

### Méthode 2 : Console du navigateur

1. Ouvrez la console du navigateur (F12)
2. Exécutez ce code :

```javascript
// Test Health Check
fetch('http://localhost:8001/chat-service/backend/api/v1/health', {
    method: 'GET',
    mode: 'cors'
})
.then(response => response.json())
.then(data => console.log('✅ Health Check:', data))
.catch(error => console.error('❌ Erreur:', error));

// Test CORS (OPTIONS)
fetch('http://localhost:8001/chat-service/backend/api/v1/conversations', {
    method: 'OPTIONS',
    mode: 'cors'
})
.then(response => {
    console.log('✅ CORS Options OK');
    console.log('Access-Control-Allow-Origin:', response.headers.get('Access-Control-Allow-Origin'));
})
.catch(error => console.error('❌ Erreur CORS:', error));
```

### Méthode 3 : cURL

```bash
# Test Health Check
curl -i http://localhost:8001/chat-service/backend/api/v1/health

# Test CORS (OPTIONS)
curl -i -X OPTIONS http://localhost:8001/chat-service/backend/api/v1/conversations \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: authorization"
```

## Vérifier les headers CORS

Une réponse du chat-service devrait contenir ces headers CORS :

```
Access-Control-Allow-Origin: http://localhost:5174
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Application-ID, Accept
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 86400
```

## Dépannage

### Le CORS ne fonctionne toujours pas

1. **Videz le cache du navigateur**
   - Chrome : Ctrl+Shift+Delete → Clear cache
   - Firefox : Ctrl+Shift+Delete → Clear cache

2. **Redémarrez le serveur PHP**
   ```bash
   cd chat-service
   php artisan serve --port=8001
   ```

3. **Videz le cache de configuration Laravel**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Vérifiez les logs Laravel**
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. **Testez avec un autre navigateur ou en mode incognito**

### Erreur "Application non autorisée"

Le middleware `EnsureApplicationIsValid` peut bloquer les requêtes. Assurez-vous de :

1. Envoyer le header `X-Application-ID` avec vos requêtes
2. Utiliser une valeur valide : `default`, `web-app`, `mobile-app`, ou `admin-panel`
3. OU simplement ne pas utiliser ce middleware (il n'est pas activé par défaut dans les routes)

### Les requêtes OPTIONS sont bloquées

Vérifiez que le middleware `HandleCors` est activé dans `bootstrap/app.php` :

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \Illuminate\Http\Middleware\HandleCors::class, // ✅ Doit être présent
    ]);
})
```

## Structure des requêtes réussies

Exemple de requête avec authentification :

```javascript
fetch('http://localhost:8001/chat-service/backend/api/v1/conversations', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer votre_token_ici',
        'X-Application-ID': 'web-app' // Optionnel mais recommandé
    },
    credentials: 'include'
})
.then(response => response.json())
.then(data => console.log(data));
```

## Production

En production, remplacez `localhost:5174` par votre domaine de production dans :

- `config/cors.php` : `allowed_origins`
- `config/sanctum.php` : `stateful`

Exemple pour production :

```php
// config/cors.php
'allowed_origins' => [
    'https://monapp.com',
    'https://www.monapp.com',
],

// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'monapp.com,www.monapp.com')),
```

## Conclusion

Après ces modifications, votre frontend React (localhost:5174) devrait pouvoir communiquer avec le backend chat-service (localhost:8001) sans erreur CORS.

Utilisez la page de test `test-cors.html` pour vérifier que tout fonctionne correctement.


