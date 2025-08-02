<?php
// Test cart functionality
require_once 'config/database.php';

echo "=== Test Cart Functionality ===\n\n";

try {
    // Test adding item to cart
    echo "1. Testing add to cart...\n";
    
    $cartData = json_encode([
        'customer_id' => 12,
        'medicine_id' => 4,
        'quantity' => 2
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $cartData
        ]
    ]);
    
    $url = 'http://192.168.18.12/pharmacy_web_app_system/api/cart.php';
    $response = file_get_contents($url, false, $context);
    echo "Add to cart response: $response\n\n";
    
    // Test fetching cart items
    echo "2. Testing fetch cart items...\n";
    $fetchUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/cart.php?customer_id=12';
    $fetchResponse = file_get_contents($fetchUrl);
    echo "Fetch cart response: $fetchResponse\n\n";
    
    echo "✅ Cart functionality test completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 