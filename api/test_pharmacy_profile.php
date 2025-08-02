<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Pharmacy Profile Test ===\n\n";

try {
    // Check if users table exists
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Available tables: " . implode(", ", $tables) . "\n\n";
    
    // Check users table structure
    $columns = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "Users table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    echo "\n";
    
    // Check all users
    $users = $conn->query("SELECT id, email, user_type, metamask_address FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "All users in database:\n";
    foreach ($users as $user) {
        echo "- ID: {$user['id']}, Email: {$user['email']}, Type: {$user['user_type']}, MetaMask: {$user['metamask_address']}\n";
    }
    echo "\n";
    
    // Check pharmacy users specifically
    $pharmacies = $conn->query("SELECT id, email, user_type, metamask_address FROM users WHERE user_type = 'pharmacy'")->fetchAll(PDO::FETCH_ASSOC);
    echo "Pharmacy users:\n";
    foreach ($pharmacies as $pharmacy) {
        echo "- ID: {$pharmacy['id']}, Email: {$pharmacy['email']}, MetaMask: {$pharmacy['metamask_address']}\n";
    }
    echo "\n";
    
    // Check medicines table
    $medicines = $conn->query("SELECT id, name, pharmacy_id, price, stock_quantity FROM medicines")->fetchAll(PDO::FETCH_ASSOC);
    echo "All medicines:\n";
    foreach ($medicines as $medicine) {
        echo "- ID: {$medicine['id']}, Name: {$medicine['name']}, Pharmacy ID: {$medicine['pharmacy_id']}, Price: {$medicine['price']}, Stock: {$medicine['stock_quantity']}\n";
    }
    echo "\n";
    
    // Test pharmacy profile API
    echo "Testing pharmacy profile API with pharmacy_id = 1:\n";
    
    // Simulate POST request to fetch profile
    $postData = json_encode(['pharmacy_id' => 1]);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = json_decode($postData, true);
    
    ob_start();
    include 'pharmacy_profile.php';
    $response = ob_get_clean();
    
    echo "API Response: $response\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 