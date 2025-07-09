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
$category = $_POST['category'] ?? '';
$pharmacy_id = $_POST['pharmacy_id'] ?? '';
$stock_status = $_POST['stock_status'] ?? '';

// Build query with filters
$filter_sql = [];
$params = [];

if (!empty($category)) {
    $filter_sql[] = 'm.category = ?';
    $params[] = $category;
}
if (!empty($pharmacy_id)) {
    $filter_sql[] = 'm.pharmacy_id = ?';
    $params[] = $pharmacy_id;
}
if (!empty($stock_status)) {
    if ($stock_status === 'in_stock') {
        $filter_sql[] = 'm.stock_quantity > 10';
    } elseif ($stock_status === 'low_stock') {
        $filter_sql[] = 'm.stock_quantity <= 10 AND m.stock_quantity > 0';
    } elseif ($stock_status === 'out_of_stock') {
        $filter_sql[] = 'm.stock_quantity = 0';
    }
}

$where = $filter_sql ? 'WHERE ' . implode(' AND ', $filter_sql) : '';

$medicines = $conn->prepare("
    SELECT m.*, p.pharmacy_name 
    FROM medicines m 
    JOIN pharmacies p ON m.pharmacy_id = p.id 
    $where
    ORDER BY m.id DESC
");
$medicines->execute($params);
$medicines = $medicines->fetchAll();

if ($format === 'csv') {
    // Export as CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="medicines_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    $headers = ['ID', 'Name', 'Manufacturer', 'Category', 'Price', 'Stock', 'Pharmacy', 'Expiry Date', 'Prescription Required'];
    fputcsv($output, $headers);
    
    // Data
    foreach ($medicines as $medicine) {
        $row = [
            $medicine['id'],
            $medicine['name'],
            $medicine['manufacturer'],
            $medicine['category'],
            '$' . number_format($medicine['price'], 2),
            $medicine['stock_quantity'],
            $medicine['pharmacy_name'],
            $medicine['expiry_date'],
            $medicine['requires_prescription'] ? 'Yes' : 'No'
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} elseif ($format === 'excel') {
    // Export as Excel (CSV with Excel headers)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="medicines_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    
    // Headers
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Name</th>';
    echo '<th>Manufacturer</th>';
    echo '<th>Category</th>';
    echo '<th>Price</th>';
    echo '<th>Stock</th>';
    echo '<th>Pharmacy</th>';
    echo '<th>Expiry Date</th>';
    echo '<th>Prescription Required</th>';
    echo '</tr>';
    
    // Data
    foreach ($medicines as $medicine) {
        echo '<tr>';
        echo '<td>' . $medicine['id'] . '</td>';
        echo '<td>' . htmlspecialchars($medicine['name']) . '</td>';
        echo '<td>' . htmlspecialchars($medicine['manufacturer']) . '</td>';
        echo '<td>' . htmlspecialchars($medicine['category']) . '</td>';
        echo '<td>$' . number_format($medicine['price'], 2) . '</td>';
        echo '<td>' . $medicine['stock_quantity'] . '</td>';
        echo '<td>' . htmlspecialchars($medicine['pharmacy_name']) . '</td>';
        echo '<td>' . $medicine['expiry_date'] . '</td>';
        echo '<td>' . ($medicine['requires_prescription'] ? 'Yes' : 'No') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} elseif ($format === 'pdf') {
    // Export as PDF
    require_once '../vendor/autoload.php'; // Make sure TCPDF is installed
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('PharmaWeb Admin');
    $pdf->SetAuthor('PharmaWeb');
    $pdf->SetTitle('Medicines Report');
    
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Medicines Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('helvetica', '', 8);
    
    // Table headers
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Name', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Manufacturer', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Category', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Price', 1, 0, 'C', true);
    $pdf->Cell(15, 7, 'Stock', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Pharmacy', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Expiry', 1, 1, 'C', true);
    
    // Table data
    foreach ($medicines as $medicine) {
        $pdf->Cell(15, 6, $medicine['id'], 1);
        $pdf->Cell(40, 6, substr($medicine['name'], 0, 18), 1);
        $pdf->Cell(30, 6, substr($medicine['manufacturer'], 0, 12), 1);
        $pdf->Cell(25, 6, substr($medicine['category'], 0, 10), 1);
        $pdf->Cell(20, 6, '$' . number_format($medicine['price'], 2), 1);
        $pdf->Cell(15, 6, $medicine['stock_quantity'], 1);
        $pdf->Cell(30, 6, substr($medicine['pharmacy_name'], 0, 12), 1);
        $pdf->Cell(25, 6, $medicine['expiry_date'], 1, 1);
    }
    
    $pdf->Output('medicines_' . date('Y-m-d') . '.pdf', 'D');
}
?> 