<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// TODO: Add token authentication and role check

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->query('SELECT * FROM orders');
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Convert IDs to integers and ensure proper data types
    foreach ($orders as &$order) {
        $order['id'] = (int)$order['id'];
        $order['customer_id'] = (int)$order['customer_id'];
        $order['pharmacy_id'] = (int)$order['pharmacy_id'];
        $order['total_amount'] = (float)$order['total_amount'];
        $order['status'] = $order['status'] ?? 'pending';
        $order['created_at'] = $order['created_at'] ?? date('Y-m-d H:i:s');
    }
    echo json_encode(['success' => true, 'orders' => $orders]);
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $customer_id = $data['customer_id'] ?? 0;
    $pharmacy_id = $data['pharmacy_id'] ?? 0;
    $status = $data['status'] ?? 'pending';
    $total_amount = $data['total_amount'] ?? 0;
    if (!$customer_id || !$pharmacy_id || !$total_amount) {
        echo json_encode(['error' => 'All fields required']);
        exit();
    }
    $stmt = $conn->prepare('INSERT INTO orders (customer_id, pharmacy_id, status, total_amount) VALUES (?, ?, ?, ?)');
    if ($stmt->execute([$customer_id, $pharmacy_id, $status, $total_amount])) {
        $id = $conn->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        echo json_encode(['error' => 'Order creation failed']);
    }
    exit();
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? null;
    if (!$id || !$status) {
        echo json_encode(['error' => 'Order id and status required']);
        exit();
    }
    $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
    if ($stmt->execute([$status, $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Order update failed']);
    }
    exit();
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if (!$id) {
        echo json_encode(['error' => 'Order id required']);
        exit();
    }
    $stmt = $conn->prepare('DELETE FROM orders WHERE id = ?');
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Order deletion failed']);
    }
    exit();
}

echo json_encode(['error' => 'Method not allowed']); 