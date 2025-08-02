<?php
require_once 'config/database.php';

echo "=== Medicines Check ===\n\n";

try {
    // Check all medicines
    $medicines = $conn->query("SELECT * FROM medicines")->fetchAll(PDO::FETCH_ASSOC);
    echo "All medicines:\n";
    foreach ($medicines as $medicine) {
        echo "- ID: {$medicine['id']}, Name: {$medicine['name']}, Pharmacy ID: {$medicine['pharmacy_id']}, Price: {$medicine['price']}, Stock: {$medicine['stock_quantity']}\n";
    }
    echo "\n";
    
    // Check medicines for pharmacy_id = 11
    $medicines11 = $conn->query("SELECT * FROM medicines WHERE pharmacy_id = 11")->fetchAll(PDO::FETCH_ASSOC);
    echo "Medicines for pharmacy_id = 11:\n";
    foreach ($medicines11 as $medicine) {
        echo "- ID: {$medicine['id']}, Name: {$medicine['name']}, Price: {$medicine['price']}, Stock: {$medicine['stock_quantity']}\n";
    }
    echo "\n";
    
    // Check medicines for pharmacy_id = 1
    $medicines1 = $conn->query("SELECT * FROM medicines WHERE pharmacy_id = 1")->fetchAll(PDO::FETCH_ASSOC);
    echo "Medicines for pharmacy_id = 1:\n";
    foreach ($medicines1 as $medicine) {
        echo "- ID: {$medicine['id']}, Name: {$medicine['name']}, Price: {$medicine['price']}, Stock: {$medicine['stock_quantity']}\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 