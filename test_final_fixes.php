<?php
// Test final fixes
echo "=== Final Fixes Test ===\n\n";

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

// Test Pharmacy Stats API
$statsUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/pharmacy_stats.php';
$statsData = json_encode(['pharmacy_id' => 3]);

$statsContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $statsData
    ]
]);

echo "Testing Pharmacy Stats API (pharmacy_id = 3):\n";
$statsResponse = file_get_contents($statsUrl, false, $statsContext);
echo "Response: $statsResponse\n\n";

// Test Medicines API
$medicinesUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?pharmacy_id=3';
echo "Testing Medicines API (pharmacy_id = 3):\n";
$medicinesResponse = file_get_contents($medicinesUrl);
echo "Response: $medicinesResponse\n\n";

echo "✅ All tests completed!\n";
?> 