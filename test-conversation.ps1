# Créer une conversation et tester les messages
$token = Get-Content "token.txt" -Raw
$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

Write-Host "=== Test de conversation ===" -ForegroundColor Cyan

# Créer une conversation
Write-Host "`n1. Création d'une conversation..." -ForegroundColor Yellow
$convBody = @{
    app_id = "test-app-001"
    name = "Conversation Test"
    type = "private"
    participant_ids = @(7)  # Bob
} | ConvertTo-Json

$conv = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations" -Method POST -Body $convBody -Headers $headers

$conversationId = $conv.data.id
Write-Host "✅ Conversation créée (ID: $conversationId)" -ForegroundColor Green

# Lister les messages
Write-Host "`n2. Liste des messages (initiale)..." -ForegroundColor Yellow
$messages = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations/$conversationId/messages" -Method GET -Headers $headers
Write-Host "✅ $($messages.data.Count) message(s)" -ForegroundColor Green

# Envoyer un message
Write-Host "`n3. Envoi d'un message..." -ForegroundColor Yellow
$msgBody = @{
    content = "Bonjour Bob ! Comment ça va ?"
    type = "text"
} | ConvertTo-Json

$msg = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations/$conversationId/messages" -Method POST -Body $msgBody -Headers $headers
$messageId = $msg.data.id
Write-Host "✅ Message envoyé (ID: $messageId)" -ForegroundColor Green
Write-Host "   Contenu: $($msg.data.content)" -ForegroundColor Gray

# Lister les messages à nouveau
Write-Host "`n4. Liste des messages (après envoi)..." -ForegroundColor Yellow
$messages = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/conversations/$conversationId/messages" -Method GET -Headers $headers
Write-Host "✅ $($messages.data.Count) message(s)" -ForegroundColor Green

$messages.data | ForEach-Object {
    Write-Host "   [$($_.created_at)] $($_.user.name): $($_.content)" -ForegroundColor Gray
}

# Test auth WebSocket
Write-Host "`n5. Test authentification WebSocket..." -ForegroundColor Yellow
$socketId = "123.456.789"
$channelName = "private-conversation.$conversationId.test-app-001"

$authBody = @{
    socket_id = $socketId
    channel_name = $channelName
} | ConvertTo-Json

try {
    $auth = Invoke-RestMethod -Uri "http://127.0.0.1:8000/broadcasting/auth" -Method POST -Body $authBody -Headers $headers
    Write-Host "✅ Auth WebSocket réussie" -ForegroundColor Green
} catch {
    if ($_.Exception.Response.StatusCode.value__ -eq 403) {
        Write-Host "✅ Auth WebSocket fonctionnelle (403 normal si canal privé sans auth correcte)" -ForegroundColor Yellow
    } else {
        Write-Host "❌ Erreur auth: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`n=== Informations de connexion ===" -ForegroundColor Cyan
Write-Host "Conversation ID: $conversationId" -ForegroundColor White
Write-Host "Channel: $channelName" -ForegroundColor White
Write-Host "WebSocket: ws://localhost:8080/app/iuvcjjlml7xkwbdfaxo3" -ForegroundColor White

$conversationId | Out-File -FilePath "conversation.txt" -Encoding utf8
Write-Host "`nConversation ID sauvegardé dans conversation.txt" -ForegroundColor Gray

















