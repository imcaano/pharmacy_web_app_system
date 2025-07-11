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

.order-expert-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  padding: 1.5rem 2rem;
  margin-bottom: 2rem;
  border-left: 6px solid #0b6e6e;
}
.order-expert-header {
  display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;
}
.order-expert-title { font-weight: 700; font-size: 1.1rem; letter-spacing: 0.5px; }
.order-expert-status {
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
  color: #fff;
  background: #218c74;
  font-size: 0.98rem;
  text-transform: capitalize;
}
.order-expert-status.pending { background: #f39c12; }
.order-expert-status.completed { background: #27ae60; }
.order-expert-status.cancelled { background: #e74c3c; }
.order-expert-info { color: #555; font-size: 0.97rem; margin-bottom: 0.5rem; }
.order-expert-items { margin-top: 0.5rem; }
.order-expert-item { display: flex; justify-content: space-between; border-bottom: 1px solid #f1f1f1; padding: 6px 0; }
.order-expert-total { font-weight: bold; font-size: 1.1rem; color: #0b6e6e; margin-top: 0.5rem; }
.order-expert-date { color: #888; font-size: 0.95rem; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-clinic-medical me-2"></i>PharmaWeb</h4>
        </div>
        <nav class="mt-4">
            <a href="dashboard.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo ' active'; ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="medicines.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='medicines.php') echo ' active'; ?>">
                <i class="fas fa-pills me-2"></i> Browse Medicines
            </a>
            <a href="cart.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='cart.php') echo ' active'; ?>">
                <i class="fas fa-shopping-cart me-2"></i> Cart
            </a>
            <a href="orders.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='orders.php') echo ' active'; ?>">
                <i class="fas fa-box me-2"></i> My Orders
            </a>
            <a href="prescriptions.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='prescriptions.php') echo ' active'; ?>">
                <i class="fas fa-file-medical me-2"></i> Prescriptions
            </a>
            <a href="profile.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='profile.php') echo ' active'; ?>">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link logout-btn" style="background:none;color:#fff;font-weight:500;padding:12px 0 12px 18px;text-align:left;display:flex;align-items:center;border-radius:0;font-size:1.1rem;">
                <i class="fas fa-sign-out-alt me-2" style="font-size:1.2rem;"></i> Logout
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
        <div class="order-expert-card">
          <div class="order-expert-header">
            <div class="order-expert-title">Order #<?php echo $order['id']; ?> <span class="order-expert-date">(<?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>)</span></div>
            <span class="order-expert-status <?php echo strtolower($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span>
          </div>
          <div class="order-expert-info">
            <i class="fas fa-clinic-medical me-1"></i>Pharmacy: <b><?php echo htmlspecialchars($order['pharmacy_name'] ?? ''); ?></b>
          </div>
          <div class="order-expert-items">
            <?php if (!empty($order_items[$order['id']])): ?>
              <?php foreach ($order_items[$order['id']] as $item): ?>
                <div class="order-expert-item">
                  <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                  <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <div class="order-expert-total">Total: $<?php echo number_format($order['total_amount'], 2); ?></div>
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