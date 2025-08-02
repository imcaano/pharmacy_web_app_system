<?php
// Test customer medicines browsing
echo "=== Test Customer Medicines Browsing ===\n\n";

try {
    // Test fetching all medicines for customers
    echo "1. Testing customer medicines fetch...\n";
    
    $url = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?all=true';
    $response = file_get_contents($url);
    
    echo "Response: $response\n\n";
    
    // Parse response to show count
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        $count = count($data['medicines']);
        echo "✅ Success! Found $count medicines for customers\n";
        
        // Show first few medicines
        echo "\nFirst 3 medicines:\n";
        for ($i = 0; $i < min(3, $count); $i++) {
            $med = $data['medicines'][$i];
            echo "   - {$med['name']} (${$med['price']}) from {$med['pharmacy_name']}\n";
        }
    } else {
        echo "❌ Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 