<?php
/**
 * Test script for Hormuud USSD Payment System
 * This script simulates USSD requests to test the payment flow
 */

echo "=== Hormuud USSD Payment Test ===\n\n";

// Test 1: Initial USSD session (begin)
echo "Test 1: Initial USSD Session\n";
echo "Simulating: User dials *000#\n\n";

$testData1 = [
    'token' => 'your_hormuud_token_here',
    'requestid' => 'test-001',
    'sessionid' => '12345',
    'origin' => '615123456',
    'ussdcontent' => '*000#',
    'ussdstate' => 'begin',
    'shortcode' => '000',
    'issuedon' => date('Y-m-d H:i:s')
];

echo "Request Data:\n";
echo json_encode($testData1, JSON_PRETTY_PRINT) . "\n\n";

// Send request to USSD endpoint
$response1 = sendUssdRequest($testData1);
echo "Response:\n";
echo json_encode($response1, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: User selects "Pay for Order"
echo "Test 2: User selects 'Pay for Order'\n";
echo "Simulating: User enters '1'\n\n";

$testData2 = [
    'token' => 'your_hormuud_token_here',
    'requestid' => 'test-002',
    'sessionid' => '12345',
    'origin' => '615123456',
    'ussdcontent' => '1',
    'ussdstate' => 'continue',
    'shortcode' => '000',
    'issuedon' => date('Y-m-d H:i:s')
];

echo "Request Data:\n";
echo json_encode($testData2, JSON_PRETTY_PRINT) . "\n\n";

$response2 = sendUssdRequest($testData2);
echo "Response:\n";
echo json_encode($response2, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: User enters order ID
echo "Test 3: User enters Order ID\n";
echo "Simulating: User enters 'ORDER2'\n\n";

$testData3 = [
    'token' => 'your_hormuud_token_here',
    'requestid' => 'test-003',
    'sessionid' => '12345',
    'origin' => '615123456',
    'ussdcontent' => 'ORDER2',
    'ussdstate' => 'continue',
    'shortcode' => '000',
    'issuedon' => date('Y-m-d H:i:s')
];

echo "Request Data:\n";
echo json_encode($testData3, JSON_PRETTY_PRINT) . "\n\n";

$response3 = sendUssdRequest($testData3);
echo "Response:\n";
echo json_encode($response3, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: User confirms payment
echo "Test 4: User confirms payment\n";
echo "Simulating: User enters '1' to confirm\n\n";

$testData4 = [
    'token' => 'your_hormuud_token_here',
    'requestid' => 'test-004',
    'sessionid' => '12345',
    'origin' => '615123456',
    'ussdcontent' => '1',
    'ussdstate' => 'continue',
    'shortcode' => '000',
    'issuedon' => date('Y-m-d H:i:s')
];

echo "Request Data:\n";
echo json_encode($testData4, JSON_PRETTY_PRINT) . "\n\n";

$response4 = sendUssdRequest($testData4);
echo "Response:\n";
echo json_encode($response4, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Test Complete ===\n";

/**
 * Function to send USSD request to the endpoint
 */
function sendUssdRequest($data) {
    $url = 'http://localhost/pharmacy_web_app_system/hormuud_ussd_payment.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        return ['error' => 'Failed to connect to USSD endpoint'];
    }
    
    $decodedResponse = json_decode($response, true);
    if ($decodedResponse === null) {
        return ['error' => 'Invalid JSON response', 'raw_response' => $response];
    }
    
    return $decodedResponse;
}

/**
 * Instructions for testing:
 * 
 * 1. Make sure your XAMPP server is running
 * 2. Update the token in hormuud_ussd_payment.php with your actual token
 * 3. Create a test order in the system with payment_method = 'hormuud'
 * 4. Run this script: php test_hormuud_ussd.php
 * 5. Check the database to see if the order status is updated
 * 
 * Expected flow:
 * - Test 1: Shows welcome menu
 * - Test 2: Prompts for order ID
 * - Test 3: Shows order details and confirmation
 * - Test 4: Confirms payment and ends session
 */
?> 