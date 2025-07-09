<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is pharmacy
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'pharmacy') {
    header('Location: ../login.php');
    exit();
}

// Get pharmacy details
$stmt = $conn->prepare("SELECT * FROM pharmacies WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$pharmacy = $stmt->fetch();

if (!$pharmacy) {
    // If pharmacy record doesn't exist, create one
    $stmt = $conn->prepare("INSERT INTO pharmacies (user_id, pharmacy_name, status) VALUES (?, ?, 'active')");
    $stmt->execute([$_SESSION['user_id'], 'My Pharmacy']);
    // Now fetch the newly created pharmacy
    $stmt = $conn->prepare("SELECT * FROM pharmacies WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $pharmacy = $stmt->fetch();
}

// Get statistics
$stats = [
    'total_medicines' => $conn->query("SELECT COUNT(*) FROM medicines WHERE pharmacy_id = " . $pharmacy['id'])->fetchColumn(),
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE pharmacy_id = " . $pharmacy['id'])->fetchColumn(),
    'pending_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE pharmacy_id = " . $pharmacy['id'] . " AND status = 'pending'")->fetchColumn(),
    'total_revenue' => $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE pharmacy_id = " . $pharmacy['id'] . " AND status = 'completed'")->fetchColumn()
];

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    WHERE o.pharmacy_id = " . $pharmacy['id'] . "
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get low stock medicines
$low_stock_medicines = $conn->query("
    SELECT * FROM medicines 
    WHERE pharmacy_id = " . $pharmacy['id'] . " 
    AND stock_quantity < 10 AND stock_quantity > 0
    ORDER BY stock_quantity ASC 
    LIMIT 5
")->fetchAll();

// Get out of stock medicines
$out_of_stock_medicines = $conn->query("
    SELECT * FROM medicines 
    WHERE pharmacy_id = " . $pharmacy['id'] . " 
    AND stock_quantity = 0
    ORDER BY name ASC 
    LIMIT 5
")->fetchAll();

// Get critical stock medicines (less than 5 units)
$critical_stock_medicines = $conn->query("
    SELECT * FROM medicines 
    WHERE pharmacy_id = " . $pharmacy['id'] . " 
    AND stock_quantity < 5 AND stock_quantity > 0
    ORDER BY stock_quantity ASC 
    LIMIT 3
")->fetchAll();

// Fetch stats for dashboard
$orders_done = $conn->query("SELECT COUNT(*) FROM orders WHERE pharmacy_id = {$pharmacy['id']} AND status = 'completed'")->fetchColumn();
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE pharmacy_id = {$pharmacy['id']} AND status = 'pending'")->fetchColumn();
$completed_payments = $conn->query("SELECT COUNT(*) FROM orders WHERE pharmacy_id = {$pharmacy['id']} AND payment_status = 'completed'")->fetchColumn();
// Fetch monthly orders and medicines for graph
$monthly_orders = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM orders WHERE pharmacy_id = {$pharmacy['id']} GROUP BY month ORDER BY month ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
$monthly_medicines = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM medicines WHERE pharmacy_id = {$pharmacy['id']} GROUP BY month ORDER BY month ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
// Merge months for chart
$all_months = array_unique(array_merge(array_keys($monthly_orders), array_keys($monthly_medicines)));
sort($all_months);
$order_counts = [];
$medicine_counts = [];
foreach ($all_months as $m) {
    $order_counts[] = isset($monthly_orders[$m]) ? (int)$monthly_orders[$m] : 0;
    $medicine_counts[] = isset($monthly_medicines[$m]) ? (int)$monthly_medicines[$m] : 0;
}
$trust_score = $pharmacy['trust_score'] ?? 100;
$performance = $pharmacy['performance'] ?? 'Excellent';
// Calculate total sales amount (sum of all order totals)
$total_sales_amount = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE pharmacy_id = " . $pharmacy['id'])->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard - PharmaWeb</title>
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

        .stat-icon {
            color: #0b6e6e;
        }

        .stat-value {
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
        .dashboard-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 2.5rem;
            margin-bottom: 2.5rem;
            justify-content: flex-start;
        }
        .summary-card {
            flex: 1 1 260px;
            background: linear-gradient(135deg, #e0f7fa 0%, #f5fafd 100%);
            border-radius: 22px;
            box-shadow: 0 6px 32px rgba(11, 110, 110, 0.13), 0 2px 8px rgba(0,0,0,0.06);
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            min-width: 260px;
            min-height: 160px;
            position: relative;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .summary-card .icon {
            font-size: 2.6rem;
            color: #0b6e6e;
            margin-bottom: 0.7rem;
        }
        .summary-card .label {
            font-size: 1.2rem;
            color: #0b6e6e;
            margin-bottom: 0.3rem;
            font-weight: 500;
        }
        .summary-card .value {
            font-size: 2.3rem;
            font-weight: bold;
            color: #0b6e6e;
            letter-spacing: 1px;
        }
        .summary-card .percent {
            font-size: 1.2rem;
            color: #009688;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .summary-card .progress {
            width: 100%;
            height: 12px;
            background: #e0f7fa;
            border-radius: 8px;
            margin-top: 0.7rem;
            overflow: hidden;
        }
        .summary-card .progress-bar {
            background: linear-gradient(90deg, #0b6e6e 60%, #009688 100%);
            height: 100%;
            border-radius: 8px;
        }
        .dashboard-graph {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 6px 32px rgba(11, 110, 110, 0.13);
            padding: 2.5rem;
            margin-bottom: 2.5rem;
        }
        .dashboard-section-title {
            font-size: 1.4rem;
            color: #0b6e6e;
            font-weight: 600;
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
        }
        .dashboard-divider {
            border-top: 2px solid #e0f7fa;
            margin: 2.5rem 0 2rem 0;
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
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="medicines.php" class="nav-link">
                <i class="fas fa-pills me-2"></i> Medicines
            </a>
            <a href="orders.php" class="nav-link">
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
            <h2 style="color: #0b6e6e;">Dashboard</h2>
            <div class="user-info">
                <span class="me-3">Welcome, <?php echo htmlspecialchars($pharmacy['pharmacy_name']); ?></span>
                <i class="fas fa-user-circle fa-2x" style="color: #0b6e6e;"></i>
            </div>
        </div>

        <!-- Statistics Cards -->
        <!-- REMOVE THIS BLOCK: Duplicate stat-cards and Total Revenue card -->
        <!--
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Medicines</h6>
                            <h3 class="mb-0"><?php echo $stats['total_medicines']; ?></h3>
                        </div>
                        <i class="fas fa-pills stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Orders</h6>
                            <h3 class="mb-0"><?php echo $stats['total_orders']; ?></h3>
                        </div>
                        <i class="fas fa-shopping-cart stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Pending Orders</h6>
                            <h3 class="mb-0"><?php echo $stats['pending_orders']; ?></h3>
                        </div>
                        <i class="fas fa-clock stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Revenue</h6>
                            <h3 class="mb-0">$
                                <?php echo number_format($stats['total_revenue'], 2); ?>
                            </h3>
                        </div>
                        <i class="fas fa-dollar-sign stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        -->

        <!-- Critical Stock Alerts -->
        <?php if (!empty($critical_stock_medicines) || !empty($out_of_stock_medicines)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-danger" role="alert">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Inventory Alerts
                        </h5>
                        <?php if (!empty($out_of_stock_medicines)): ?>
                            <p class="mb-2">
                                <strong>Out of Stock:</strong> 
                                <?php echo count($out_of_stock_medicines); ?> medicine(s) need restocking
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($critical_stock_medicines)): ?>
                            <p class="mb-0">
                                <strong>Critical Stock:</strong> 
                                <?php echo count($critical_stock_medicines); ?> medicine(s) running very low
                            </p>
                        <?php endif; ?>
                        <hr>
                        <a href="medicines.php" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-pills me-1"></i>Manage Inventory
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="dashboard-section-title">Key Metrics</div>
        <div class="dashboard-summary">
            <div class="summary-card">
                <span class="icon"><i class="fas fa-pills"></i></span>
                <span class="label">Total Medicines</span>
                <span class="value"><?php echo $stats['total_medicines']; ?></span>
            </div>
            <div class="summary-card">
                <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                <span class="label">Total Orders</span>
                <span class="value"><?php echo $stats['total_orders']; ?></span>
            </div>
            <div class="summary-card">
                <span class="icon"><i class="fas fa-clock"></i></span>
                <span class="label">Pending Orders</span>
                <span class="value"><?php echo $stats['pending_orders']; ?></span>
            </div>
            <div class="summary-card">
                <span class="icon"><i class="fas fa-dollar-sign"></i></span>
                <span class="label">Total Sales</span>
                <span class="value">$<?php echo number_format($total_sales_amount, 2); ?></span>
            </div>
        </div>
        <div class="dashboard-divider"></div>
        <div class="dashboard-section-title">Monthly Trends</div>
        <div class="dashboard-graph">
            <canvas id="ordersChart" height="100"></canvas>
        </div>
        <div class="dashboard-divider"></div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="dashboard-section-title">Recent Orders</div>
                <div class="recent-activity">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">No recent orders</p>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <h6 class="mb-1">Order #<?php echo $order['id']; ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?php echo $order['status'] === 'completed' ? 'badge-completed' : 'badge-pending'; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                    <div class="text-muted mt-1">
                                        $<?php echo number_format($order['total_amount'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-section-title">Inventory Management</div>
                <div class="recent-activity">
                    <?php if (!empty($out_of_stock_medicines)): ?>
                        <div class="mb-3">
                            <h6 class="text-danger mb-2">
                                <i class="fas fa-times-circle me-1"></i>Out of Stock
                            </h6>
                            <?php foreach ($out_of_stock_medicines as $medicine): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <h6 class="mb-1 text-danger"><?php echo htmlspecialchars($medicine['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($medicine['category']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger">0 left</span>
                                        <div class="text-muted mt-1">
                                            $<?php echo number_format($medicine['price'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($low_stock_medicines)): ?>
                        <div class="mb-3">
                            <h6 class="text-warning mb-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>Low Stock
                            </h6>
                            <?php foreach ($low_stock_medicines as $medicine): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($medicine['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($medicine['category']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="stock-warning">
                                            <?php echo $medicine['stock_quantity']; ?> left
                                        </span>
                                        <div class="text-muted mt-1">
                                            $<?php echo number_format($medicine['price'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($low_stock_medicines) && empty($out_of_stock_medicines)): ?>
                        <p class="text-muted">All medicines are well stocked</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const months = <?php echo json_encode($all_months); ?>;
    const orderCounts = <?php echo json_encode($order_counts); ?>;
    const medicineCounts = <?php echo json_encode($medicine_counts); ?>;
    // Limit chart data to last 6 months
    const months_to_show = 6;
    if (months.length > months_to_show) {
        months.splice(0, months.length - months_to_show);
        orderCounts.splice(0, orderCounts.length - months_to_show);
        medicineCounts.splice(0, medicineCounts.length - months_to_show);
    }
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Orders',
                    data: orderCounts,
                    backgroundColor: 'rgba(11, 110, 110, 0.5)',
                    borderColor: '#0b6e6e',
                    borderWidth: 2,
                    borderRadius: 8,
                    yAxisID: 'y',
                    type: 'bar',
                },
                {
                    label: 'New Medicines',
                    data: medicineCounts,
                    borderColor: '#009688',
                    backgroundColor: 'rgba(0,150,136,0.12)',
                    borderWidth: 3,
        <div class="dashboard-summary">
            <div class="summary-card">
                <span class="icon"><i class="fas fa-pills"></i></span>
                <span class="label">Total Medicines</span>
                <span class="value"><?php echo $stats['total_medicines']; ?></span>
            </div>
            <div class="summary-card">
                <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                <span class="label">Total Orders</span>
                <span class="value"><?php echo $stats['total_orders']; ?></span>
            </div>
            <div class="summary-card">
                <span class="icon"><i class="fas fa-clock"></i></span>
                <span class="label">Pending Orders</span>
                <span class="value"><?php echo $stats['pending_orders']; ?></span>
            </div>
        </div>
        <div class="dashboard-graph">
            <div class="dashboard-section-title">Monthly Orders</div>
            <canvas id="ordersChart" height="100"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const months = <?php echo json_encode(array_keys($monthly_orders)); ?>;
    const counts = <?php echo json_encode(array_values($monthly_orders)); ?>;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Orders',
                data: counts,
                backgroundColor: 'rgba(11, 110, 110, 0.5)',
                borderColor: '#0b6e6e',
                borderWidth: 2,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: '#e0f7fa' } }
            }
        }
    });
    </script>
</body>
</html> 