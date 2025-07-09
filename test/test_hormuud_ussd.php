<?php
// Test script for Hormuud USSD payment endpoint
// Usage: php test_hormuud_ussd.php or run in browser

$endpoint = '../hormuud_ussd_payment.php'; // Adjust path if needed

// Example USSD request payload
$payload = [
    'token' => 'API-u5EgPyUot2qISzI1GOYJ9vUEpT8m', // Use your API key
    'requestid' => uniqid('req_'),
    'sessionid' => rand(10000,99999),
    'origin' => '615510162',
    'ussdcontent' => 'Pay order #1', // Simulate payment for order #1
    'ussdstate' => 'begin',
    'shortcode' => '000',
    'issuedon' => date('Y-m-d H:i:s'),
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($payload),
    ],
];
$context  = stream_context_create($options);
$response = file_get_contents($endpoint, false, $context);

header('Content-Type: application/json');
echo $response; 