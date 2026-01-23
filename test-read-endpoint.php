<?php

/**
 * Test de l'endpoint /api/v1/conversations/{id}/read
 * 
 * Pour tester :
 * php test-read-endpoint.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// 1. Récupérer un token valide
echo "==========================================\n";
echo "Test de l'endpoint /conversations/{id}/read\n";
echo "==========================================\n\n";

// Récupérer un utilisateur (user avec ID 2)
$user = \App\Models\User::find(2);

if (!$user) {
    echo "❌ Erreur: Utilisateur ID 2 introuvable\n";
    exit(1);
}

echo "✅ Utilisateur trouvé: {$user->name} (ID: {$user->id})\n";

// Générer un token pour cet utilisateur
$token = $user->createToken('test-read')->plainTextToken;

echo "✅ Token généré: {$token}\n\n";

// 2. Récupérer une conversation (ID 8)
$conversation = \App\Models\Conversation::find(8);

if (!$conversation) {
    echo "❌ Erreur: Conversation ID 8 introuvable\n";
    exit(1);
}

echo "✅ Conversation trouvée: {$conversation->name} (ID: {$conversation->id})\n\n";

// 3. Faire une requête HTTP à l'endpoint
$url = 'http://localhost:8000/api/v1/conversations/8/read';
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'X-Application-ID: test-app-001',
    ],
]);

echo "📤 Envoi de la requête à: {$url}\n";
echo "   Headers:\n";
echo "   - Content-Type: application/json\n";
echo "   - Authorization: Bearer {$token}\n";
echo "   - X-Application-ID: test-app-001\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur cURL: {$error}\n";
    exit(1);
}

echo "📥 Réponse HTTP {$httpCode}:\n";
echo $response . "\n\n";

// 4. Vérifier si l'endpoint fonctionne
$data = json_decode($response, true);

if (!$data) {
    echo "❌ Erreur: Réponse JSON invalide\n";
    exit(1);
}

if ($data['success'] ?? false) {
    echo "✅ Succès! Message: {$data['message']}\n\n";
} else {
    echo "❌ Erreur: {$data['message'] ?? 'Erreur inconnue'}\n\n";
}

// 5. Vérifier dans la base de données
echo "==========================================\n";
echo "Vérification dans la base de données\n";
echo "==========================================\n\n";

$participant = $conversation->participants()->where('user_id', $user->id)->first();

if (!$participant) {
    echo "❌ Erreur: Participant introuvable\n";
    exit(1);
}

echo "✅ Participant trouvé\n";
echo "   last_read_at: " . ($participant->last_read_at ? $participant->last_read_at->toIso8601String() : 'NULL') . "\n";
echo "   unread_count: {$participant->unread_count}\n\n";

// 6. Vérifier les logs
echo "==========================================\n";
echo "Vérification des logs Laravel\n";
echo "==========================================\n\n";

$logPath = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lastLines = array_slice(file($logPath), -20);
    echo "Dernières 20 lignes du fichier de log:\n";
    echo implode("\n", $lastLines) . "\n";
} else {
    echo "❌ Fichier de log introuvable: {$logPath}\n";
}

