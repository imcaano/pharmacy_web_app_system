<?php
require_once 'config/database.php';

echo "=== Pharmacy Relationship Check ===\n\n";

try {
    // Check users table
    $users = $conn->query("SELECT id, email, user_type FROM users WHERE user_type = 'pharmacy'")->fetchAll(PDO::FETCH_ASSOC);
    echo "Pharmacy users:\n";
    foreach ($users as $user) {
        echo "- User ID: {$user['id']}, Email: {$user['email']}\n";
    }
    echo "\n";
    
    // Check pharmacies table
    $pharmacies = $conn->query("SELECT id, user_id, pharmacy_name, address FROM pharmacies")->fetchAll(PDO::FETCH_ASSOC);
    echo "Pharmacies:\n";
    foreach ($pharmacies as $pharmacy) {
        echo "- Pharmacy ID: {$pharmacy['id']}, User ID: {$pharmacy['user_id']}, Name: {$pharmacy['pharmacy_name']}, Address: {$pharmacy['address']}\n";
    }
    echo "\n";
    
    // Check the relationship
    echo "Relationship check:\n";
    $stmt = $conn->prepare("
        SELECT u.id as user_id, u.email, p.id as pharmacy_id, p.pharmacy_name, p.address 
        FROM users u 
        LEFT JOIN pharmacies p ON u.id = p.user_id 
        WHERE u.user_type = 'pharmacy'
    ");
    $stmt->execute();
    $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($relationships as $rel) {
        echo "- User ID: {$rel['user_id']}, Email: {$rel['email']}, Pharmacy ID: {$rel['pharmacy_id']}, Name: {$rel['pharmacy_name']}\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 