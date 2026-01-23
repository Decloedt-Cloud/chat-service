<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Create a test user
$user = User::firstOrCreate([
    'email' => 'test@example.com',
    'name' => 'Test User',
    'password' => 'password123', // Remove bcrypt for testing
]);

echo "Test user created/exists with ID: " . $user->id . "\n";

// Test the conversations relationship
try {
    $conversations = $user->conversations()->get();
    echo "Conversations count: " . $conversations->count() . "\n";
    
    if ($conversations->count() > 0) {
        echo "First conversation ID: " . $conversations->first()->id . "\n";
    }
} catch (\Exception $e) {
    echo "Error accessing conversations: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "Error file: " . $e->getFile() . "\n";
    echo "Error line: " . $e->getLine() . "\n";
}