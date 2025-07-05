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
    'total_revenue' => $conn->query("SELECT SUM(total_amount) FROM orders WHERE status = 'completed'")->fetchColumn(),
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_customers' => $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'customer'")->fetchColumn(),
    'total_pharmacies' => $conn->query("SELECT COUNT(*) FROM pharmacies WHERE status = 'active'")->fetchColumn()
];

// Get monthly revenue data for chart
$monthly_revenue = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           SUM(total_amount) as revenue
    FROM orders
    WHERE status = 'completed'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
")->fetchAll();

// Get top selling medicines
$top_medicines = $conn->query("
    SELECT m.name, m.manufacturer, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_quantity
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY m.id
    ORDER BY total_quantity DESC
    LIMIT 5
")->fetchAll();

// Get top performing pharmacies
$top_pharmacies = $conn->query("
    SELECT p.pharmacy_name, COUNT(o.id) as order_count, SUM(o.total_amount) as revenue
    FROM orders o
    JOIN pharmacies p ON o.pharmacy_id = p.id
    WHERE o.status = 'completed'
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll();

// Payment Reports Section
$payment_reports = $conn->query("SELECT scp.*, p.pharmacy_name, o.total_amount, o.payout_amount, o.payout_tx_hash FROM smart_contract_payments scp JOIN pharmacies p ON scp.pharmacy_id = p.id JOIN orders o ON scp.order_id = o.id ORDER BY scp.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - PharmaWeb Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stats-icon {
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
            <a href="orders.php" class="nav-link">
                <i class="fas fa-shopping-cart me-2"></i> Orders
            </a>
            <a href="transactions.php" class="nav-link">
                <i class="fas fa-exchange-alt me-2"></i> Transactions
            </a>
            <a href="reports.php" class="nav-link active">
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
            <h2>Reports & Analytics</h2>
            <div>
                <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                    <i class="fas fa-calendar me-2"></i>Date Range
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportReportModal">
                    <i class="fas fa-download me-2"></i>Export Report
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="report-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Revenue</h6>
                            <h3 class="mb-0">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-dollar-sign text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Orders</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-shopping-cart text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Customers</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_customers']); ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active Pharmacies</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_pharmacies']); ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-clinic-medical text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="chart-container">
            <h5 class="mb-4">Monthly Revenue</h5>
            <canvas id="revenueChart"></canvas>
        </div>

        <div class="row g-4">
            <!-- Top Selling Medicines -->
            <div class="col-md-6">
                <div class="table-container">
                    <h5 class="mb-4">Top Selling Medicines</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Manufacturer</th>
                                    <th>Orders</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_medicines as $medicine): ?>
                                    <tr>
                                        <td><?php echo $medicine['name']; ?></td>
                                        <td><?php echo $medicine['manufacturer']; ?></td>
                                        <td><?php echo $medicine['order_count']; ?></td>
                                        <td><?php echo $medicine['total_quantity']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Performing Pharmacies -->
            <div class="col-md-6">
                <div class="table-container">
                    <h5 class="mb-4">Top Performing Pharmacies</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pharmacy</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_pharmacies as $pharmacy): ?>
                                    <tr>
                                        <td><?php echo $pharmacy['pharmacy_name']; ?></td>
                                        <td><?php echo $pharmacy['order_count']; ?></td>
                                        <td>$<?php echo number_format($pharmacy['revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="mb-4">Payment Reports</h4>
        <form method="GET" class="row g-3 mb-3">
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
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $_GET['date_to'] ?? ''; ?>">
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Pharmacy</th>
                    <th>Amount</th>
                    <th>Payout</th>
                    <th>Tx Hash</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payment_reports as $pr): ?>
                <tr>
                    <td>#<?php echo $pr['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($pr['pharmacy_name']); ?></td>
                    <td>$<?php echo number_format($pr['amount'], 2); ?></td>
                    <td>$<?php echo number_format($pr['payout_amount'], 2); ?></td>
                    <td><span style="font-family:monospace;"><?php echo $pr['payout_tx_hash'] ? substr($pr['payout_tx_hash'],0,8).'...'.substr($pr['payout_tx_hash'],-4) : '—'; ?></span></td>
                    <td><?php echo ucfirst($pr['status']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($pr['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Date Range Modal -->
    <div class="modal fade" id="dateRangeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Date Range</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="dateRangeForm">
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="dateRangeForm" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Report Modal -->
    <div class="modal fade" id="exportReportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="exportReportForm">
                        <div class="mb-3">
                            <label class="form-label">Report Type</label>
                            <select class="form-select" required>
                                <option value="revenue">Revenue Report</option>
                                <option value="orders">Orders Report</option>
                                <option value="medicines">Medicines Report</option>
                                <option value="pharmacies">Pharmacies Report</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select class="form-select" required>
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="date" class="form-control" placeholder="From" required>
                                </div>
                                <div class="col">
                                    <input type="date" class="form-control" placeholder="To" required>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="exportReportForm" class="btn btn-primary">Export</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($monthly_revenue), 'month')); ?>,
                datasets: [{
                    label: 'Monthly Revenue',
                    data: <?php echo json_encode(array_column(array_reverse($monthly_revenue), 'revenue')); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 