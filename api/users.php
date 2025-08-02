<?php
header('Content-Type: application/json');
error_reporting(0); // Disable error reporting to prevent HTML output
require_once '../config/database.php';

// TODO: Add token authentication and admin check

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // List all users
        $stmt = $conn->query('SELECT id, email, user_type, metamask_address FROM users');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Convert IDs to integers
        foreach ($users as &$user) {
            $user['id'] = (int)$user['id'];
        }
        echo json_encode(['success' => true, 'users' => $users]);
        exit();
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $user_type = $data['user_type'] ?? '';
        $metamask_address = $data['metamask_address'] ?? '';
        if (!$email || !$password || !$user_type || !$metamask_address) {
            echo json_encode(['success' => false, 'error' => 'All fields required']);
            exit();
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (email, password, user_type, metamask_address) VALUES (?, ?, ?, ?)');
        if ($stmt->execute([$email, $hashed, $user_type, $metamask_address])) {
            $id = $conn->lastInsertId();
            echo json_encode(['success' => true, 'id' => (int)$id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User creation failed']);
        }
        exit();
    }

    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $email = $data['email'] ?? null;
        $user_type = $data['user_type'] ?? null;
        $metamask_address = $data['metamask_address'] ?? null;
        if (!$id || !$email || !$user_type || !$metamask_address) {
            echo json_encode(['success' => false, 'error' => 'All fields required']);
            exit();
        }
        $stmt = $conn->prepare('UPDATE users SET email = ?, user_type = ?, metamask_address = ? WHERE id = ?');
        if ($stmt->execute([$email, $user_type, $metamask_address, $id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User update failed']);
        }
        exit();
    }

    if ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'User id required']);
            exit();
        }
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User deletion failed']);
        }
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?> 