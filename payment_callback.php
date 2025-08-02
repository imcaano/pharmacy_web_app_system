<?php
header('Content-Type: application/json');
require_once 'config/database.php';

// Waafi Pay callback handler
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid callback data');
    }
    
    $transactionId = $input['transaction_id'] ?? '';
    $paymentId = $input['payment_id'] ?? '';
    $status = $input['status'] ?? '';
    $amount = $input['amount'] ?? '';
    $orderId = $input['order_id'] ?? '';
    
    if (empty($transactionId) || empty($status)) {
        throw new Exception('Missing required callback parameters');
    }
    
    $pdo = getConnection();
    
    // Update payment status
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = ?, updated_at = NOW() 
        WHERE transaction_id = ?
    ");
    $stmt->execute([$status, $transactionId]);
    
    // Update order status based on payment status
    if ($status === 'completed' || $status === 'success') {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'paid', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        // Send SMS notification to customer
        // TODO: Implement SMS notification
    } elseif ($status === 'failed' || $status === 'cancelled') {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'cancelled', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
    }
    
    // Log the callback
    $stmt = $pdo->prepare("
        INSERT INTO payment_logs (
            transaction_id, 
            payment_id, 
            status, 
            amount, 
            order_id, 
            callback_data, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $transactionId,
        $paymentId,
        $status,
        $amount,
        $orderId,
        json_encode($input)
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Callback processed successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 