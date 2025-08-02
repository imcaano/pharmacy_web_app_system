<?php
// Final test of both APIs
echo "=== Final API Test ===\n\n";

// Test Pharmacy Profile API
$profileUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/pharmacy_profile.php';
$postData = json_encode(['pharmacy_id' => 3]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

echo "Testing Pharmacy Profile API (pharmacy_id = 3):\n";
$response = file_get_contents($profileUrl, false, $context);
echo "Response: $response\n\n";

// Test Medicines API
$medicinesUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?pharmacy_id=3';
echo "Testing Medicines API (pharmacy_id = 3):\n";
$medicinesResponse = file_get_contents($medicinesUrl);
echo "Response: $medicinesResponse\n\n";

// Test Medicine Creation
$createUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php';
$createData = json_encode([
    'name' => 'Final Test Medicine',
    'description' => 'Test description',
    'price' => 45.00,
    'stock_quantity' => 75,
    'pharmacy_id' => 3
]);

$createContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $createData
    ]
]);

echo "Testing Medicine Creation (pharmacy_id = 3):\n";
$createResponse = file_get_contents($createUrl, false, $createContext);
echo "Response: $createResponse\n\n";

echo "✅ All tests completed!\n";
?> 