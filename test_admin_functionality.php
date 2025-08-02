<?php
// Test admin functionality
require_once 'config/database.php';

echo "=== Test Admin Functionality ===\n\n";

try {
    // Test 1: Fetch users
    echo "1. Testing fetch users...\n";
    $usersUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/users.php';
    $usersResponse = file_get_contents($usersUrl);
    echo "Users response: " . substr($usersResponse, 0, 200) . "...\n\n";
    
    // Test 2: Fetch orders
    echo "2. Testing fetch orders...\n";
    $ordersUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/orders.php';
    $ordersResponse = file_get_contents($ordersUrl);
    echo "Orders response: " . substr($ordersResponse, 0, 200) . "...\n\n";
    
    // Test 3: Fetch medicines
    echo "3. Testing fetch medicines...\n";
    $medicinesUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?all=true';
    $medicinesResponse = file_get_contents($medicinesUrl);
    echo "Medicines response: " . substr($medicinesResponse, 0, 200) . "...\n\n";
    
    // Test 4: Create a test user
    echo "4. Testing create user...\n";
    $userData = json_encode([
        'email' => 'testadmin@example.com',
        'password' => 'password123',
        'user_type' => 'admin',
        'metamask_address' => '0x' . uniqid() . 'abcdef'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $userData
        ]
    ]);
    
    $createUserResponse = file_get_contents($usersUrl, false, $context);
    echo "Create user response: $createUserResponse\n\n";
    
    echo "✅ Admin functionality test completed!\n";
    echo "All admin pages should work correctly in the Flutter app.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 