<?php
require_once 'config/database.php';

echo "=== Simple Medicine Test ===\n\n";

try {
    // Insert a test medicine directly
    $stmt = $conn->prepare("
        INSERT INTO medicines (name, description, price, stock_quantity, pharmacy_id, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $name = 'Test Medicine Direct';
    $description = 'Test description';
    $price = 25.00;
    $stockQuantity = 100;
    $pharmacyId = 11;
    
    $result = $stmt->execute([$name, $description, $price, $stockQuantity, $pharmacyId]);
    
    if ($result) {
        $medicineId = $conn->lastInsertId();
        echo "✅ Medicine created successfully!\n";
        echo "- Medicine ID: $medicineId\n";
        echo "- Name: $name\n";
        echo "- Pharmacy ID: $pharmacyId\n";
        echo "- Price: $price\n";
        echo "- Stock: $stockQuantity\n\n";
    } else {
        echo "❌ Failed to create medicine\n\n";
    }
    
    // Check medicines for pharmacy_id = 11
    $medicines = $conn->query("SELECT * FROM medicines WHERE pharmacy_id = 11")->fetchAll(PDO::FETCH_ASSOC);
    echo "Medicines for pharmacy_id = 11:\n";
    foreach ($medicines as $medicine) {
        echo "- ID: {$medicine['id']}, Name: {$medicine['name']}, Price: {$medicine['price']}, Stock: {$medicine['stock_quantity']}\n";
    }
    echo "\n";
    
    // Check all medicines
    $allMedicines = $conn->query("SELECT * FROM medicines ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "All medicines (latest 10):\n";
    foreach ($allMedicines as $medicine) {
        echo "- ID: {$medicine['id']}, Name: {$medicine['name']}, Pharmacy ID: {$medicine['pharmacy_id']}, Price: {$medicine['price']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 