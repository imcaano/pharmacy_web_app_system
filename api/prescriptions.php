<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all prescriptions
        $stmt = $conn->prepare("
            SELECT p.*, u.email as customer_email, u.metamask_address
            FROM prescriptions p 
            JOIN users u ON p.customer_id = u.id 
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        $prescriptions = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'prescriptions' => $prescriptions]);
        break;
        
    case 'POST':
        // Accept or reject prescription
        
        $input = json_decode(file_get_contents('php://input'), true);
        $prescriptionId = $input['prescription_id'] ?? null;
        $action = $input['action'] ?? null;
        
        if (!$prescriptionId || !$action) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing prescription_id or action']);
            exit;
        }
        
        if ($action === 'accept') {
            // Accept prescription
            $stmt = $conn->prepare("
                UPDATE prescriptions 
                SET verification_status = 'verified', 
                    verification_notes = 'Prescription accepted by pharmacy', 
                    verified_at = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$prescriptionId])) {
                // Update related order status if exists
                $conn->prepare("UPDATE orders SET status = 'approved' WHERE prescription_id = ?")->execute([$prescriptionId]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Prescription accepted successfully',
                    'prescription_id' => $prescriptionId
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to accept prescription']);
            }
        } elseif ($action === 'reject') {
            // Reject prescription
            $rejectionNotes = $input['notes'] ?? 'Prescription rejected by pharmacy';
            
            $stmt = $conn->prepare("
                UPDATE prescriptions 
                SET verification_status = 'rejected', 
                    verification_notes = ?, 
                    verified_at = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$rejectionNotes, $prescriptionId])) {
                // Update related order status if exists
                $conn->prepare("UPDATE orders SET status = 'rejected' WHERE prescription_id = ?")->execute([$prescriptionId]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Prescription rejected successfully',
                    'prescription_id' => $prescriptionId
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to reject prescription']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action. Use "accept" or "reject"']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
?> 