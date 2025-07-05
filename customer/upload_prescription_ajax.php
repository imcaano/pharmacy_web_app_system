<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if (!isset($_FILES['prescription_file']) || $_FILES['prescription_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error.']);
    exit();
}

$fileTmpPath = $_FILES['prescription_file']['tmp_name'];
$fileName = $_FILES['prescription_file']['name'];
$fileSize = $_FILES['prescription_file']['size'];
$fileType = $_FILES['prescription_file']['type'];
$fileNameCmps = explode('.', $fileName);
$fileExtension = strtolower(end($fileNameCmps));
$allowedfileExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
if (!in_array($fileExtension, $allowedfileExtensions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and PDF allowed.']);
    exit();
}
$newFileName = uniqid('presc_', true) . '.' . $fileExtension;
$uploadFileDir = '../uploads/prescriptions/';
if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0777, true);
}
$dest_path = $uploadFileDir . $newFileName;
if (!move_uploaded_file($fileTmpPath, $dest_path)) {
    echo json_encode(['success' => false, 'error' => 'Error moving the uploaded file.']);
    exit();
}
// Save to DB
$stmt = $conn->prepare("INSERT INTO prescriptions (customer_id, prescription_file, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], $newFileName]);
$prescription_id = $conn->lastInsertId();
echo json_encode(['success' => true, 'prescription_id' => $prescription_id]); 