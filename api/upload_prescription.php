<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Get authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

$token = $matches[1];

// Decode token to get user info
$tokenData = base64_decode($token);
$tokenParts = explode('|', $tokenData);

if (count($tokenParts) < 2) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token format']);
    exit;
}

$userId = $tokenParts[0];
$userEmail = $tokenParts[1];

// Verify user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND email = ?");
$stmt->execute([$userId, $userEmail]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileSize = $file['size'];
$fileTmpName = $file['tmp_name'];
$fileType = $file['type'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed']);
    exit;
}

// Validate file size (max 10MB)
$maxSize = 10 * 1024 * 1024; // 10MB
if ($fileSize > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 10MB']);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = '../uploads/prescriptions/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$timestamp = time();
$randomString = bin2hex(random_bytes(8));
$newFileName = "presc_{$timestamp}_{$randomString}.{$fileExtension}";
$filePath = $uploadDir . $newFileName;

// Move uploaded file
if (move_uploaded_file($fileTmpName, $filePath)) {
    // Return the file URL
    $fileUrl = "uploads/prescriptions/{$newFileName}";
    
    echo json_encode([
        'success' => true,
        'fileUrl' => $fileUrl,
        'message' => 'File uploaded successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}
?> 