<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to get pharmacy_id from user_id
function getPharmacyIdFromUserId($conn, $userId) {
    $stmt = $conn->prepare("SELECT id FROM pharmacies WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Check if customer wants all medicines
        $allMedicines = isset($_GET['all']) && $_GET['all'] === 'true';
        
        if ($allMedicines) {
            // Fetch all medicines for customers
            try {
                $stmt = $conn->prepare("
                    SELECT m.*, p.pharmacy_name 
                    FROM medicines m 
                    LEFT JOIN pharmacies p ON m.pharmacy_id = p.id 
                    WHERE m.status = 'active' 
                    ORDER BY m.name ASC
                ");
                $stmt->execute();
                $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'medicines' => $medicines,
                    'debug' => [
                        'type' => 'all_medicines',
                        'count' => count($medicines),
                        'all_medicines_count' => $conn->query("SELECT COUNT(*) FROM medicines")->fetchColumn()
                    ]
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
        }
        
        // Fetch medicines by pharmacy (for pharmacy users)
        $pharmacyId = $_GET['pharmacy_id'] ?? 1; // This is actually user_id
        
        try {
            // Convert user_id to pharmacy_id
            $actualPharmacyId = getPharmacyIdFromUserId($conn, $pharmacyId);
            if (!$actualPharmacyId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Pharmacy not found for user_id: ' . $pharmacyId,
                    'debug' => ['user_id' => $pharmacyId]
                ]);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT * FROM medicines WHERE pharmacy_id = ? ORDER BY name ASC");
            $stmt->execute([$actualPharmacyId]);
            $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'medicines' => $medicines,
                'debug' => [
                    'user_id' => $pharmacyId,
                    'pharmacy_id' => $actualPharmacyId,
                    'count' => count($medicines),
                    'all_medicines_count' => $conn->query("SELECT COUNT(*) FROM medicines")->fetchColumn()
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Create medicine
        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = $input['name'] ?? '';
        $description = $input['description'] ?? '';
        $price = $input['price'] ?? 0;
        $stockQuantity = $input['stock_quantity'] ?? 0;
        $pharmacyId = $input['pharmacy_id'] ?? 1; // This is actually user_id

        if (!$name) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name is required']);
            exit;
        }

        try {
            // Convert user_id to pharmacy_id
            $actualPharmacyId = getPharmacyIdFromUserId($conn, $pharmacyId);
            if (!$actualPharmacyId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Pharmacy not found for user_id: ' . $pharmacyId,
                    'debug' => ['user_id' => $pharmacyId]
                ]);
                exit;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO medicines (name, description, price, stock_quantity, pharmacy_id, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $result = $stmt->execute([$name, $description, $price, $stockQuantity, $actualPharmacyId]);
            
            if ($result) {
                $medicineId = $conn->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'message' => 'Medicine created successfully',
                    'debug' => [
                        'medicine_id' => $medicineId,
                        'user_id' => $pharmacyId,
                        'pharmacy_id' => $actualPharmacyId,
                        'name' => $name,
                        'total_medicines' => $conn->query("SELECT COUNT(*) FROM medicines")->fetchColumn()
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to insert medicine',
                    'debug' => [
                        'user_id' => $pharmacyId,
                        'pharmacy_id' => $actualPharmacyId,
                        'name' => $name
                    ]
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Update medicine
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        $description = $input['description'] ?? '';
        $price = $input['price'] ?? 0;
        $stockQuantity = $input['stock_quantity'] ?? 0;
        $pharmacyId = $input['pharmacy_id'] ?? 1; // This is actually user_id

        if (!$id || !$name) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID and name are required']);
            exit;
        }

        try {
            // Convert user_id to pharmacy_id
            $actualPharmacyId = getPharmacyIdFromUserId($conn, $pharmacyId);
            if (!$actualPharmacyId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Pharmacy not found for user_id: ' . $pharmacyId,
                    'debug' => ['user_id' => $pharmacyId]
                ]);
                exit;
            }
            
            $stmt = $conn->prepare("
                UPDATE medicines 
                SET name = ?, description = ?, price = ?, stock_quantity = ?, pharmacy_id = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([$name, $description, $price, $stockQuantity, $actualPharmacyId, $id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Medicine updated successfully',
                    'debug' => [
                        'medicine_id' => $id,
                        'user_id' => $pharmacyId,
                        'pharmacy_id' => $actualPharmacyId
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update medicine'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Delete medicine
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID is required']);
            exit;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Medicine deleted successfully',
                    'debug' => ['medicine_id' => $id]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to delete medicine'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
?> 