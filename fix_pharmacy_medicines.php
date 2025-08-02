<?php
require_once 'config/database.php';

echo "=== Fix Pharmacy Medicines Relationship ===\n\n";

try {
    // Check current pharmacies
    $pharmacies = $conn->query("SELECT * FROM pharmacies")->fetchAll(PDO::FETCH_ASSOC);
    echo "Current pharmacies:\n";
    foreach ($pharmacies as $pharmacy) {
        echo "- ID: {$pharmacy['id']}, User ID: {$pharmacy['user_id']}, Name: {$pharmacy['pharmacy_name']}\n";
    }
    echo "\n";
    
    // Check if pharmacy record exists for user_id = 11
    $stmt = $conn->prepare("SELECT * FROM pharmacies WHERE user_id = ?");
    $stmt->execute([11]);
    $pharmacy = $stmt->fetch();
    
    if (!$pharmacy) {
        echo "Creating pharmacy record for user_id = 11...\n";
        $insertStmt = $conn->prepare("
            INSERT INTO pharmacies (user_id, pharmacy_name, address, phone, license_number, status, wallet_address, trust_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $insertStmt->execute([
            11, 
            'Test Pharmacy', 
            'Test Address', 
            '1234567890', 
            'LIC123', 
            'active', 
            '0x1234567890abcdef', 
            5.0
        ]);
        
        if ($result) {
            $pharmacyId = $conn->lastInsertId();
            echo "✅ Created pharmacy record with ID: $pharmacyId\n\n";
        } else {
            echo "❌ Failed to create pharmacy record\n\n";
        }
    } else {
        echo "Pharmacy record already exists for user_id = 11\n";
        echo "- Pharmacy ID: {$pharmacy['id']}\n\n";
    }
    
    // Now try to insert a medicine
    $stmt = $conn->prepare("
        INSERT INTO medicines (name, description, price, stock_quantity, pharmacy_id, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $name = 'Test Medicine Fixed';
    $description = 'Test description';
    $price = 30.00;
    $stockQuantity = 150;
    $pharmacyId = $pharmacy ? $pharmacy['id'] : $conn->lastInsertId();
    
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
    
    // Check medicines for the pharmacy
    $medicines = $conn->query("SELECT * FROM medicines WHERE pharmacy_id = $pharmacyId")->fetchAll(PDO::FETCH_ASSOC);
    echo "Medicines for pharmacy_id = $pharmacyId:\n";
    foreach ($medicines as $medicine) {
        echo "- ID: {$medicine['id']}, Name: {$medicine['name']}, Price: {$medicine['price']}, Stock: {$medicine['stock_quantity']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 