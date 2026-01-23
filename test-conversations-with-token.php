<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

echo "=== Test API Login et Conversations ===\n\n";

try {
    // Step 1: Login
    echo "1. Test Login:\n";
    $user = User::find(1);
    
    if (!$user) {
        echo "❌ User not found!\n";
        exit(1);
    }
    
    echo "   User: {$user->name} (ID: {$user->id})\n";
    
    // Create token
    $token = $user->createToken('test-api')->plainTextToken;
    echo "   Token: {$token}\n\n";
    
    // Step 2: Test conversations endpoint
    echo "2. Test GET /api/v1/conversations:\n";
    
    $request = Request::create('/api/v1/conversations', 'GET');
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('X-Application-ID', 'test-app-001');
    $request->headers->set('Content-Type', 'application/json');
    
    echo "   Headers:\n";
    echo "     Authorization: Bearer {$token}\n";
    echo "     X-Application-ID: test-app-001\n\n";
    
    $response = $kernel->handle($request);
    $statusCode = $response->status();
    
    echo "   Status Code: {$statusCode}\n\n";
    
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "   Response structure:\n";
    echo "   success: " . ($data['success'] ?? 'N/A') . "\n";
    
    if (isset($data['data'])) {
        if (is_array($data['data'])) {
            echo "   data type: array\n";
            echo "   data count: " . count($data['data']) . "\n";
            
            if (count($data['data']) > 0) {
                $firstConv = $data['data'][0];
                echo "   First conversation ID: " . ($firstConv['id'] ?? 'N/A') . "\n";
                echo "   First conversation type: " . ($firstConv['type'] ?? 'N/A') . "\n";
                echo "   First conversation display_name: " . ($firstConv['display_name'] ?? 'N/A') . "\n";
            }
        } else {
            echo "   data type: object/class\n";
            echo "   data keys: " . implode(', ', array_keys($data['data'])) . "\n";
            
            if (isset($data['data']['data'])) {
                echo "   data.data exists (pagination)\n";
                echo "   data.data type: " . gettype($data['data']['data']) . "\n";
                echo "   data.data count: " . count($data['data']['data']) . "\n";
            }
        }
    } else {
        echo "   data: NOT SET\n";
    }
    
    echo "\n";
    
    if ($statusCode === 200 && isset($data['success']) && $data['success'] === true) {
        echo "✅ API TEST PASSED!\n";
        
        // Check what frontend expects
        echo "\n🔍 Frontend expects: data.data (for pagination) OR data (for array)\n";
        echo "🔍 API returns: ";
        if (is_array($data['data'])) {
            echo "Array (direct array)\n";
            echo "✅ Frontend code 'conversations = data.data' should work!\n";
        } else {
            echo "Object (pagination)\n";
            echo "❌ Frontend needs 'conversations = data.data.data' for pagination\n";
        }
    } else {
        echo "❌ API TEST FAILED!\n";
        echo "Response: " . $content . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

















