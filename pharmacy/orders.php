<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is pharmacy
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'pharmacy') {
    header('Location: ../login.php');
    exit();
}

// Get pharmacy details
$pharmacy = $conn->query("
    SELECT * FROM pharmacies 
    WHERE user_id = " . $_SESSION['user_id']
)->fetch();

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $conn->beginTransaction();
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND pharmacy_id = ?");
        $stmt->execute([$new_status, $order_id, $pharmacy['id']]);
        
        // If order is approved, update medicine stock
        if ($new_status === 'approved') {
            // Get order items
            $stmt = $conn->prepare("
                SELECT oi.medicine_id, oi.quantity, m.stock_quantity 
                FROM order_items oi 
                JOIN medicines m ON oi.medicine_id = m.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
            
            // Update stock for each medicine
            foreach ($order_items as $item) {
                $new_stock = $item['stock_quantity'] - $item['quantity'];
                if ($new_stock >= 0) {
                    $stmt = $conn->prepare("UPDATE medicines SET stock_quantity = ? WHERE id = ?");
                    $stmt->execute([$new_stock, $item['medicine_id']]);
                } else {
                    throw new Exception("Insufficient stock for medicine ID: " . $item['medicine_id']);
                }
            }
        }
        
        $conn->commit();
        $success_message = "Order status updated successfully!";
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

// --- Export Orders Logic ---
if (isset($_POST['export_orders'])) {
    $format = $_POST['export_format'] ?? 'csv';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $include_items = isset($_POST['include_items']);
    $where = "WHERE o.pharmacy_id = {$pharmacy['id']}";
    $params = [];
    if ($date_from) {
        $where .= " AND o.created_at >= ?";
        $params[] = $date_from . ' 00:00:00';
    }
    if ($date_to) {
        $where .= " AND o.created_at <= ?";
        $params[] = $date_to . ' 23:59:59';
    }
    $sql = "SELECT o.*, u.metamask_address as customer_metamask, u.email as customer_email FROM orders o JOIN users u ON o.customer_id = u.id $where ORDER BY o.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $orders_export = $stmt->fetchAll();
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_export.csv"');
        $out = fopen('php://output', 'w');
        $header = ['Order ID', 'Customer Metamask', 'Order Date', 'Status', 'Total Amount'];
        if ($include_items) $header[] = 'Order Items';
        fputcsv($out, $header);
        foreach ($orders_export as $order) {
            $row = [
                $order['id'],
                $order['customer_metamask'] ?: $order['customer_email'],
                $order['created_at'],
                $order['status'],
                $order['total_amount']
            ];
            if ($include_items) {
                $items = $conn->query("SELECT m.name, oi.quantity FROM order_items oi JOIN medicines m ON oi.medicine_id = m.id WHERE oi.order_id = " . $order['id'])->fetchAll();
                $item_strs = [];
                foreach ($items as $item) {
                    $item_strs[] = $item['name'] . ' (x' . $item['quantity'] . ')';
                }
                $row[] = implode('; ', $item_strs);
            }
            fputcsv($out, $row);
        }
        fclose($out);
        exit();
    } elseif ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="orders_export.xls"');
        echo "<table border='1'><tr><th>Order ID</th><th>Customer Metamask</th><th>Order Date</th><th>Status</th><th>Total Amount</th>";
        if ($include_items) echo "<th>Order Items</th>";
        echo "</tr>";
        foreach ($orders_export as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>" . ($order['customer_metamask'] ?: $order['customer_email']) . "</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "<td>{$order['status']}</td>";
            echo "<td>{$order['total_amount']}</td>";
            if ($include_items) {
                $items = $conn->query("SELECT m.name, oi.quantity FROM order_items oi JOIN medicines m ON oi.medicine_id = m.id WHERE oi.order_id = " . $order['id'])->fetchAll();
                $item_strs = [];
                foreach ($items as $item) {
                    $item_strs[] = $item['name'] . ' (x' . $item['quantity'] . ')';
                }
                echo "<td>" . implode('; ', $item_strs) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        exit();
    } elseif ($format === 'pdf') {
        require_once '../vendor/autoload.php';
        $mpdf = new \Mpdf\Mpdf();
        $html = '<h2>Orders Export</h2><table border="1" cellpadding="6"><tr><th>Order ID</th><th>Customer Metamask</th><th>Order Date</th><th>Status</th><th>Total Amount</th>';
        if ($include_items) $html .= '<th>Order Items</th>';
        $html .= '</tr>';
        foreach ($orders_export as $order) {
            $html .= '<tr>';
            $html .= '<td>' . $order['id'] . '</td>';
            $html .= '<td>' . ($order['customer_metamask'] ?: $order['customer_email']) . '</td>';
            $html .= '<td>' . $order['created_at'] . '</td>';
            $html .= '<td>' . $order['status'] . '</td>';
            $html .= '<td>' . $order['total_amount'] . '</td>';
            if ($include_items) {
                $items = $conn->query("SELECT m.name, oi.quantity FROM order_items oi JOIN medicines m ON oi.medicine_id = m.id WHERE oi.order_id = " . $order['id'])->fetchAll();
                $item_strs = [];
                foreach ($items as $item) {
                    $item_strs[] = $item['name'] . ' (x' . $item['quantity'] . ')';
                }
                $html .= '<td>' . implode('; ', $item_strs) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $mpdf->WriteHTML($html);
        $mpdf->Output('orders_export.pdf', 'D');
        exit();
    }
}

// Get all orders for this pharmacy
$orders = $conn->query("
    SELECT o.*, 
           u.email as customer_email,
           u.metamask_address as customer_metamask,
           pr.doctor_name,
           pr.prescription_file
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    LEFT JOIN prescriptions pr ON o.prescription_id = pr.id 
    WHERE o.pharmacy_id = " . $pharmacy['id'] . "
    ORDER BY o.created_at DESC
")->fetchAll();

// Fetch smart contract payments for this pharmacy
$smart_payments = $conn->prepare("SELECT scp.*, o.id as order_id, o.total_amount, u.email as customer_email FROM smart_contract_payments scp JOIN orders o ON scp.order_id = o.id JOIN users u ON o.customer_id = u.id WHERE scp.pharmacy_id = ? ORDER BY scp.created_at DESC");
$smart_payments->execute([$pharmacy['id']]);
$smart_payments = $smart_payments->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - PharmaWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0b6e6e;
            --primary-light: #0b6e6e;
            --primary-dark: #0b6e6e;
            --accent: #0b6e6e;
            --text-light: #0b6e6e;
        }

        /* Update primary button styles */
        .btn-primary {
            background: #0b6e6e;
            border-color: #0b6e6e;
            color: #fff;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #0b6e6e;
            border-color: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }

        .btn-outline-primary {
            border-color: #0b6e6e;
            color: #0b6e6e;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: #0b6e6e;
            color: #fff;
            border-color: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }

        h2, h3, h4, h5, h6 {
            color: #0b6e6e;
        }

        .order-card i {
            color: #0b6e6e;
        }

        .order-number {
            color: #0b6e6e;
            font-weight: 600;
        }

        .order-total {
            color: #0b6e6e;
            font-weight: 600;
        }

        .order-status {
            color: #0b6e6e;
        }

        .order-date {
            color: #0b6e6e;
        }

        .process-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .process-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }
        .expert-order-card {
            background: linear-gradient(135deg, #e0f7fa 0%, #f5fafd 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(11, 110, 110, 0.10), 0 1.5px 4px rgba(0,0,0,0.04);
            border: none;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
            margin-bottom: 2rem;
        }
        .expert-order-card:hover {
            box-shadow: 0 8px 32px rgba(11, 110, 110, 0.18), 0 3px 8px rgba(0,0,0,0.08);
            transform: translateY(-2px) scale(1.01);
            background: linear-gradient(135deg, #b2ebf2 0%, #e0f7fa 100%);
        }
        .expert-order-card .badge {
            font-size: 1rem;
            padding: 0.5em 1em;
            border-radius: 12px;
        }
        .expert-order-card .fw-bold {
            word-break: break-all;
            white-space: normal;
            font-size: 1.1rem;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-clinic-medical me-2"></i>PharmaWeb</h4>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="medicines.php" class="nav-link">
                <i class="fas fa-pills me-2"></i> Medicines
            </a>
            <a href="orders.php" class="nav-link active">
                <i class="fas fa-shopping-cart me-2"></i> Orders
            </a>
            <a href="prescriptions.php" class="nav-link">
                <i class="fas fa-file-medical me-2"></i> Prescriptions
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user-cog me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link" style="background-color: #0b6e6e; color: white;">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color: #0b6e6e;">Manage Orders</h2>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="ordersTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">Orders</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">Smart Contract Payments</button>
            </li>
        </ul>
        <div class="tab-content" id="ordersTabContent">
            <div class="tab-pane fade show active" id="orders" role="tabpanel">
                <!-- Orders Grid -->
                <div class="row g-4">
                    <?php if (empty($orders)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>No orders found for your pharmacy yet.
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php foreach (
    $orders as $order): ?>
    <div class="col-md-6 col-lg-4">
        <div class="expert-order-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">#<?php echo $order['id']; ?></h5>
                <span class="badge bg-success fs-6"><?php echo ucfirst($order['status']); ?></span>
            </div>
            <div class="mb-2">
                <i class="fas fa-wallet me-2"></i>
                <span class="fw-bold"><?php echo htmlspecialchars($order['customer_metamask'] ?? $order['customer_email']); ?></span>
            </div>
            <div class="mb-2">
                <i class="fas fa-calendar me-2"></i>
                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
            </div>
            <div class="mb-2">
                <i class="fas fa-dollar-sign me-2"></i>
                $<?php echo number_format($order['total_amount'], 2); ?>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewOrderModal<?php echo $order['id']; ?>">
                    <i class="fas fa-eye me-1"></i>View Details
                </button>
                <?php if (!in_array($order['status'], ['completed', 'rejected', 'cancelled'])): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="new_status" value="approved">
                        <button type="submit" name="update_status" class="btn btn-success btn-sm ms-1">Approve</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="new_status" value="pending">
                        <button type="submit" name="update_status" class="btn btn-warning btn-sm ms-1">Set Pending</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="new_status" value="rejected">
                        <button type="submit" name="update_status" class="btn btn-danger btn-sm ms-1">Reject</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal<?php echo $order['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details #<?php echo $order['id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p class="mb-1">Metamask: <?php echo $order['customer_metamask'] ?? $order['customer_email']; ?></p>
                            <p class="mb-1">Order Date: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p class="mb-1">Status: <?php echo ucfirst($order['status']); ?></p>
                            <p class="mb-1">Total Amount: $<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                    </div>
                    <h6>Order Items</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $items = $conn->query("SELECT oi.*, m.name as medicine_name FROM order_items oi JOIN medicines m ON oi.medicine_id = m.id WHERE oi.order_id = " . $order['id'])->fetchAll();
                                foreach ($items as $item):
                                ?>
                                    <tr>
                                        <td><?php echo $item['medicine_name']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                    <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php if (!in_array($order['status'], ['completed', 'rejected', 'cancelled'])): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_status" value="approved" class="btn btn-success">Approve</button>
                                <button type="submit" name="update_status" value="pending" class="btn btn-warning">Set Pending</button>
                                <button type="submit" name="update_status" value="rejected" class="btn btn-danger">Reject</button>
                            </div>
                            <input type="hidden" name="new_status" id="newStatusInput<?php echo $order['id']; ?>">
                        </form>
                        <script>
                        document.querySelectorAll('form[method="POST"] button[type="submit"]').forEach(btn => {
                            btn.addEventListener('click', function(e) {
                                this.form.querySelector('input[name="new_status"]').value = this.value;
                            });
                        });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
                </div>
            </div>
            <div class="tab-pane fade" id="payments" role="tabpanel">
                <!-- Payments Grid -->
                <div class="row g-4">
                    <?php foreach ($smart_payments as $payment): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="order-card p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1">Payment #<?php echo $payment['order_id']; ?></h5>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-user me-2"></i><?php echo $payment['customer_email']; ?>
                                        </p>
                                    </div>
                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-1">
                                        <i class="fas fa-dollar-sign me-2"></i><?php echo number_format($payment['total_amount'], 2); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-clock me-2"></i><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewPaymentModal<?php echo $payment['order_id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- View Payment Modal -->
                        <div class="modal fade" id="viewPaymentModal<?php echo $payment['order_id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Payment Details #<?php echo $payment['order_id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <h6>Customer Information</h6>
                                                <p class="mb-1">Email: <?php echo $payment['customer_email']; ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Payment Information</h6>
                                                <p class="mb-1">Status: <?php echo ucfirst($payment['status']); ?></p>
                                                <p class="mb-1">Total Amount: $<?php echo number_format($payment['total_amount'], 2); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Export Orders</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select class="form-select" name="export_format">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="date" class="form-control" name="date_from" placeholder="From">
                                </div>
                                <div class="col">
                                    <input type="date" class="form-control" name="date_to" placeholder="To">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="includeItems" name="include_items">
                                <label class="form-check-label" for="includeItems">Include Order Items</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="export_orders" class="btn btn-primary">Export</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/metamask.js"></script>
    <script>
    async function signOrderWithWallet(orderId) {
        if (!window.ethereum) {
            alert('MetaMask is not installed.');
            return;
        }
        const message = 'Approve/confirm order #' + orderId + ' on PharmaWeb';
        try {
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            const from = accounts[0];
            const signature = await window.ethereum.request({
                method: 'personal_sign',
                params: [message, from],
            });
            document.getElementById('signResult' + orderId).innerHTML = '<span class="text-success">Signed! Signature: ' + signature.substring(0, 12) + '...</span>';
        } catch (err) {
            document.getElementById('signResult' + orderId).innerHTML = '<span class="text-danger">Signature failed: ' + err.message + '</span>';
        }
    }
    </script>
</body>
</html> 