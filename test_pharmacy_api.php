<?php
// Test the pharmacy profile API
$url = 'http://192.168.18.12/pharmacy_web_app_system/api/pharmacy_profile.php';

// Test POST (fetch profile)
$postData = json_encode(['pharmacy_id' => 11]);
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

echo "Testing POST (fetch profile):\n";
$response = file_get_contents($url, false, $context);
echo "Response: $response\n\n";

// Test PUT (update profile)
$putData = json_encode([
    'pharmacy_id' => 11,
    'email' => 'test@pharmacy.com',
    'metamask_address' => '0x1234567890abcdef'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'PUT',
        'header' => 'Content-Type: application/json',
        'content' => $putData
    ]
]);

echo "Testing PUT (update profile):\n";
$response = file_get_contents($url, false, $context);
echo "Response: $response\n\n";
?> 