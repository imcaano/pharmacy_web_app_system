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

if (!$email || !$password) {
    echo json_encode(['error' => 'Email and password required']);
    exit();
}

$stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // Generate a fake JWT token (replace with real JWT in production)
    $token = base64_encode($user['id'] . '|' . $user['email'] . '|' . time());
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'metamask_address' => $user['metamask_address'],
        ],
        'token' => $token
    ]);
} else {
    echo json_encode(['error' => 'Invalid credentials']);
} 