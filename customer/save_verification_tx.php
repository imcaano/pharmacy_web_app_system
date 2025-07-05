<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['prescription_id']) || empty($data['tx_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

$prescription_id = $data['prescription_id'];
$tx_hash = $data['tx_hash'];

$stmt = $conn->prepare("UPDATE prescriptions SET verification_tx_hash = ? WHERE id = ? AND customer_id = ?");
$ok = $stmt->execute([$tx_hash, $prescription_id, $_SESSION['user_id']]);
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB update failed']);
} 