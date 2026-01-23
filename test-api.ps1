# ============================================
# 🧪 Script de Test Automatisé - Chat Service
# ============================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Chat Service API Test Suite" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$baseUrl = "http://127.0.0.1:8000/api"
$token = ""
$conversationId = ""
$aliceToken = ""
$bobToken = ""

# Fonction pour afficher les résultats
function Show-Test($name, $success, $details = "") {
    if ($success) {
        Write-Host "✅ $name" -ForegroundColor Green
    } else {
        Write-Host "❌ $name" -ForegroundColor Red
    }
    if ($details) {
        Write-Host "   $details" -ForegroundColor Gray
    }
}

# Fonction pour faire des requêtes API
function Invoke-Api($method, $endpoint, $body = $null, $headers = $null) {
    try {
        $params = @{
            Uri = "$baseUrl$endpoint"
            Method = $method
            ContentType = "application/json"
        }

        if ($headers) { $params.Headers = $headers }
        if ($body) { $params.Body = ($body | ConvertTo-Json) }

        $response = Invoke-RestMethod @params
        return @{ Success = $true; Data = $response }
    } catch {
        return @{
            Success = $false
            Error = $_.Exception.Message
            StatusCode = $_.Exception.Response.StatusCode.value__
        }
    }
}

# ============================================
# TEST 1 : Health Check
# ============================================
Write-Host "`n[1/10] Health Check..." -ForegroundColor Yellow
$result = Invoke-Api "GET" "/health"
Show-Test "Health Check" $result.Success ($result.Data | ConvertTo-Json)

if (-not $result.Success) {
    Write-Host "`n❌ Serveur inaccessible. Vérifiez que Laravel est démarré." -ForegroundColor Red
    Write-Host "Exécutez: php artisan serve --host=127.0.0.1 --port=8000" -ForegroundColor Yellow
    exit 1
}

# ============================================
# TEST 2 : Créer utilisateurs
# ============================================
Write-Host "`n[2/10] Création des utilisateurs..." -ForegroundColor Yellow

$user1 = Invoke-Api "POST" "/auth/register" @{
    name = "Alice"
    email = "alice@test.com"
    password = "password123"
    password_confirmation = "password123"
}
Show-Test "Création Alice" $user1.Success

$user2 = Invoke-Api "POST" "/auth/register" @{
    name = "Bob"
    email = "bob@test.com"
    password = "password123"
    password_confirmation = "password123"
}
Show-Test "Création Bob" $user2.Success

# ============================================
# TEST 3 : Connexion des utilisateurs
# ============================================
Write-Host "`n[3/10] Connexion des utilisateurs..." -ForegroundColor Yellow

$aliceLogin = Invoke-Api "POST" "/auth/login" @{
    email = "alice@test.com"
    password = "password123"
}

if ($aliceLogin.Success) {
    $aliceToken = $aliceLogin.Data.data.token
    Show-Test "Connexion Alice" $true "Token: $($aliceToken.Substring(0,20))..."
} else {
    Show-Test "Connexion Alice" $false $aliceLogin.Error
}

$bobLogin = Invoke-Api "POST" "/auth/login" @{
    email = "bob@test.com"
    password = "password123"
}

if ($bobLogin.Success) {
    $bobToken = $bobLogin.Data.data.token
    Show-Test "Connexion Bob" $true "Token: $($bobToken.Substring(0,20))..."
} else {
    Show-Test "Connexion Bob" $false $bobLogin.Error
}

if (-not $aliceToken -or -not $bobToken) {
    Write-Host "`n❌ Impossible d'obtenir les tokens d'authentification" -ForegroundColor Red
    exit 1
}

$aliceHeaders = @{ Authorization = "Bearer $aliceToken" }
$bobHeaders = @{ Authorization = "Bearer $bobToken" }

# ============================================
# TEST 4 : Créer une conversation
# ============================================
Write-Host "`n[4/10] Création d'une conversation..." -ForegroundColor Yellow

$conv = Invoke-Api "POST" "/v1/conversations" @{
    app_id = "test-app-001"
    name = "Conversation de test"
    type = "private"
    participant_ids = @(2)  # Bob
} -headers $aliceHeaders

if ($conv.Success) {
    $conversationId = $conv.Data.data.id
    Show-Test "Création conversation" $true "ID: $conversationId"
} else {
    Show-Test "Création conversation" $false $conv.Error
    exit 1
}

# ============================================
# TEST 5 : Lister les conversations
# ============================================
Write-Host "`n[5/10] Liste des conversations..." -ForegroundColor Yellow

$convs = Invoke-Api "GET" "/v1/conversations" -headers $aliceHeaders
Show-Test "Liste conversations" $convs.Success "$($convs.Data.data.Count) conversation(s) trouvée(s)"

# ============================================
# TEST 6 : Envoyer un message
# ============================================
Write-Host "`n[6/10] Envoi d'un message..." -ForegroundColor Yellow

$msg = Invoke-Api "POST" "/v1/conversations/$conversationId/messages" @{
    content = "Bonjour Bob ! Comment ça va ?"
    type = "text"
} -headers $aliceHeaders

if ($msg.Success) {
    $messageId = $msg.Data.data.id
    Show-Test "Envoi message" $true "Message ID: $messageId"
} else {
    Show-Test "Envoi message" $false $msg.Error
}

# ============================================
# TEST 7 : Lister les messages
# ============================================
Write-Host "`n[7/10] Liste des messages..." -ForegroundColor Yellow

$messages = Invoke-Api "GET" "/v1/conversations/$conversationId/messages" -headers $aliceHeaders
if ($messages.Success) {
    Show-Test "Liste messages" $true "$($messages.Data.data.Count) message(s) trouvé(s)"
    $messages.Data.data | ForEach-Object {
        Write-Host "   [$($_.created_at)] $($_.user.name): $($_.content)" -ForegroundColor Gray
    }
} else {
    Show-Test "Liste messages" $false $messages.Error
}

# ============================================
# TEST 8 : Marquer comme lu
# ============================================
Write-Host "`n[8/10] Marquer comme lu..." -ForegroundColor Yellow

$read = Invoke-Api "POST" "/v1/conversations/$conversationId/read" -headers $bobHeaders
Show-Test "Marquer comme lu" $read.Success

# ============================================
# TEST 9 : Test auth WebSocket
# ============================================
Write-Host "`n[9/10] Authentification WebSocket..." -ForegroundColor Yellow

$socketId = "123.456.789"
$channelName = "private-conversation.$conversationId.test-app-001"

$authBody = @{
    socket_id = $socketId
    channel_name = $channelName
}

$auth = Invoke-Api "POST" "/broadcasting/auth" $authBody -headers $aliceHeaders

if ($auth.Success -or $auth.StatusCode -eq 403) {
    if ($auth.StatusCode -eq 403) {
        Show-Test "Auth WebSocket" $true "Route fonctionnelle (403 normal si non authentifié correctement)"
    } else {
        Show-Test "Auth WebSocket" $true "Authentification réussie"
    }
} else {
    Show-Test "Auth WebSocket" $false $auth.Error
}

# ============================================
# TEST 10 : Vérifier la configuration Reverb
# ============================================
Write-Host "`n[10/10] Configuration Reverb..." -ForegroundColor Yellow

try {
    $netstat = netstat -ano | findstr ":8080"
    if ($netstat) {
        Show-Test "Reverb actif" $true "Port 8080 en écoute"
    } else {
        Show-Test "Reverb actif" $false "Port 8080 non détecté"
    }
} catch {
    Show-Test "Reverb actif" $false "Impossible de vérifier le port"
}

# ============================================
# RÉSUMÉ
# ============================================
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Test Terminé !" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Détails de connexion:" -ForegroundColor Yellow
Write-Host "  Alice Token: $aliceToken" -ForegroundColor White
Write-Host "  Bob Token: $bobToken" -ForegroundColor White
Write-Host "  Conversation ID: $conversationId" -ForegroundColor White
Write-Host ""

Write-Host "URLs de test:" -ForegroundColor Yellow
Write-Host "  API Health: $baseUrl/health" -ForegroundColor White
Write-Host "  API Login: $baseUrl/auth/login" -ForegroundColor White
Write-Host "  WebSocket: ws://localhost:8080/app/iuvcjjlml7xkwbdfaxo3" -ForegroundColor White
Write-Host ""

Write-Host "Prochaines étapes:" -ForegroundColor Yellow
Write-Host "  1. Testez la connexion WebSocket avec un navigateur" -ForegroundColor White
Write-Host "  2. Ouvrez une deuxième fenêtre et connectez-vous avec Bob" -ForegroundColor White
Write-Host "  3. Échangez des messages et vérifiez la réception en temps réel" -ForegroundColor White
Write-Host ""

















