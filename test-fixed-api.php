<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Models\User;

echo "=== Test API: GET /api/v1/conversations ===\n\n";

try {
    // Get a valid user and token
    $user = User::find(1);
    $token = $user->createToken('test-api')->plainTextToken;
    
    echo "User: {$user->name} (ID: {$user->id})\n";
    echo "Token: {$token}\n\n";
    
    // Create request
    $request = Request::create('/api/v1/conversations', 'GET');
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('X-Application-ID', 'test-app-001');
    $request->headers->set('Content-Type', 'application/json');
    
    echo "Headers:\n";
    echo "  Authorization: Bearer {$token}\n";
    echo "  X-Application-ID: test-app-001\n\n";
    
    // Handle request
    $response = $kernel->handle($request);
    
    echo "Status Code: {$response->status()}\n\n";
    
    $content = json_decode($response->getContent(), true);
    
    echo "Response:\n";
    print_r($content);
    
    if ($response->status() === 200 && isset($content['success']) && $content['success'] === true) {
        echo "\n✅ API TEST PASSED!\n";
    } else {
        echo "\n❌ API TEST FAILED!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

















