<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['amount']) || empty($data['cart'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

$payment_method = isset($data['payment_method']) ? $data['payment_method'] : 'hormuud';
$txHash = isset($data['txHash']) ? $data['txHash'] : null;
$amount = $data['amount'];
$cart = $data['cart'];
$prescription_id = isset($data['prescription_id']) ? $data['prescription_id'] : null;
$phone_number = isset($data['phone']) ? $data['phone'] : null;

try {
    $conn->beginTransaction();
    // Group cart items by pharmacy_id
    $pharmacy_orders = [];
    foreach ($cart as $item) {
        // Get pharmacy_id for each medicine
        $stmt = $conn->prepare("SELECT pharmacy_id FROM medicines WHERE id = ?");
        $stmt->execute([$item['medicine_id']]);
        $pharmacy_id = $stmt->fetchColumn();
        if (!$pharmacy_id) {
            throw new Exception("Medicine not associated with a valid pharmacy.");
        }
        // Validate pharmacy_id exists in pharmacies table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM pharmacies WHERE id = ?");
        $stmt->execute([$pharmacy_id]);
        $pharmacy_exists = $stmt->fetchColumn();
        if (!$pharmacy_exists) {
            throw new Exception("Pharmacy does not exist for medicine: " . $item['name']);
        }
        if (!isset($pharmacy_orders[$pharmacy_id])) {
            $pharmacy_orders[$pharmacy_id] = [
                'items' => [],
                'total' => 0
            ];
        }
        $pharmacy_orders[$pharmacy_id]['items'][] = $item;
        $pharmacy_orders[$pharmacy_id]['total'] += $item['price'] * $item['quantity'];
    }
    $order_ids = [];
    foreach ($pharmacy_orders as $pharmacy_id => $order) {
        // Create order for each pharmacy
        $status = 'pending';
        $txHashToStore = $payment_method === 'hormuud' ? null : $txHash;
        if ($prescription_id) {
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, pharmacy_id, total_amount, status, transaction_hash, prescription_id, payment_method, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $pharmacy_id, $order['total'], $status, $txHashToStore, $prescription_id, $payment_method, $phone_number]);
        } else {
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, pharmacy_id, total_amount, status, transaction_hash, payment_method, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $pharmacy_id, $order['total'], $status, $txHashToStore, $payment_method, $phone_number]);
        }
        $order_id = $conn->lastInsertId();
        $order_ids[] = $order_id;
        // Add items to order and update stock
        foreach ($order['items'] as $item) {
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
    }
    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $conn->commit();
    echo json_encode(['success' => true, 'order_ids' => $order_ids]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 