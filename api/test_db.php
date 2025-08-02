<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Test database connection
    $conn->query("SELECT 1");
    
    // Get all medicines
    $medicines = $conn->query("SELECT * FROM medicines ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get medicines by pharmacy
    $pharmacy1Medicines = $conn->query("SELECT * FROM medicines WHERE pharmacy_id = 1")->fetchAll(PDO::FETCH_ASSOC);
    $pharmacy3Medicines = $conn->query("SELECT * FROM medicines WHERE pharmacy_id = 3")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pharmacies
    $pharmacies = $conn->query("SELECT * FROM pharmacies")->fetchAll(PDO::FETCH_ASSOC);
    
    // Test adding a medicine
    $testName = "Test Medicine " . date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO medicines (name, description, price, stock_quantity, pharmacy_id) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$testName, "Test Description", 10.00, 100, 1]);
    
    if ($result) {
        $newMedicineId = $conn->lastInsertId();
        $conn->prepare("DELETE FROM medicines WHERE id = ?")->execute([$newMedicineId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Database is working correctly',
            'data' => [
                'total_medicines' => count($medicines),
                'pharmacy_1_medicines' => count($pharmacy1Medicines),
                'pharmacy_3_medicines' => count($pharmacy3Medicines),
                'total_pharmacies' => count($pharmacies),
                'test_medicine_added' => true,
                'test_medicine_id' => $newMedicineId
            ],
            'medicines' => $medicines,
            'pharmacies' => $pharmacies
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to add test medicine'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 