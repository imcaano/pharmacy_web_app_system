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
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND pharmacy_id = ?");
    $stmt->execute([$new_status, $order_id, $pharmacy['id']]);
}

// Get all orders for this pharmacy
$orders = $conn->query("
    SELECT o.*, 
           u.email as customer_email,
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color: #0b6e6e;">Manage Orders</h2>
            <div>
                <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
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
                                        <?php if (!empty($order['prescription_id'])): ?>
                                            <?php
                                            $presc = $conn->prepare("SELECT prescription_file FROM prescriptions WHERE id = ?");
                                            $presc->execute([$order['prescription_id']]);
                                            $prescFile = $presc->fetchColumn();
                                            if ($prescFile): ?>
                                                <div class="mb-3">
                                                    <strong>Prescription File:</strong>
                                                    <a href="../uploads/prescriptions/<?php echo htmlspecialchars($prescFile); ?>" target="_blank" class="btn btn-outline-info btn-sm ms-2">
                                                        <i class="fas fa-file-download"></i> View Prescription
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-primary mt-3" onclick="signOrderWithWallet(<?php echo $order['id']; ?>)">
                                            <i class="fab fa-ethereum"></i> Sign with Wallet
                                        </button>
                                        <div id="signResult<?php echo $order['id']; ?>" class="mt-2"></div>
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
                    <form id="exportForm">
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select class="form-select">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
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
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="includeItems">
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