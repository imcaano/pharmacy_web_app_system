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
$waafiPayUrl = 'https://api.waafipay.com/v1/payments';
$merchantId = 'your_merchant_id'; // Replace with your actual merchant ID
$apiKey = 'your_api_key'; // Replace with your actual API key

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $amount = $input['amount'] ?? '';
    $currency = $input['currency'] ?? 'USD';
    $orderId = $input['order_id'] ?? '';
    $customerPhone = $input['customer_phone'] ?? '';
    $customerName = $input['customer_name'] ?? '';
    $description = $input['description'] ?? '';
    
    if (empty($amount) || empty($orderId) || empty($customerPhone)) {
        throw new Exception('Missing required parameters');
    }
    
    // Prepare payment data for Waafi Pay
    $paymentData = [
        'merchant_id' => $merchantId,
        'amount' => $amount,
        'currency' => $currency,
        'order_id' => $orderId,
        'customer_phone' => $customerPhone,
        'customer_name' => $customerName,
        'description' => $description,
        'callback_url' => 'http://192.168.18.12/pharmacy_web_app_system/payment_callback.php',
        'return_url' => 'http://192.168.18.12/pharmacy_web_app_system/payment_success.php',
    ];
    
    // Make request to Waafi Pay API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $waafiPayUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
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
        throw new Exception('Waafi Pay payment creation failed: ' . ($waafiResponse['message'] ?? 'Unknown error'));
    }
    
    // Store payment information in database
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            order_id, 
            payment_id, 
            transaction_id, 
            amount, 
            currency, 
            status, 
            customer_phone, 
            customer_name, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $orderId,
        $waafiResponse['payment_id'] ?? '',
        $waafiResponse['transaction_id'] ?? '',
        $amount,
        $currency,
        'pending',
        $customerPhone,
        $customerName
    ]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'payment_id' => $waafiResponse['payment_id'] ?? '',
        'transaction_id' => $waafiResponse['transaction_id'] ?? '',
        'payment_url' => $waafiResponse['payment_url'] ?? '',
        'message' => 'Payment order created successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 