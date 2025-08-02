<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$pharmacyId = $input['pharmacy_id'] ?? null;

if (!$pharmacyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Pharmacy ID required']);
    exit;
}

try {
    // Get low stock medicines for pharmacy
    $stmt = $conn->prepare("
        SELECT * FROM medicines 
        WHERE pharmacy_id = ? 
        AND stock_quantity < 10 AND stock_quantity > 0
        ORDER BY stock_quantity ASC 
        LIMIT 5
    ");
    $stmt->execute([$pharmacyId]);
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'medicines' => $medicines
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?> 