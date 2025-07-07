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
    SELECT p.*, u.email, u.metamask_address 
    FROM pharmacies p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.user_id = " . $_SESSION['user_id']
)->fetch();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $pharmacy_name = $_POST['pharmacy_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $license_number = $_POST['license_number'];
    $email = $_POST['email'];
    $metamask_address = $_POST['metamask_address'];
    
    // Update pharmacy details
    $stmt = $conn->prepare("
        UPDATE pharmacies 
        SET pharmacy_name = ?, address = ?, phone = ?, license_number = ? 
        WHERE user_id = ?
    ");
    $stmt->execute([$pharmacy_name, $address, $phone, $license_number, $_SESSION['user_id']]);
    
    // Update user details
    $stmt = $conn->prepare("
        UPDATE users 
        SET email = ?, metamask_address = ? 
        WHERE id = ?
    ");
    $stmt->execute([$email, $metamask_address, $_SESSION['user_id']]);
    
    // Refresh pharmacy details
    $pharmacy = $conn->query("
        SELECT p.*, u.email, u.metamask_address 
        FROM pharmacies p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = " . $_SESSION['user_id']
    )->fetch();
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

if (!$pharmacy): ?>
    <div class="main-content">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="profile-card text-center">
                    <h3 class="mb-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Pharmacy Profile Not Found</h3>
                    <p class="text-muted">We could not find your pharmacy profile. Please contact support or try again later.</p>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php exit; ?>
<?php endif; ?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - PharmaWeb</title>
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
            <a href="orders.php" class="nav-link">
                <i class="fas fa-shopping-cart me-2"></i> Orders
            </a>
            <a href="prescriptions.php" class="nav-link">
                <i class="fas fa-file-medical me-2"></i> Prescriptions
            </a>
            <a href="profile.php" class="nav-link active">
                <i class="fas fa-user-cog me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link" style="background-color: #0b6e6e; color: white;">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-clinic-medical" style="color: #0b6e6e;"></i>
                        </div>
                        <h3 style="color: #0b6e6e;"><?php echo $pharmacy['pharmacy_name'] ?? ''; ?></h3>
                        <p class="text-muted">Pharmacy User Profile</p>
                    </div>
                    <form method="POST" class="mb-5">
                        <h5 class="mb-4">Pharmacy Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Pharmacy Name</label>
                                    <input type="text" class="form-control" name="pharmacy_name" value="<?php echo htmlspecialchars($pharmacy['pharmacy_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($pharmacy['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($pharmacy['phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">License Number</label>
                                    <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars($pharmacy['license_number'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3" required><?php echo htmlspecialchars($pharmacy['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <h5 class="mb-4 mt-5">Wallet & Role</h5>
                        <div class="mb-3">
                            <label class="form-label">MetaMask Address</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="metamask_address" id="metamaskAddress" value="<?php echo htmlspecialchars($pharmacy['metamask_address'] ?? ''); ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="connectWalletAndFill()">
                                    <i class="fab fa-ethereum"></i> Connect Wallet
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="Pharmacy" readonly>
                        </div>
                        
                        <button type="submit" name="update_profile" class="save-changes-btn">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </form>
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
    </script>
</body>
</html> 