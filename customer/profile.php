<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get customer details
$customer = $conn->query("
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COUNT(DISTINCT p.id) as total_prescriptions,
           SUM(o.total_amount) as total_spent
    FROM users u 
    LEFT JOIN orders o ON u.id = o.customer_id 
    LEFT JOIN prescriptions p ON u.id = p.customer_id 
    WHERE u.id = " . $_SESSION['user_id'] . "
    GROUP BY u.id
")->fetch();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $email = $_POST['email'] ?? '';
    $metamask_address = $_POST['metamask_address'] ?? '';
    
    // Update user details
    $stmt = $conn->prepare("
        UPDATE users 
        SET email = ?, metamask_address = ? 
        WHERE id = ?
    ");
    $stmt->execute([$email, $metamask_address, $_SESSION['user_id']]);
    
    // Update session
    $_SESSION['email'] = $email;
    $_SESSION['metamask_address'] = $metamask_address;
    
    // Refresh customer details
    $customer = $conn->query("
        SELECT u.*, 
               COUNT(DISTINCT o.id) as total_orders,
               COUNT(DISTINCT p.id) as total_prescriptions,
               SUM(o.total_amount) as total_spent
        FROM users u 
        LEFT JOIN orders o ON u.id = o.customer_id 
        LEFT JOIN prescriptions p ON u.id = p.customer_id 
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

$email = $customer['email'] ?? $_SESSION['email'] ?? '';
$metamask_address = $customer['metamask_address'] ?? $_SESSION['metamask_address'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PharmaWeb</title>
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

        .profile-card i {
            color: #0b6e6e;
        }

        .profile-info h5 {
            color: #0b6e6e;
        }

        .nav-pills .nav-link.active {
            background-color: #0b6e6e;
        }

        .nav-pills .nav-link {
            color: #0b6e6e;
        }

        .form-label {
            color: #0b6e6e;
        }

        .save-changes-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .save-changes-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }

        .profile-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-right: 1.5rem;
        }

        .profile-info h3 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            color: #6b7280;
            margin-bottom: 0;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .info-card label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .info-card p {
            color: #1f2937;
            font-weight: 500;
            margin-bottom: 0;
        }

        .edit-profile-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .edit-profile-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .section-title {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
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
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="medicines.php" class="nav-link">
                <i class="fas fa-pills me-2"></i> Browse Medicines
            </a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart me-2"></i> Cart
            </a>
            <a href="orders.php" class="nav-link">
                <i class="fas fa-box me-2"></i> My Orders
            </a>
            <a href="prescriptions.php" class="nav-link">
                <i class="fas fa-file-medical me-2"></i> Prescriptions
            </a>
            <a href="profile.php" class="nav-link active">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($customer['email']); ?></h3>
                    <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($email); ?></p>
                    <p><i class="fas fa-calendar me-2"></i>Member since <?php echo date('F Y', strtotime($customer['created_at'])); ?></p>
                </div>
            </div>

            <h5 class="section-title"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="info-card">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <label>MetaMask Address</label>
                        <div class="input-group">
                            <input type="text" id="metamaskAddress" class="form-control" value="<?php echo htmlspecialchars($metamask_address); ?>" readonly>
                            <button type="button" class="btn btn-outline-secondary" onclick="connectWalletAndFill()">
                                <i class="fab fa-ethereum"></i> Connect Wallet
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <label>Role</label>
                        <input type="text" class="form-control" value="customer" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/metamask.js"></script>
    <script>
        async function connectWalletAndFill() {
            const address = await window.connectWallet();
            if (address) {
                document.getElementById('metamaskAddress').value = address;
            }
        }
        function switchToWalletLogin() {
            alert('Switch to wallet login (simulated).');
        }

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