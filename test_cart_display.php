<?php
// Test cart display functionality
require_once 'config/database.php';

echo "=== Test Cart Display ===\n\n";

try {
    // First, add some items to cart
    echo "1. Adding items to cart...\n";
    
    $cartData1 = json_encode([
        'customer_id' => 12,
        'medicine_id' => 4,
        'quantity' => 2
    ]);
    
    $cartData2 = json_encode([
        'customer_id' => 12,
        'medicine_id' => 5,
        'quantity' => 1
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $cartData1
        ]
    ]);
    
    $url = 'http://192.168.18.12/pharmacy_web_app_system/api/cart.php';
    $response1 = file_get_contents($url, false, $context);
    echo "Add item 1 response: $response1\n";
    
    // Add second item
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $cartData2
        ]
    ]);
    
    $response2 = file_get_contents($url, false, $context);
    echo "Add item 2 response: $response2\n\n";
    
    // Test fetching cart items
    echo "2. Testing fetch cart items...\n";
    $fetchUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/cart.php?customer_id=12';
    $fetchResponse = file_get_contents($fetchUrl);
    echo "Fetch cart response: $fetchResponse\n\n";
    
    echo "✅ Cart display test completed!\n";
    echo "Cart should now show the added medicines in the Flutter app.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 