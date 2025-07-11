<?php
// WaafiPay payment handler
header('Content-Type: application/json');

// WaafiPay credentials
$merchantUid = 'M0913963';
$apiUserId = '1008217';
$apiKey = 'API-u5EgPyUot2qISzI1GOYJ9vUEpT8m';
$waafiEndpoint = 'https://api.waafipay.net/asm'; // Replace with actual endpoint if different

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['phone']) || empty($data['amount']) || empty($data['cart'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}
$phone = $data['phone'];
$amount = $data['amount'];
$cart = $data['cart'];

// Generate order reference and invoice
$orderRef = 'ORD' . time() . rand(100,999);
$invoiceId = $orderRef;
$description = 'Pharmacy order via WaafiPay';

$payload = [
    'schemaVersion' => '1.0',
    'requestId' => uniqid('waafi_', true),
    'timestamp' => date('Y-m-d H:i:s'),
    'channelName' => 'WEB',
    'serviceName' => 'API_PURCHASE',
    'serviceParams' => [
        'merchantUid' => $merchantUid,
        'apiUserId' => $apiUserId,
        'apiKey' => $apiKey,
        'paymentMethod' => 'MWALLET_ACCOUNT',
        'payerInfo' => [
            'accountNo' => $phone
        ],
        'transactionInfo' => [
            'referenceId' => $orderRef,
            'invoiceId' => $invoiceId,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'USD',
            'description' => $description
        ]
    ]
];

$ch = curl_init($waafiEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpcode == 200 && $response) {
    $respData = json_decode($response, true);
    if (isset($respData['responseCode']) && $respData['responseCode'] == '2001' && isset($respData['params']['state']) && strtolower($respData['params']['state']) == 'approved') {
        // TODO: Save order in DB if needed
        echo json_encode(['success' => true, 'transactionId' => $respData['params']['transactionId'], 'orderRef' => $orderRef]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => $respData['responseMsg'] ?? 'WaafiPay error']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => $error ?: 'HTTP error ' . $httpcode]);
    exit();
} 