<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Fetch cart items for customer
        $customerId = $_GET['customer_id'] ?? null;
        
        if (!$customerId) {
            echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT c.*, m.name, m.price, m.stock_quantity, m.requires_prescription, p.pharmacy_name
                FROM cart c 
                JOIN medicines m ON c.medicine_id = m.id 
                JOIN pharmacies p ON m.pharmacy_id = p.id
                WHERE c.customer_id = ?
                ORDER BY c.id DESC
            ");
            $stmt->execute([$customerId]);
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total
            $total = 0;
            foreach ($cart_items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'cart_items' => $cart_items,
                'total' => $total,
                'debug' => [
                    'customer_id' => $customerId,
                    'count' => count($cart_items)
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Add item to cart
        $input = json_decode(file_get_contents('php://input'), true);
        
        $customerId = $input['customer_id'] ?? null;
        $medicineId = $input['medicine_id'] ?? null;
        $quantity = $input['quantity'] ?? 1;

        if (!$customerId || !$medicineId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Customer ID and Medicine ID are required']);
            exit;
        }

        try {
            // Check if item already exists in cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE customer_id = ? AND medicine_id = ?");
            $stmt->execute([$customerId, $medicineId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update quantity
                $newQuantity = $existing['quantity'] + $quantity;
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $existing['id']]);
                $message = 'Cart updated successfully';
            } else {
                // Add new item
                $stmt = $conn->prepare("INSERT INTO cart (customer_id, medicine_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$customerId, $medicineId, $quantity]);
                $message = 'Item added to cart successfully';
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'debug' => [
                    'customer_id' => $customerId,
                    'medicine_id' => $medicineId,
                    'quantity' => $quantity
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Update cart item quantity
        $input = json_decode(file_get_contents('php://input'), true);
        
        $cartId = $input['cart_id'] ?? null;
        $quantity = $input['quantity'] ?? null;
        $customerId = $input['customer_id'] ?? null;

        if (!$cartId || !$customerId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Cart ID and Customer ID are required']);
            exit;
        }

        try {
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
                $stmt->execute([$cartId, $customerId]);
                $message = 'Item removed from cart';
            } else {
                // Update quantity
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
                $stmt->execute([$quantity, $cartId, $customerId]);
                $message = 'Cart updated successfully';
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'debug' => [
                    'cart_id' => $cartId,
                    'customer_id' => $customerId,
                    'quantity' => $quantity
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Remove item from cart
        $input = json_decode(file_get_contents('php://input'), true);
        $cartId = $input['cart_id'] ?? null;
        $customerId = $input['customer_id'] ?? null;

        if (!$cartId || !$customerId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Cart ID and Customer ID are required']);
            exit;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
            $result = $stmt->execute([$cartId, $customerId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Item removed from cart successfully',
                    'debug' => [
                        'cart_id' => $cartId,
                        'customer_id' => $customerId
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to remove item from cart'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
?> 