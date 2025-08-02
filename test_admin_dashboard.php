<?php
header('Content-Type: application/json');
require_once 'config/database.php';

echo "Testing Admin Dashboard API endpoints...\n\n";

// Test 1: Fetch all users
echo "1. Testing fetchUsers...\n";
$url = 'http://192.168.18.12/pharmacy_web_app_system/api/users.php';
$response = file_get_contents($url);
$data = json_decode($response, true);
if ($data['success']) {
    echo "✓ Users fetched successfully: " . count($data['users']) . " users found\n";
} else {
    echo "✗ Failed to fetch users: " . ($data['error'] ?? 'Unknown error') . "\n";
}

// Test 2: Fetch all orders
echo "\n2. Testing fetchOrders...\n";
$url = 'http://192.168.18.12/pharmacy_web_app_system/api/orders.php';
$response = file_get_contents($url);
$data = json_decode($response, true);
if ($data['success']) {
    echo "✓ Orders fetched successfully: " . count($data['orders']) . " orders found\n";
} else {
    echo "✗ Failed to fetch orders: " . ($data['error'] ?? 'Unknown error') . "\n";
}

// Test 3: Fetch all medicines
echo "\n3. Testing fetchAllMedicines...\n";
$url = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?all=true';
$response = file_get_contents($url);
$data = json_decode($response, true);
if ($data['success']) {
    echo "✓ Medicines fetched successfully: " . count($data['medicines']) . " medicines found\n";
} else {
    echo "✗ Failed to fetch medicines: " . ($data['error'] ?? 'Unknown error') . "\n";
}

echo "\n✅ Admin Dashboard API tests completed!\n";
?> 