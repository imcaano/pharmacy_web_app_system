<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/database.php';

// Verify JWT token
function verifyToken($token) {
    // TODO: Implement proper JWT verification
    // For now, we'll just check if token exists
    return !empty($token);
}

try {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        throw new Exception('Missing or invalid authorization header');
    }
    
    $token = substr($authHeader, 7);
    
    if (!verifyToken($token)) {
        throw new Exception('Invalid token');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $orderId = $input['order_id'] ?? '';
    $paymentId = $input['payment_id'] ?? '';
    $transactionId = $input['transaction_id'] ?? '';
    $amount = $input['amount'] ?? '';
    $status = $input['status'] ?? 'pending';
    
    if (empty($orderId) || empty($transactionId)) {
        throw new Exception('Missing required parameters');
    }
    
    $pdo = getConnection();
    
    // Update order with payment information
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET 
            payment_id = ?, 
            transaction_id = ?, 
            payment_status = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$paymentId, $transactionId, $status, $orderId]);
    
    // Update payment record if it exists
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET 
            order_id = ?, 
            amount = ?, 
            status = ?, 
            updated_at = NOW() 
        WHERE transaction_id = ?
    ");
    $stmt->execute([$orderId, $amount, $status, $transactionId]);
    
    // If payment record doesn't exist, create it
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                order_id, 
                payment_id, 
                transaction_id, 
                amount, 
                currency, 
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, 'USD', ?, NOW())
        ");
        $stmt->execute([$orderId, $paymentId, $transactionId, $amount, $status]);
    }
    
    // Get updated order information
    $stmt = $pdo->prepare("
        SELECT o.*, p.status as payment_status, p.transaction_id 
        FROM orders o 
        LEFT JOIN payments p ON o.id = p.order_id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order payment updated successfully',
        'order' => $order
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 