@echo off
echo ======================================
echo Verification du Backend Chat Service
echo ======================================
echo.

echo [1/4] Verification du serveur PHP...
tasklist | findstr php.exe >nul
if %errorlevel% == 0 (
    echo [OK] PHP est en cours d'execution
) else (
    echo [ERREUR] PHP n'est PAS en cours d'execution
    echo.
    echo Pour demarrer le serveur PHP, executez :
    echo cd C:\xampp\htdocs\WAP\chat-service
    echo php artisan serve --port=8001
    echo.
)

echo.
echo [2/4] Verification du port 8001...
netstat -ano | findstr :8001 >nul
if %errorlevel% == 0 (
    echo [OK] Le port 8001 est ouvert
) else (
    echo [ERREUR] Le port 8001 n'est PAS ouvert
    echo.
    echo Possibles causes :
    echo - Le serveur PHP n'est pas demarre
    echo - Un autre processus utilise le port 8001
    echo.
)

echo.
echo [3/4] Test de l'endpoint health...
curl -s http://localhost:8001/api/v1/health >nul 2>&1
if %errorlevel% == 0 (
    echo [OK] L'endpoint health est accessible
) else (
    echo [ERREUR] L'endpoint health est INACCESSIBLE
    echo.
    echo Verifiez que :
    echo - Le serveur PHP est demarre sur le port 8001
    echo - La configuration CORS est correcte
    echo.
)

echo.
echo [4/4] Test de l'endpoint conversations (avec token requis)...
echo [INFO] Ce test necessite un token d'authentification
echo.

echo ======================================
echo Verification terminee
echo ======================================
echo.
echo Etapes suivantes recommandees :
echo 1. Si le serveur PHP ne tourne pas, demarrez-le :
echo    cd C:\xampp\htdocs\WAP\chat-service
echo    php artisan serve --port=8001
echo.
echo 2. Verifiez votre fichier .env dans wapfront :
echo    VITE_CHAT_API_URL=http://localhost:8001/chat-service/backend/api/v1
echo.
echo 3. Redemarrez votre serveur React :
echo    cd C:\xampp\htdocs\WAP\wapfront
echo    npm run dev
echo.
pause


