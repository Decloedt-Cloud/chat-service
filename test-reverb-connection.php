<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test Reverb Connection ===\n\n";

// Configuration du broadcaster
$config = config('broadcasting.connections.reverb');
echo "Configuration Reverb:\n";
echo "  - Key: " . $config['key'] . "\n";
echo "  - Secret: " . substr($config['secret'], 0, 5) . "...\n";
echo "  - App ID: " . $config['app_id'] . "\n";
echo "  - Host: " . $config['options']['host'] . "\n";
echo "  - Port: " . $config['options']['port'] . "\n";
echo "  - Scheme: " . $config['options']['scheme'] . "\n\n";

// Test de connexion HTTP à Reverb
$reverbUrl = $config['options']['scheme'] . '://' . $config['options']['host'] . ':' . $config['options']['port'];
echo "Testing connection to Reverb at: {$reverbUrl}\n";

try {
    $ch = curl_init($reverbUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ Connection error: {$error}\n";
    } else {
        echo "✅ Connected! HTTP {$httpCode}\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Broadcast via Pusher SDK ===\n\n";

// Créer un client Pusher manuellement
$pusher = new Pusher\Pusher(
    $config['key'],
    $config['secret'],
    $config['app_id'],
    [
        'host' => $config['options']['host'],
        'port' => $config['options']['port'],
        'scheme' => $config['options']['scheme'],
        'useTLS' => $config['options']['scheme'] === 'https',
    ]
);

try {
    $result = $pusher->trigger(
        'private-conversation.3.test-app-001',
        'message.sent',
        [
            'message' => [
                'id' => 999,
                'conversation_id' => 3,
                'content' => 'TEST MESSAGE FROM PHP SCRIPT',
                'created_at' => date('c'),
            ],
            'sender' => [
                'id' => 1,
                'name' => 'Test User',
                'email' => 'test@test.com',
            ],
        ]
    );
    
    echo "Pusher trigger result: " . json_encode($result) . "\n";
    
    if ($result) {
        echo "✅ Broadcast sent successfully via Pusher SDK!\n";
    } else {
        echo "❌ Broadcast failed\n";
    }
} catch (Exception $e) {
    echo "❌ Pusher error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

