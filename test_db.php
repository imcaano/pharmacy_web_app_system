<?php
require_once 'config/database.php';

echo "=== Database Test ===\n\n";

try {
    // Check if users table exists
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Available tables: " . implode(", ", $tables) . "\n\n";
    
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
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 