<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get admin details
$admin = $conn->query("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as total_pharmacies,
           COUNT(DISTINCT c.id) as total_customers,
           COUNT(DISTINCT o.id) as total_orders,
           SUM(o.total_amount) as total_revenue
    FROM users u 
    LEFT JOIN pharmacies p ON u.id = p.user_id 
    LEFT JOIN users c ON c.user_type = 'customer' 
    LEFT JOIN orders o ON o.pharmacy_id = p.id 
    WHERE u.id = " . $_SESSION['user_id'] . "
    GROUP BY u.id
")->fetch();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $email = $_POST['email'];
    $metamask_address = $_POST['metamask_address'];
    
    // Update user details
    $stmt = $conn->prepare("
        UPDATE users 
        SET email = ?, metamask_address = ? 
        WHERE id = ?
    ");
    $stmt->execute([$email, $metamask_address, $_SESSION['user_id']]);
    
    // Refresh admin details
    $admin = $conn->query("
        SELECT u.*, 
               COUNT(DISTINCT p.id) as total_pharmacies,
               COUNT(DISTINCT c.id) as total_customers,
               COUNT(DISTINCT o.id) as total_orders,
               SUM(o.total_amount) as total_revenue
        FROM users u 
        LEFT JOIN pharmacies p ON u.id = p.user_id 
        LEFT JOIN users c ON c.user_type = 'customer' 
        LEFT JOIN orders o ON o.pharmacy_id = p.id 
        WHERE u.id = " . $_SESSION['user_id'] . "
        GROUP BY u.id
    ")->fetch();
}

// Handle password update
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            $password_success = true;
        } else {
            $password_error = "New passwords do not match";
        }
    } else {
        $password_error = "Current password is incorrect";
    }
}

// Get recent activities
$recent_pharmacies = $conn->query("
    SELECT p.*, u.email 
    FROM pharmacies p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
")->fetchAll();

$recent_orders = $conn->query("
    SELECT o.*, u.email as customer_email, p.name as pharmacy_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    JOIN pharmacies p ON o.pharmacy_id = p.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - PharmaWeb Admin</title>
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
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar me-2"></i> Reports
            </a>
            <a href="profile.php" class="nav-link active">
                <i class="fas fa-user-cog me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link mt-5">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-md-8">
                <div class="profile-card mb-4">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3><?php echo $admin['email']; ?></h3>
                        <p class="text-muted">Administrator</p>
                    </div>

                    <!-- Profile Information Form -->
                    <form method="POST" class="mb-5">
                        <h5 class="mb-4">Profile Information</h5>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?php echo $admin['email']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">MetaMask Address</label>
                            <div class="metamask-address mb-2"><?php echo $admin['metamask_address']; ?></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="connectMetaMask()">
                                <i class="fab fa-ethereum me-2"></i>Connect MetaMask
                            </button>
                            <input type="hidden" name="metamask_address" value="<?php echo $admin['metamask_address']; ?>">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </form>

                    <!-- Change Password Form -->
                    <form method="POST">
                        <h5 class="mb-4">Change Password</h5>
                        <?php if (isset($password_success)): ?>
                            <div class="alert alert-success">
                                Password updated successfully
                            </div>
                        <?php endif; ?>
                        <?php if (isset($password_error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $password_error; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i>Update Password
                        </button>
                    </form>
                </div>

                <!-- Recent Activity -->
                <h5 class="mb-4">Recent Activity</h5>
                
                <!-- Recent Pharmacies -->
                <div class="activity-card">
                    <h6 class="mb-3">Recent Pharmacies</h6>
                    <?php foreach ($recent_pharmacies as $pharmacy): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="activity-icon">
                                <i class="fas fa-clinic-medical"></i>
                            </div>
                            <div>
                                <p class="mb-0"><?php echo $pharmacy['name']; ?></p>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($pharmacy['created_at'])); ?> - 
                                    <?php echo $pharmacy['email']; ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Recent Orders -->
                <div class="activity-card">
                    <h6 class="mb-3">Recent Orders</h6>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="activity-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div>
                                <p class="mb-0">Order #<?php echo $order['id']; ?> at <?php echo $order['pharmacy_name']; ?></p>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?> - 
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Statistics -->
                <div class="stats-card mb-4">
                    <div class="stats-icon">
                        <i class="fas fa-clinic-medical"></i>
                    </div>
                    <div class="stats-value"><?php echo $admin['total_pharmacies']; ?></div>
                    <div class="stats-label">Total Pharmacies</div>
                </div>

                <div class="stats-card mb-4">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-value"><?php echo $admin['total_customers']; ?></div>
                    <div class="stats-label">Total Customers</div>
                </div>

                <div class="stats-card mb-4">
                    <div class="stats-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stats-value"><?php echo $admin['total_orders']; ?></div>
                    <div class="stats-label">Total Orders</div>
                </div>

                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-value">$<?php echo number_format($admin['total_revenue'], 2); ?></div>
                    <div class="stats-label">Total Revenue</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function connectMetaMask() {
            if (typeof window.ethereum !== 'undefined') {
                try {
                    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                    const metamaskAddress = accounts[0];
                    document.querySelector('input[name="metamask_address"]').value = metamaskAddress;
                    document.querySelector('.metamask-address').textContent = metamaskAddress;
                } catch (error) {
                    console.error('Error connecting to MetaMask:', error);
                }
            } else {
                alert('Please install MetaMask to use this feature');
            }
        }
    </script>
</body>
</html> 