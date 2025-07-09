<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get export parameters
$format = $_POST['format'] ?? 'csv';
$date_from = $_POST['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? '';
$include_items = isset($_POST['include_items']);

// Build query with filters
$filter_sql = [];
$params = [];

if (!empty($date_from)) {
    $filter_sql[] = 'o.created_at >= ?';
    $params[] = $date_from . ' 00:00:00';
}
if (!empty($date_to)) {
    $filter_sql[] = 'o.created_at <= ?';
    $params[] = $date_to . ' 23:59:59';
}

$where = $filter_sql ? 'WHERE ' . implode(' AND ', $filter_sql) : '';

$orders = $conn->prepare("
    SELECT o.*, u.email as customer_email, p.pharmacy_name
    FROM orders o
    LEFT JOIN users u ON o.customer_id = u.id
    LEFT JOIN pharmacies p ON o.pharmacy_id = p.id
    $where
    ORDER BY o.created_at DESC
");
$orders->execute($params);
$orders = $orders->fetchAll();

// Get order items if needed
$order_items = [];
if ($include_items) {
    $order_ids = array_column($orders, 'id');
    if (!empty($order_ids)) {
        $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
        $items_query = $conn->prepare("
            SELECT oi.*, m.name as medicine_name 
            FROM order_items oi 
            JOIN medicines m ON oi.medicine_id = m.id 
            WHERE oi.order_id IN ($placeholders)
        ");
        $items_query->execute($order_ids);
        $items = $items_query->fetchAll();
        
        foreach ($items as $item) {
            $order_items[$item['order_id']][] = $item;
        }
    }
}

if ($format === 'csv') {
    // Export as CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    $headers = ['Order ID', 'Customer Email', 'Pharmacy', 'Status', 'Total Amount', 'Order Date'];
    if ($include_items) {
        $headers[] = 'Items';
    }
    fputcsv($output, $headers);
    
    // Data
    foreach ($orders as $order) {
        $row = [
            $order['id'],
            $order['customer_email'],
            $order['pharmacy_name'],
            ucfirst($order['status']),
            '$' . number_format($order['total_amount'], 2),
            date('Y-m-d H:i', strtotime($order['created_at']))
        ];
        
        if ($include_items) {
            $items_text = '';
            if (isset($order_items[$order['id']])) {
                foreach ($order_items[$order['id']] as $item) {
                    $items_text .= $item['medicine_name'] . ' (Qty: ' . $item['quantity'] . ', Price: $' . number_format($item['price'], 2) . '); ';
                }
            }
            $row[] = $items_text;
        }
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} elseif ($format === 'excel') {
    // Export as Excel (CSV with Excel headers)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    
    // Headers
    echo '<tr>';
    echo '<th>Order ID</th>';
    echo '<th>Customer Email</th>';
    echo '<th>Pharmacy</th>';
    echo '<th>Status</th>';
    echo '<th>Total Amount</th>';
    echo '<th>Order Date</th>';
    if ($include_items) {
        echo '<th>Items</th>';
    }
    echo '</tr>';
    
    // Data
    foreach ($orders as $order) {
        echo '<tr>';
        echo '<td>' . $order['id'] . '</td>';
        echo '<td>' . htmlspecialchars($order['customer_email']) . '</td>';
        echo '<td>' . htmlspecialchars($order['pharmacy_name']) . '</td>';
        echo '<td>' . ucfirst($order['status']) . '</td>';
        echo '<td>$' . number_format($order['total_amount'], 2) . '</td>';
        echo '<td>' . date('Y-m-d H:i', strtotime($order['created_at'])) . '</td>';
        
        if ($include_items) {
            $items_text = '';
            if (isset($order_items[$order['id']])) {
                foreach ($order_items[$order['id']] as $item) {
                    $items_text .= $item['medicine_name'] . ' (Qty: ' . $item['quantity'] . ', Price: $' . number_format($item['price'], 2) . '); ';
                }
            }
            echo '<td>' . htmlspecialchars($items_text) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    
} elseif ($format === 'pdf') {
    // Export as PDF
    require_once '../vendor/autoload.php'; // Make sure TCPDF is installed
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('PharmaWeb Admin');
    $pdf->SetAuthor('PharmaWeb');
    $pdf->SetTitle('Orders Report');
    
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Orders Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('helvetica', '', 10);
    
    // Table headers
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(25, 7, 'Order ID', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Customer', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Pharmacy', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Status', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Amount', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Date', 1, 1, 'C', true);
    
    // Table data
    foreach ($orders as $order) {
        $pdf->Cell(25, 6, $order['id'], 1);
        $pdf->Cell(50, 6, substr($order['customer_email'], 0, 20), 1);
        $pdf->Cell(40, 6, substr($order['pharmacy_name'], 0, 15), 1);
        $pdf->Cell(25, 6, ucfirst($order['status']), 1);
        $pdf->Cell(30, 6, '$' . number_format($order['total_amount'], 2), 1);
        $pdf->Cell(30, 6, date('Y-m-d', strtotime($order['created_at'])), 1, 1);
    }
    
    $pdf->Output('orders_' . date('Y-m-d') . '.pdf', 'D');
}
?> 