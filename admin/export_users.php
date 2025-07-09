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
$user_type = $_POST['user_type'] ?? '';
$status = $_POST['status'] ?? '';

// Build query with filters
$filter_sql = [];
$params = [];

if (!empty($user_type)) {
    $filter_sql[] = 'u.user_type = ?';
    $params[] = $user_type;
}
if (!empty($status)) {
    if ($user_type === 'pharmacy') {
        $filter_sql[] = 'p.status = ?';
        $params[] = $status;
    }
}

$where = $filter_sql ? 'WHERE ' . implode(' AND ', $filter_sql) : '';

$users = $conn->prepare("
    SELECT u.*, p.pharmacy_name, p.status as pharmacy_status
    FROM users u
    LEFT JOIN pharmacies p ON u.id = p.user_id
    $where
    ORDER BY u.id DESC
");
$users->execute($params);
$users = $users->fetchAll();

if ($format === 'csv') {
    // Export as CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    $headers = ['ID', 'Email', 'User Type', 'Name/Pharmacy', 'Status', 'Created Date'];
    fputcsv($output, $headers);
    
    // Data
    foreach ($users as $user) {
        $name = $user['user_type'] === 'pharmacy' ? $user['pharmacy_name'] : $user['email'];
        $status = $user['user_type'] === 'pharmacy' ? $user['pharmacy_status'] : 'active';
        
        $row = [
            $user['id'],
            $user['email'],
            ucfirst($user['user_type']),
            $name,
            ucfirst($status),
            date('Y-m-d', strtotime($user['created_at']))
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} elseif ($format === 'excel') {
    // Export as Excel (CSV with Excel headers)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    
    // Headers
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Email</th>';
    echo '<th>User Type</th>';
    echo '<th>Name/Pharmacy</th>';
    echo '<th>Status</th>';
    echo '<th>Created Date</th>';
    echo '</tr>';
    
    // Data
    foreach ($users as $user) {
        $name = $user['user_type'] === 'pharmacy' ? $user['pharmacy_name'] : $user['email'];
        $status = $user['user_type'] === 'pharmacy' ? $user['pharmacy_status'] : 'active';
        
        echo '<tr>';
        echo '<td>' . $user['id'] . '</td>';
        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
        echo '<td>' . ucfirst($user['user_type']) . '</td>';
        echo '<td>' . htmlspecialchars($name) . '</td>';
        echo '<td>' . ucfirst($status) . '</td>';
        echo '<td>' . date('Y-m-d', strtotime($user['created_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} elseif ($format === 'pdf') {
    // Export as PDF
    require_once '../vendor/autoload.php'; // Make sure TCPDF is installed
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('PharmaWeb Admin');
    $pdf->SetAuthor('PharmaWeb');
    $pdf->SetTitle('Users Report');
    
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Users Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('helvetica', '', 9);
    
    // Table headers
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Email', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Type', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Name/Pharmacy', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Status', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Created', 1, 1, 'C', true);
    
    // Table data
    foreach ($users as $user) {
        $name = $user['user_type'] === 'pharmacy' ? $user['pharmacy_name'] : $user['email'];
        $status = $user['user_type'] === 'pharmacy' ? $user['pharmacy_status'] : 'active';
        
        $pdf->Cell(15, 6, $user['id'], 1);
        $pdf->Cell(50, 6, substr($user['email'], 0, 25), 1);
        $pdf->Cell(25, 6, ucfirst($user['user_type']), 1);
        $pdf->Cell(50, 6, substr($name, 0, 25), 1);
        $pdf->Cell(25, 6, ucfirst($status), 1);
        $pdf->Cell(25, 6, date('Y-m-d', strtotime($user['created_at'])), 1, 1);
    }
    
    $pdf->Output('users_' . date('Y-m-d') . '.pdf', 'D');
}
?> 