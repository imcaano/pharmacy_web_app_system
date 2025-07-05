<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get statistics
$stats = [
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE customer_id = " . $_SESSION['user_id'])->fetchColumn(),
    'pending_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE customer_id = " . $_SESSION['user_id'] . " AND status = 'pending'")->fetchColumn(),
    'total_prescriptions' => $conn->query("SELECT COUNT(*) FROM prescriptions WHERE customer_id = " . $_SESSION['user_id'])->fetchColumn(),
    'total_spent' => $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE customer_id = " . $_SESSION['user_id'] . " AND status = 'completed'")->fetchColumn()
];

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, p.pharmacy_name 
    FROM orders o 
    JOIN pharmacies p ON o.pharmacy_id = p.id 
    WHERE o.customer_id = " . $_SESSION['user_id'] . "
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get recent prescriptions
$recent_prescriptions = $conn->query("
    SELECT * FROM prescriptions 
    WHERE customer_id = " . $_SESSION['user_id'] . "
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - PharmaWeb</title>
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

        .dashboard-card i {
            color: #0b6e6e;
        }

        .dashboard-stats h5 {
            color: #0b6e6e;
        }

        .recent-activity i {
            color: #0b6e6e;
        }

        .activity-item {
            border-left: 3px solid #0b6e6e;
        }

        .activity-item:hover {
            background-color: rgba(11, 110, 110, 0.05);
        }

        .view-all-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button (Hamburger) -->
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-clinic-medical me-2"></i>PharmaWeb</h4>
        </div>
        <nav class="mt-4">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="medicines.php" class="nav-link">
                <i class="fas fa-pills me-2"></i> Browse Medicines
            </a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart me-2"></i> Cart
            </a>
            <a href="orders.php" class="nav-link">
                <i class="fas fa-shopping-cart me-2"></i> My Orders
            </a>
            <a href="prescriptions.php" class="nav-link">
                <i class="fas fa-file-medical me-2"></i> Prescriptions
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard</h2>
            <div class="user-info">
                <span class="me-3">Welcome, <?php echo $user['email']; ?></span>
                <i class="fas fa-user-circle fa-2x"></i>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon stat-orders"><i class="fas fa-shopping-cart"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                        <div class="text-muted">Total Orders</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon stat-pending"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                        <div class="text-muted">Pending Orders</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon stat-prescriptions"><i class="fas fa-file-medical"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $stats['total_prescriptions']; ?></div>
                        <div class="text-muted">Prescriptions</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon stat-spent"><i class="fas fa-dollar-sign"></i></div>
                    <div>
                        <div class="stat-value">$<?php echo number_format($stats['total_spent'], 2); ?></div>
                        <div class="text-muted">Total Spent</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Prescription -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="upload-prescription">
                    <i class="fas fa-file-upload upload-icon"></i>
                    <h4>Upload New Prescription</h4>
                    <p class="text-muted">Upload your prescription to order medicines</p>
                    <form action="upload_prescription.php" method="POST" enctype="multipart/form-data" class="mt-3">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="prescription" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Upload
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="recent-activity">
                    <h5 class="mb-4">Recent Orders</h5>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">Order #<?php echo $order['id']; ?></h6>
                                <small class="text-muted"><?php echo $order['pharmacy_name']; ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <div class="text-muted mt-1">
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="recent-activity">
                    <h5 class="mb-4">Recent Prescriptions</h5>
                    <?php foreach ($recent_prescriptions as $prescription): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">Prescription #<?php echo $prescription['id']; ?></h6>
                                <small class="text-muted">Dr. <?php echo $prescription['doctor_name']; ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $prescription['status'] === 'verified' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($prescription['status']); ?>
                                </span>
                                <div class="text-muted mt-1">
                                    <?php echo date('M d, Y', strtotime($prescription['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            document.body.classList.toggle('sidebar-open');
        });
        // Optional: close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                    sidebar.classList.remove('open');
                    document.body.classList.remove('sidebar-open');
                }
            }
        });
    </script>
</body>
</html> 