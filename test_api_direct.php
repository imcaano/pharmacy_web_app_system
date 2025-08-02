<?php
// Test the pharmacy profile API directly
echo "=== Testing Pharmacy Profile API ===\n\n";

// Test POST (fetch profile for pharmacy_id = 11)
$url = 'http://192.168.18.12/pharmacy_web_app_system/api/pharmacy_profile.php';
$postData = json_encode(['pharmacy_id' => 11]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

echo "Testing POST (fetch profile for pharmacy_id = 11):\n";
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Error: Could not connect to API\n";
    $error = error_get_last();
    echo "Error details: " . print_r($error, true) . "\n";
} else {
    echo "Response: $response\n";
}

echo "\n=== Testing Medicines API ===\n";

// Test GET medicines for pharmacy_id = 11
$medicinesUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?pharmacy_id=11';
echo "Testing GET medicines for pharmacy_id = 11:\n";
$medicinesResponse = file_get_contents($medicinesUrl);

if ($medicinesResponse === false) {
    echo "Error: Could not connect to medicines API\n";
    $error = error_get_last();
    echo "Error details: " . print_r($error, true) . "\n";
} else {
    echo "Response: $medicinesResponse\n";
}
?> 