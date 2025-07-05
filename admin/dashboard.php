<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$stats = [
    'total_pharmacies' => $conn->query("SELECT COUNT(*) FROM pharmacies")->fetchColumn(),
    'total_customers' => $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'customer'")->fetchColumn(),
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_medicines' => $conn->query("SELECT COUNT(*) FROM medicines")->fetchColumn()
];

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.email as customer_email, p.pharmacy_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    JOIN pharmacies p ON o.pharmacy_id = p.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get recent pharmacies
$recent_pharmacies = $conn->query("
    SELECT p.*, u.email 
    FROM pharmacies p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.id DESC 
    LIMIT 5
")->fetchAll();

// --- Chart Data Queries ---
// Orders per Month (last 12 months)
$orders_per_month = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM orders GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
$orders_per_month = array_reverse($orders_per_month);
// Revenue per Month (last 12 months)
$revenue_per_month = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as revenue FROM orders GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
$revenue_per_month = array_reverse($revenue_per_month);
// Order Status Distribution (current year)
$status_dist = $conn->query("SELECT status, COUNT(*) as count FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PharmaWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-user-shield me-2"></i>PharmaWeb Admin</h4>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link active">
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
            <a href="orders.php" class="nav-link">
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
            <h2>Dashboard</h2>
            <div class="user-info">
                <span class="me-3">Welcome, Admin</span>
                <i class="fas fa-user-circle fa-2x"></i>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Pharmacies</h6>
                            <h3><?php echo $stats['total_pharmacies']; ?></h3>
                        </div>
                        <i class="fas fa-clinic-medical stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Customers</h6>
                            <h3><?php echo $stats['total_customers']; ?></h3>
                        </div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Orders</h6>
                            <h3><?php echo $stats['total_orders']; ?></h3>
                        </div>
                        <i class="fas fa-shopping-cart stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Medicines</h6>
                            <h3><?php echo $stats['total_medicines']; ?></h3>
                        </div>
                        <i class="fas fa-pills stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Table -->
        <div class="row g-4">
            <div class="col-md-8">
                <div class="recent-activity">
                    <h5 class="mb-4"><i class="fas fa-exchange-alt me-2"></i>Recent Transactions</h5>
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo date('m/d/Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    <td>
                                        <span class="badge <?php
                                            if ($order['status'] === 'completed') echo 'badge-success';
                                            elseif ($order['status'] === 'pending') echo 'badge-warning';
                                            else echo 'badge-info';
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <div class="recent-activity">
                    <h5 class="mb-4"><i class="fas fa-clinic-medical me-2"></i>Recent Pharmacies</h5>
                    <?php foreach ($recent_pharmacies as $pharmacy): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1"><?php echo $pharmacy['pharmacy_name']; ?></h6>
                                <small class="text-muted"><?php echo $pharmacy['email']; ?></small>
                            </div>
                            <span class="badge bg-<?php echo $pharmacy['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($pharmacy['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- New Row for Charts -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card p-3 mb-3 shadow-sm">
                    <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Orders per Month</h6>
                    <canvas id="ordersChart" height="180"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 mb-3 shadow-sm">
                    <h6 class="mb-3"><i class="fas fa-dollar-sign me-2"></i>Revenue per Month</h6>
                    <canvas id="revenueChart" height="180"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 mb-3 shadow-sm">
                    <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Order Status Distribution</h6>
                    <canvas id="statusChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    // Chart Data from PHP
    const ordersPerMonth = <?php echo json_encode($orders_per_month); ?>;
    const revenuePerMonth = <?php echo json_encode($revenue_per_month); ?>;
    const statusDist = <?php echo json_encode($status_dist); ?>;
    // Orders Chart
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'bar',
        data: {
            labels: ordersPerMonth.map(x => x.month),
            datasets: [{
                label: 'Orders',
                data: ordersPerMonth.map(x => x.count),
                backgroundColor: '#0b6e6e',
                borderRadius: 8
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenuePerMonth.map(x => x.month),
            datasets: [{
                label: 'Revenue',
                data: revenuePerMonth.map(x => x.revenue),
                backgroundColor: 'rgba(16,185,129,0.2)',
                borderColor: '#10b981',
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusDist),
            datasets: [{
                data: Object.values(statusDist),
                backgroundColor: ['#10b981', '#f59e42', '#e53e3e', '#3498db', '#a78bfa'],
                borderWidth: 1
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom' } }
        }
    });
    </script>
</body>
</html> 