<?php

// Test API endpoint
$url = 'http://localhost:8000/api/v1/conversations';
$headers = [
    'Authorization: Bearer test-token',
    'X-Application-ID: test-app-001',
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "Status Code: " . $statusCode . "\n";
echo "Error: " . $error . "\n";
echo "Response: " . $response . "\n";

if ($statusCode == 500) {
    echo "\n=== ERROR 500 DETECTED ===\n";
    echo "This indicates a server-side error in the ConversationController@index method.\n";
    echo "Possible causes:\n";
    echo "1. Database connection issues\n";
    echo "2. Missing user authentication\n";
    echo "3. Error in Eloquent relationships\n";
    echo "4. Missing required headers\n";
}
















