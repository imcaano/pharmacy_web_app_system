<?php
require_once 'config/database.php';
require_once 'config/hormuud_config.php';

header('Content-Type: application/json');

// Log USSD requests for debugging
function logUssdRequest($data) {
    if (!HORMUUD_LOG_ENABLED) return;
    
    $logFile = HORMUUD_LOG_FILE;
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = date('Y-m-d H:i:s') . " - " . json_encode($data) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log the incoming request
logUssdRequest($data);

// Validate required parameters
if (!$data || !isset($data['token']) || !isset($data['sessionid']) || !isset($data['origin'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameters',
        'sessionid' => $data['sessionid'] ?? 'unknown',
        'shortcode' => HORMUUD_USSD_SHORTCODE,
        'origin' => $data['origin'] ?? 'unknown',
        'ussdcontent' => 'Invalid request. Please try again.',
        'endreply' => 'true'
    ]);
    exit;
}

// Extract parameters
$token = $data['token'];
$requestId = $data['requestid'] ?? '';
$sessionId = $data['sessionid'];
$origin = $data['origin'];
$ussdContent = $data['ussdcontent'] ?? '';
$ussdState = $data['ussdstate'] ?? 'begin';
$shortCode = $data['shortcode'] ?? HORMUUD_USSD_SHORTCODE;
$issuedOn = $data['issuedon'] ?? '';

// Validate token
if ($token !== HORMUUD_USSD_TOKEN) {
    http_response_code(401);
    echo json_encode([
        'sessionid' => $sessionId,
        'shortcode' => $shortCode,
        'origin' => $origin,
        'ussdcontent' => HORMUUD_ERROR_MESSAGES['invalid_token'],
        'endreply' => 'true'
    ]);
    exit;
}

// Validate origin (phone number)
if (!in_array($origin, HORMUUD_ALLOWED_ORIGINS) && !preg_match('/^6[0-9]{8}$/', $origin)) {
    http_response_code(400);
    echo json_encode([
        'sessionid' => $sessionId,
        'shortcode' => $shortCode,
        'origin' => $origin,
        'ussdcontent' => HORMUUD_ERROR_MESSAGES['invalid_phone'],
        'endreply' => 'true'
    ]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle different USSD states
    switch ($ussdState) {
        case 'begin':
            $response = [
                'sessionid' => $sessionId,
                'shortcode' => $shortCode,
                'origin' => $origin,
                'ussdcontent' => HORMUUD_RESPONSE_TEMPLATES['welcome'],
                'endreply' => 'false'
            ];
            break;
            
        case 'continue':
            $userChoice = trim($ussdContent);
            
            if ($userChoice === '1') {
                $response = [
                    'sessionid' => $sessionId,
                    'shortcode' => $shortCode,
                    'origin' => $origin,
                    'ussdcontent' => HORMUUD_RESPONSE_TEMPLATES['enter_order'],
                    'endreply' => 'false'
                ];
            } elseif ($userChoice === '2') {
                $response = [
                    'sessionid' => $sessionId,
                    'shortcode' => $shortCode,
                    'origin' => $origin,
                    'ussdcontent' => HORMUUD_SUCCESS_MESSAGES['balance_check'] . "\n\nThank you for using our service.",
                    'endreply' => 'true'
                ];
            } elseif ($userChoice === '3') {
                $response = [
                    'sessionid' => $sessionId,
                    'shortcode' => $shortCode,
                    'origin' => $origin,
                    'ussdcontent' => HORMUUD_RESPONSE_TEMPLATES['goodbye'],
                    'endreply' => 'true'
                ];
            } else {
                $response = [
                    'sessionid' => $sessionId,
                    'shortcode' => $shortCode,
                    'origin' => $origin,
                    'ussdcontent' => HORMUUD_RESPONSE_TEMPLATES['invalid_choice'],
                    'endreply' => 'false'
                ];
            }
            break;
            
        case 'end':
            $response = [
                'sessionid' => $sessionId,
                'shortcode' => $shortCode,
                'origin' => $origin,
                'ussdcontent' => HORMUUD_RESPONSE_TEMPLATES['goodbye'],
                'endreply' => 'true'
            ];
            break;
            
        default:
            $response = [
                'sessionid' => $sessionId,
                'shortcode' => $shortCode,
                'origin' => $origin,
                'ussdcontent' => 'Invalid session state.',
                'endreply' => 'true'
            ];
    }
    
    // Handle order ID input
    if (isset($userChoice) && $userChoice === '1' && !empty($ussdContent) && strpos($ussdContent, 'ORDER') === 0) {
        $orderNumber = intval(str_replace('ORDER', '', trim($ussdContent)));
        
        $stmt = $pdo->prepare("SELECT id, total_amount, payment_status FROM orders WHERE id = ? AND payment_status = 'pending' AND payment_method = 'hormuud'");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $amount = $order['total_amount'];
            $response = [
                'sessionid' => $sessionId,
                'shortcode' => $shortCode,
                'origin' => $origin,
                'ussdcontent' => sprintf(HORMUUD_RESPONSE_TEMPLATES['order_details'], $orderNumber, $amount),
                'endreply' => 'false'
            ];
            
            // Store session info for payment confirmation
            $stmt = $pdo->prepare("UPDATE orders SET ussd_session_id = ? WHERE id = ?");
            $stmt->execute([$sessionId, $orderNumber]);
        } else {
            $response = [
                'sessionid' => $sessionId,
                'shortcode' => $shortCode,
                'origin' => $origin,
                'ussdcontent' => HORMUUD_ERROR_MESSAGES['invalid_order'] . "\n\nThank you for using our service.",
                'endreply' => 'true'
            ];
        }
    }
    
    // Handle payment confirmation
    if (isset($userChoice) && $userChoice === '1' && !empty($ussdContent) && strpos($ussdContent, '1') === 0) {
        $response = [
            'sessionid' => $sessionId,
            'shortcode' => $shortCode,
            'origin' => $origin,
            'ussdcontent' => HORMUUD_SUCCESS_MESSAGES['payment_success'] . "\n\nThank you for your payment.",
            'endreply' => 'true'
        ];
        
        // Update order status to completed
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed', status = 'approved' WHERE ussd_session_id = ?");
        $stmt->execute([$sessionId]);
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Hormuud USSD Database Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'sessionid' => $sessionId,
        'shortcode' => $shortCode,
        'origin' => $origin,
        'ussdcontent' => HORMUUD_ERROR_MESSAGES['system_error'],
        'endreply' => 'true'
    ]);
}
?> 