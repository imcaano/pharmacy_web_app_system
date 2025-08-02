<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$pharmacyId = $input['pharmacy_id'] ?? 3; // Default to pharmacy_id = 3

try {
    // Get pharmacy statistics with debugging
    $totalMedicines = $conn->query("SELECT COUNT(*) FROM medicines WHERE pharmacy_id = $pharmacyId")->fetchColumn();
    $totalOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE pharmacy_id = $pharmacyId")->fetchColumn();
    $pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE pharmacy_id = $pharmacyId AND status = 'pending'")->fetchColumn();
    $totalRevenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE pharmacy_id = $pharmacyId AND status = 'completed'")->fetchColumn();
    
    // Get all medicines count for debugging
    $allMedicinesCount = $conn->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
    
    $stats = [
        'total_medicines' => $totalMedicines,
        'total_orders' => $totalOrders,
        'pending_orders' => $pendingOrders,
        'total_revenue' => $totalRevenue
    ];

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'debug' => [
            'pharmacy_id' => $pharmacyId,
            'total_medicines' => $totalMedicines,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'total_revenue' => $totalRevenue,
            'all_medicines_count' => $allMedicinesCount
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?> 