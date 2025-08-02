<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get all medicines
        $medicines = $conn->query("SELECT * FROM medicines ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get medicines count by pharmacy
        $pharmacyCounts = $conn->query("SELECT pharmacy_id, COUNT(*) as count FROM medicines GROUP BY pharmacy_id")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get table structure
        $tableStructure = $conn->query("DESCRIBE medicines")->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'all_medicines' => $medicines,
            'pharmacy_counts' => $pharmacyCounts,
            'table_structure' => $tableStructure,
            'total_medicines' => count($medicines)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Test adding a medicine
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? 'Test Medicine';
    $description = $input['description'] ?? 'Test Description';
    $price = $input['price'] ?? 10.00;
    $stockQuantity = $input['stock_quantity'] ?? 100;
    $pharmacyId = $input['pharmacy_id'] ?? 1;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO medicines (name, description, price, stock_quantity, pharmacy_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([$name, $description, $price, $stockQuantity, $pharmacyId]);
        
        if ($result) {
            $medicineId = $conn->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'Test medicine added successfully',
                'medicine_id' => $medicineId,
                'pharmacy_id' => $pharmacyId
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
}
?> 