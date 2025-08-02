<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/database.php';

// Waafi Pay Configuration
$waafiPayUrl = 'https://api.waafipay.com/v1/payments/status';
$merchantId = 'your_merchant_id'; // Replace with your actual merchant ID
$apiKey = 'your_api_key'; // Replace with your actual API key

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $transactionId = $input['transaction_id'] ?? '';
    
    if (empty($transactionId)) {
        throw new Exception('Missing transaction ID');
    }
    
    // Check payment status from Waafi Pay API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $waafiPayUrl . '?transaction_id=' . urlencode($transactionId));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Waafi Pay API error: HTTP ' . $httpCode);
    }
    
    $waafiResponse = json_decode($response, true);
    
    if (!$waafiResponse || !isset($waafiResponse['success']) || !$waafiResponse['success']) {
        throw new Exception('Failed to get payment status: ' . ($waafiResponse['message'] ?? 'Unknown error'));
    }
    
    // Update local database with latest status
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = ?, updated_at = NOW() 
        WHERE transaction_id = ?
    ");
    $stmt->execute([$waafiResponse['status'], $transactionId]);
    
    // Return payment status
    echo json_encode([
        'success' => true,
        'transaction_id' => $transactionId,
        'status' => $waafiResponse['status'],
        'amount' => $waafiResponse['amount'] ?? '',
        'currency' => $waafiResponse['currency'] ?? '',
        'payment_date' => $waafiResponse['payment_date'] ?? '',
        'message' => 'Payment status retrieved successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 