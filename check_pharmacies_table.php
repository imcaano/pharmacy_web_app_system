<?php
require_once 'config/database.php';

echo "=== Pharmacies Table Check ===\n\n";

try {
    // Check pharmacies table structure
    $columns = $conn->query("DESCRIBE pharmacies")->fetchAll(PDO::FETCH_ASSOC);
    echo "Pharmacies table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    echo "\n";
    
    // Check all pharmacies data
    $pharmacies = $conn->query("SELECT * FROM pharmacies")->fetchAll(PDO::FETCH_ASSOC);
    echo "All pharmacies:\n";
    foreach ($pharmacies as $pharmacy) {
        echo "- ID: {$pharmacy['id']}, Name: {$pharmacy['name']}, Address: {$pharmacy['address']}, Phone: {$pharmacy['phone']}\n";
    }
    echo "\n";
    
    // Check users table structure
    $columns = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "Users table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 