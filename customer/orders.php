<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch orders for this customer
$stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Fetch order items for all orders
$order_items = [];
if ($orders) {
    $order_ids = array_column($orders, 'id');
    $in  = str_repeat('?,', count($order_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT oi.*, m.name, m.price, p.pharmacy_name FROM order_items oi JOIN medicines m ON oi.medicine_id = m.id JOIN pharmacies p ON m.pharmacy_id = p.id WHERE oi.order_id IN ($in)");
    $stmt->execute($order_ids);
    foreach ($stmt->fetchAll() as $item) {
        $order_items[$item['order_id']][] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - PharmaWeb</title>
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

        .view-details-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
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
        <nav class="mt-4">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="medicines.php" class="nav-link">
                <i class="fas fa-pills me-2"></i> Browse Medicines
            </a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart me-2"></i> Cart
            </a>
            <a href="orders.php" class="nav-link active">
                <i class="fas fa-box me-2"></i> My Orders
            </a>
            <a href="prescriptions.php" class="nav-link">
                <i class="fas fa-file-medical me-2"></i> Prescriptions
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link text-danger mt-5">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Orders</h2>
            <div class="user-info">
                <span class="me-3">Welcome, <?php echo $user['email']; ?></span>
                <i class="fas fa-user-circle fa-2x"></i>
            </div>
        </div>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>Order placed successfully!
            </div>
        <?php endif; ?>
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h4>You have no orders yet</h4>
                <p class="text-muted">Browse medicines and place your first order</p>
                <a href="medicines.php" class="btn btn-primary">
                    <i class="fas fa-pills me-2"></i>Browse Medicines
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h5 class="mb-1">Order #<?php echo $order['id']; ?></h5>
                            <div class="text-muted small">Placed on <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Items:</strong>
                        <table class="table table-sm order-items-table mt-2">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Pharmacy</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($order_items[$order['id']])): ?>
                                    <?php foreach ($order_items[$order['id']] as $item): ?>
                                        <tr>
                                            <td><?php echo $item['name']; ?></td>
                                            <td><?php echo $item['pharmacy_name']; ?></td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-2">
                        <strong>Blockchain Timeline:</strong>
                        <ul class="list-inline">
                            <li class="list-inline-item <?php echo ($order['status'] !== 'pending') ? 'text-success' : ''; ?>">Requested</li>
                            <li class="list-inline-item <?php echo ($order['status'] === 'approved' || $order['status'] === 'completed') ? 'text-success' : ''; ?>">Accepted</li>
                            <li class="list-inline-item <?php echo ($order['status'] === 'completed') ? 'text-success' : ''; ?>">Shipped</li>
                            <li class="list-inline-item <?php echo ($order['status'] === 'completed') ? 'text-success' : ''; ?>">Delivered</li>
                        </ul>
                        <?php if ($order['transaction_hash']): ?>
                            <div>
                                <strong>TxHash:</strong> <span style="font-family:monospace;"> <?php echo $order['transaction_hash']; ?> </span>
                                <a href="#" class="btn btn-outline-info btn-sm ms-2" onclick="downloadBlockchainReceipt('<?php echo $order['transaction_hash']; ?>')">
                                    <i class="fas fa-download"></i> Download Blockchain Receipt
                                </a>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-outline-danger btn-sm mt-2" onclick="disputeOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-gavel"></i> Dispute Order
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function downloadBlockchainReceipt(txHash) {
        // Simulate download (replace with real logic)
        alert('Download receipt for TxHash: ' + txHash + '\n\n(Simulated)');
    }
    function disputeOrder(orderId) {
        // Simulate dispute (replace with real smart contract logic)
        alert('Dispute order #' + orderId + ' has been logged on-chain. (Simulated)');
    }
    </script>
</body>
</html> 