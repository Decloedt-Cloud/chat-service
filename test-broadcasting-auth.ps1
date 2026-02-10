# Test broadcasting auth endpoint
$token = Get-Content "token.txt" -Raw

$body = @{
    socket_id = "123.456.789"
    channel_name = "private-conversation.1.test-app-001"
} | ConvertTo-Json

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

Write-Host "Testing POST /api/v1/broadcasting/auth" -ForegroundColor Cyan
Write-Host "Socket ID: 123.456.789" -ForegroundColor Gray
Write-Host "Channel: private-conversation.1.test-app-001" -ForegroundColor Gray

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/broadcasting/auth" -Method POST -Body $body -Headers $headers

    Write-Host "Status Code: $($response.StatusCode)" -ForegroundColor Yellow

    if ($response.StatusCode -eq 200) {
        Write-Host "SUCCESS: Broadcasting auth working!" -ForegroundColor Green
        $data = $response | ConvertFrom-Json
        Write-Host "Auth Signature: $($data.auth)" -ForegroundColor Cyan
    } elseif ($response.StatusCode -eq 403) {
        Write-Host "Forbidden - This may be expected if user is not participant" -ForegroundColor Yellow
        $data = $response | ConvertFrom-Json
        Write-Host "Message: $($data.message)" -ForegroundColor Gray
    } else {
        Write-Host "FAILED: $($response.StatusCode)" -ForegroundColor Red
        Write-Host "Response: $($response.Content)" -ForegroundColor Gray
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

















