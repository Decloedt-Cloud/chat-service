# Test simple de connexion
$body = @{
    email = "alice@test.com"
    password = "password123"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/auth/login" -Method POST -Body $body -ContentType "application/json"

Write-Host "✅ Connexion réussie !" -ForegroundColor Green
Write-Host "Token: $($response.data.token.Substring(0,50))..." -ForegroundColor Cyan
Write-Host "User: $($response.data.user.name) ($($response.data.user.email))" -ForegroundColor Yellow

# Sauvegarder le token dans une variable pour l'utiliser ensuite
$token = $response.data.token
$token | Out-File -FilePath "token.txt" -Encoding utf8
Write-Host "`nToken sauvegardé dans token.txt" -ForegroundColor Gray

















