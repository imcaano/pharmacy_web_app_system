<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['txHash']) || empty($data['amount']) || empty($data['cart'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

$txHash = $data['txHash'];
$amount = $data['amount'];
$cart = $data['cart'];
$prescription_id = isset($data['prescription_id']) ? $data['prescription_id'] : null;

try {
    $conn->beginTransaction();
    // Create order
    if ($prescription_id) {
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, status, transaction_hash, prescription_id) VALUES (?, ?, 'pending', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $amount, $txHash, $prescription_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, status, transaction_hash) VALUES (?, ?, 'pending', ?)");
        $stmt->execute([$_SESSION['user_id'], $amount, $txHash]);
    }
    $order_id = $conn->lastInsertId();
    // Add items to order and update stock
    foreach ($cart as $item) {
        // Check if enough stock
        $stock = $conn->query("SELECT stock_quantity FROM medicines WHERE id = " . intval($item['medicine_id']))->fetchColumn();
        if ($stock < $item['quantity']) {
            throw new Exception("Not enough stock for " . $item['name']);
        }
        // Add to order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $item['medicine_id'], $item['quantity'], $item['price']]);
        // Update stock
        $stmt = $conn->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['medicine_id']]);
    }
    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 