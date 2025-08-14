<?php
echo "<h2>Testing Prescription Acceptance API</h2>";

// Test 1: Get all prescriptions
echo "<h3>Test 1: Getting all prescriptions</h3>";
$url = 'http://localhost/pharmacy_web_app_system/api/prescriptions.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

// Test 2: Accept a prescription (assuming prescription ID 1 exists)
echo "<h3>Test 2: Accepting prescription (ID: 1)</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'prescription_id' => 1,
    'action' => 'accept'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

// Test 3: Reject a prescription (assuming prescription ID 2 exists)
echo "<h3>Test 3: Rejecting prescription (ID: 2)</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'prescription_id' => 2,
    'action' => 'reject',
    'notes' => 'Test rejection from testing script'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

// Test 4: Get prescriptions again to see the changes
echo "<h3>Test 4: Getting prescriptions again to see changes</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

echo "<h3>Testing Complete!</h3>";
echo "<p>Check the responses above to verify the API is working correctly.</p>";
echo "<p>You can also:</p>";
echo "<ul>";
echo "<li>Visit <a href='pharmacy/prescriptions.php' target='_blank'>Pharmacy Prescriptions Page</a> to test the web interface</li>";
echo "<li>Run the Flutter app to test the mobile interface</li>";
echo "</ul>";
?>
