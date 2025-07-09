<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
}

// Order Reports Filters
$filter_sql = [];
$params = [];
if (!empty($_GET['pharmacy_id'])) {
    $filter_sql[] = 'o.pharmacy_id = ?';
    $params[] = $_GET['pharmacy_id'];
}
if (!empty($_GET['customer_id'])) {
    $filter_sql[] = 'o.customer_id = ?';
    $params[] = $_GET['customer_id'];
}
if (!empty($_GET['status'])) {
    $filter_sql[] = 'o.status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $filter_sql[] = 'o.created_at >= ?';
    $params[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $filter_sql[] = 'o.created_at <= ?';
    $params[] = $_GET['date_to'] . ' 23:59:59';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - PharmaWeb Admin</title>
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-user-shield me-2"></i>PharmaWeb Admin</h4>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="pharmacies.php" class="nav-link">
                <i class="fas fa-clinic-medical me-2"></i> Pharmacies
            </a>
            <a href="users.php" class="nav-link">
                <i class="fas fa-users me-2"></i> Users
            </a>
            <a href="medicines.php" class="nav-link">
                <i class="fas fa-pills me-2"></i> Medicines
            </a>
            <a href="orders.php" class="nav-link active">
                <i class="fas fa-shopping-cart me-2"></i> Orders
            </a>
            <a href="transactions.php" class="nav-link">
                <i class="fas fa-exchange-alt me-2"></i> Transactions
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar me-2"></i> Reports
            </a>
            <a href="../logout.php" class="nav-link mt-5">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Orders</h2>
            <div>
                <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Order Reports Filter Form -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Pharmacy</label>
                <select name="pharmacy_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($conn->query('SELECT id, pharmacy_name FROM pharmacies') as $ph): ?>
                        <option value="<?php echo $ph['id']; ?>" <?php if(isset($_GET['pharmacy_id']) && $_GET['pharmacy_id']==$ph['id']) echo 'selected'; ?>><?php echo $ph['pharmacy_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Customer Email</label>
                <input type="text" name="customer_id" class="form-control" placeholder="Customer ID or Email" value="<?php echo $_GET['customer_id'] ?? ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" <?php if(isset($_GET['status']) && $_GET['status']=='pending') echo 'selected'; ?>>Pending</option>
                    <option value="approved" <?php if(isset($_GET['status']) && $_GET['status']=='approved') echo 'selected'; ?>>Approved</option>
                    <option value="rejected" <?php if(isset($_GET['status']) && $_GET['status']=='rejected') echo 'selected'; ?>>Rejected</option>
                    <option value="completed" <?php if(isset($_GET['status']) && $_GET['status']=='completed') echo 'selected'; ?>>Completed</option>
                    <option value="cancelled" <?php if(isset($_GET['status']) && $_GET['status']=='cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $_GET['date_to'] ?? ''; ?>">
            </div>
            <div class="col-md-12 text-end align-self-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <!-- Orders Grid -->
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="order-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">Order #<?php echo $order['id']; ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-user me-2"></i><?php echo $order['customer_email']; ?>
                                </p>
                            </div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1">
                                <i class="fas fa-clinic-medical me-2"></i><?php echo $order['pharmacy_name']; ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-dollar-sign me-2"></i><?php echo number_format($order['total_amount'], 2); ?>
                            </p>
                            <?php if ($order['prescription_id']): ?>
                                <p class="mb-1">
                                    <i class="fas fa-file-medical me-2"></i>Dr. <?php echo $order['doctor_name']; ?>
                                </p>
                            <?php endif; ?>
                            <p class="mb-1">
                                <i class="fas fa-clock me-2"></i><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewOrderModal<?php echo $order['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $order['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                            <?php if ($order['prescription_id']): ?>
                                <a href="<?php echo $order['prescription_file']; ?>" class="btn btn-sm btn-outline-info" target="_blank">
                                    <i class="fas fa-file-medical me-1"></i>View Prescription
                                </a>
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
                                        <p class="mb-1">Email: <?php echo $order['customer_email']; ?></p>
                                        <p class="mb-1">Order Date: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Pharmacy Information</h6>
                                        <p class="mb-1">Name: <?php echo $order['pharmacy_name']; ?></p>
                                        <p class="mb-1">Status: <?php echo ucfirst($order['status']); ?></p>
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
                                            $items = $conn->query("
                                                SELECT oi.*, m.name as medicine_name 
                                                FROM order_items oi 
                                                JOIN medicines m ON oi.medicine_id = m.id 
                                                WHERE oi.order_id = " . $order['id']
                                            )->fetchAll();
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
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Status Modal -->
                <div class="modal fade" id="updateStatusModal<?php echo $order['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Order Status</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="new_status" class="form-select" required>
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $order['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo $order['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="date" class="form-control" placeholder="From">
                                </div>
                                <div class="col">
                                    <input type="date" class="form-control" placeholder="To">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pharmacy</label>
                            <select class="form-select">
                                <option value="">All Pharmacies</option>
                                <?php
                                $pharmacies = $conn->query("SELECT * FROM pharmacies WHERE status = 'active'")->fetchAll();
                                foreach ($pharmacies as $pharmacy) {
                                    echo "<option value='{$pharmacy['id']}'>{$pharmacy['pharmacy_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="number" class="form-control" placeholder="Min">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" placeholder="Max">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="filterForm" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm" action="export_orders.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select name="format" class="form-select" required>
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="date" name="date_from" class="form-control" placeholder="From">
                                </div>
                                <div class="col">
                                    <input type="date" name="date_to" class="form-control" placeholder="To">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="include_items" class="form-check-input" id="includeItems" value="1">
                                <label class="form-check-label" for="includeItems">Include Order Items</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="exportForm" class="btn btn-primary">Export</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 