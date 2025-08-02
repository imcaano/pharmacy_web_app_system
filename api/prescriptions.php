<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// TODO: Add token authentication and role check

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->query('SELECT * FROM prescriptions');
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Convert IDs to integers and ensure proper data types
    foreach ($prescriptions as &$prescription) {
        $prescription['id'] = (int)$prescription['id'];
        $prescription['customer_id'] = (int)$prescription['customer_id'];
        $prescription['file_url'] = $prescription['file_url'] ?? '';
        $prescription['status'] = $prescription['status'] ?? 'pending';
        $prescription['created_at'] = $prescription['created_at'] ?? date('Y-m-d H:i:s');
    }
    echo json_encode(['success' => true, 'prescriptions' => $prescriptions]);
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $customer_id = $data['customer_id'] ?? 0;
    $file_url = $data['file_url'] ?? '';
    $status = $data['status'] ?? 'pending';
    if (!$customer_id || !$file_url) {
        echo json_encode(['error' => 'All fields required']);
        exit();
    }
    $stmt = $conn->prepare('INSERT INTO prescriptions (customer_id, file_url, status) VALUES (?, ?, ?)');
    if ($stmt->execute([$customer_id, $file_url, $status])) {
        $id = $conn->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        echo json_encode(['error' => 'Prescription creation failed']);
    }
    exit();
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? null;
    if (!$id || !$status) {
        echo json_encode(['error' => 'Prescription id and status required']);
        exit();
    }
    $stmt = $conn->prepare('UPDATE prescriptions SET status = ? WHERE id = ?');
    if ($stmt->execute([$status, $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Prescription update failed']);
    }
    exit();
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if (!$id) {
        echo json_encode(['error' => 'Prescription id required']);
        exit();
    }
    $stmt = $conn->prepare('DELETE FROM prescriptions WHERE id = ?');
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Prescription deletion failed']);
    }
    exit();
}

echo json_encode(['error' => 'Method not allowed']); 