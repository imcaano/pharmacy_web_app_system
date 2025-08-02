<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$user_type = $data['user_type'] ?? '';
$metamask_address = $data['metamask_address'] ?? '';

if (!$email || !$password || !$user_type || !$metamask_address) {
    echo json_encode(['error' => 'All fields required']);
    exit();
}

// Check if email already exists
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'Email already exists']);
    exit();
}

// Generate unique MetaMask address if not provided or if it already exists
if (!$metamask_address) {
    $metamask_address = '0x' . bin2hex(random_bytes(20));
}

// Check if MetaMask address already exists and regenerate if needed
$stmt = $conn->prepare('SELECT id FROM users WHERE metamask_address = ?');
$stmt->execute([$metamask_address]);
if ($stmt->fetch()) {
    $metamask_address = '0x' . bin2hex(random_bytes(20));
}

try {
    $conn->beginTransaction();
    
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (email, password, user_type, metamask_address) VALUES (?, ?, ?, ?)');
    if ($stmt->execute([$email, $hashed, $user_type, $metamask_address])) {
        $userId = $conn->lastInsertId();
        
        // If user is pharmacy, create pharmacy record
        if ($user_type === 'pharmacy') {
            $pharmacyName = 'My Pharmacy'; // Default name, user can update later
            $address = 'Enter your address'; // Default address
            $phone = 'Enter your phone'; // Default phone
            
            $pharmacyStmt = $conn->prepare('
                INSERT INTO pharmacies (user_id, pharmacy_name, address, phone, license_number, status, wallet_address, trust_score) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $pharmacyStmt->execute([
                $userId,
                $pharmacyName,
                $address,
                $phone,
                'LIC' . $userId, // Generate license number
                'active',
                $metamask_address,
                5.0 // Default trust score
            ]);
        }
        
        $conn->commit();
        
        // Generate a fake JWT token (replace with real JWT in production)
        $token = base64_encode($userId . '|' . $email . '|' . time());
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => (int)$userId,
                'email' => $email,
                'user_type' => $user_type,
                'metamask_address' => $metamask_address,
            ],
            'token' => $token
        ]);
    } else {
        $conn->rollBack();
        echo json_encode(['error' => 'Signup failed']);
    }
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 