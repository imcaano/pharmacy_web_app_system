<?php
// Hormuud USSD Payment Endpoint
// Author: Abdulkadir Hassan
// HORMUUD TELECOM Howlwadag Street, Bakaro Market
// Version: 1.0
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';

// Hormuud credentials
$MERCHANT_UID = 'M0913963';
$API_USER_ID = '1008217';
$API_KEY = 'API-u5EgPyUot2qISzI1GOYJ9vUEpT8m';

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

// Validate token (simple check, you may want to improve this)
if (empty($input['token']) || $input['token'] !== $API_KEY) {
    echo json_encode(['error' => 'Invalid token']);
    exit();
}

// Extract USSD parameters
$sessionid = $input['sessionid'] ?? '';
$shortcode = $input['shortcode'] ?? '';
$origin = $input['origin'] ?? '';
$ussdcontent = $input['ussdcontent'] ?? '';
$ussdstate = $input['ussdstate'] ?? '';
$requestid = $input['requestid'] ?? '';

// Example: If payment is confirmed (simulate with ussdcontent == '1' or 'Pay')
$payment_confirmed = false;
$order_id = null;
if (isset($input['ussdcontent'])) {
    // You may want to parse the ussdcontent to extract order ID or payment info
    // For demo, assume ussdcontent contains the order ID or a keyword
    if (preg_match('/order\s*#?(\d+)/i', $ussdcontent, $m)) {
        $order_id = $m[1];
    }
    if (stripos($ussdcontent, 'pay') !== false || $ussdcontent === '1') {
        $payment_confirmed = true;
    }
}

if ($payment_confirmed && $order_id) {
    // Update order status to completed
    $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND payment_method = 'hormuud'");
    $stmt->execute([$order_id]);
}

// Build USSD response
$response = [
    'sessionid' => $sessionid,
    'shortcode' => $shortcode,
    'origin' => $origin,
    'ussdcontent' => $payment_confirmed ? 'Payment received. Thank you!' : '1. Pay\n2. Cancel',
    'endreply' => $payment_confirmed ? 'true' : 'false',
];
echo json_encode($response); 