<?php
// Test medicine adding functionality
require_once 'config/database.php';

echo "=== Test Medicine Adding ===\n\n";

try {
    // First, let's check what pharmacies exist
    echo "1. Checking existing pharmacies...\n";
    $stmt = $conn->prepare("SELECT p.id, p.user_id, p.pharmacy_name, u.email FROM pharmacies p LEFT JOIN users u ON p.user_id = u.id");
    $stmt->execute();
    $pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pharmacies as $pharmacy) {
        echo "   Pharmacy ID: {$pharmacy['id']}, User ID: {$pharmacy['user_id']}, Name: {$pharmacy['pharmacy_name']}, Email: {$pharmacy['email']}\n";
    }
    
    // Test adding medicine for pharmacy ID 3 (shaafi pharmacy)
    echo "\n2. Testing medicine adding for pharmacy ID 3...\n";
    
    $medicineData = json_encode([
        'name' => 'Test Medicine ' . time(),
        'description' => 'Test description',
        'price' => 25.50,
        'stock_quantity' => 100,
        'pharmacy_id' => 11  // This is user_id for pharmacy ID 3
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $medicineData
        ]
    ]);
    
    $url = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php';
    $response = file_get_contents($url, false, $context);
    echo "Response: $response\n\n";
    
    // Test fetching medicines for the same pharmacy
    echo "3. Testing medicine fetching for pharmacy ID 3...\n";
    $fetchUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?pharmacy_id=11';
    $fetchResponse = file_get_contents($fetchUrl);
    echo "Fetch Response: $fetchResponse\n\n";
    
    echo "✅ Medicine adding test completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 