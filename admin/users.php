<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle user status update
if (isset($_POST['update_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
}

// Handle user deletion
$delete_error = '';
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Delete user's cart items
        $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete prescriptions
        $stmt = $conn->prepare("DELETE FROM prescriptions WHERE customer_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete user's order items and orders
        $stmt = $conn->prepare("DELETE oi FROM order_items oi 
                              INNER JOIN orders o ON oi.order_id = o.id 
                              WHERE o.customer_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete user's orders
        $stmt = $conn->prepare("DELETE FROM orders WHERE customer_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete user's dispute logs
        $stmt = $conn->prepare("DELETE FROM dispute_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // If user is a pharmacy, delete their medicines first
        $stmt = $conn->prepare("DELETE m FROM medicines m 
                              INNER JOIN pharmacies p ON m.pharmacy_id = p.id 
                              WHERE p.user_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete pharmacy record if exists
        $stmt = $conn->prepare("DELETE FROM pharmacies WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Finally delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
        // Commit transaction
        $conn->commit();
        
        // If we reach here, deletion was successful
        if (!headers_sent()) {
    header('Location: users.php');
    exit();
        }
        
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        $delete_error = "Error deleting user: " . $e->getMessage();
        error_log("Failed to delete user $user_id: " . $e->getMessage());
    }
}

// Handle user update
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $user_type = $_POST['user_type'];
    $metamask_address = $_POST['metamask_address'];
    
    $stmt = $conn->prepare("UPDATE users SET email = ?, user_type = ?, metamask_address = ? WHERE id = ?");
    $stmt->execute([$email, $user_type, $metamask_address, $user_id]);
    
    header('Location: users.php');
    exit();
}

// Get all users
$users = $conn->query("
    SELECT u.*, 
           CASE 
               WHEN u.user_type = 'pharmacy' THEN p.pharmacy_name 
               ELSE NULL 
           END as pharmacy_name
    FROM users u
    LEFT JOIN pharmacies p ON u.id = p.user_id
    ORDER BY u.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - PharmaWeb Admin</title>
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

        body {
            background: #f8f9fa;
        }

        .user-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(11, 110, 110, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(11, 110, 110, 0.1);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .user-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-dark), var(--primary-light));
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(11, 110, 110, 0.15);
            border-color: var(--primary-light);
        }

        .user-type-badge {
            padding: 0.4rem 1.5rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
            margin: 0 auto;
        }

        .type-admin {
            background: var(--primary);
            color: white;
        }

        .type-customer {
            background: var(--accent);
            color: white;
        }

        .type-pharmacy {
            background: var(--primary-light);
            color: white;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-active:hover {
            background: #166534;
            color: white;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-inactive:hover {
            background: #991b1b;
            color: white;
        }

        .btn-group .btn {
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.2rem;
        }

        .btn-group .btn:hover {
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 77, 64, 0.2);
        }

        .btn-outline-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .user-info {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .user-info p {
            color: #6b7280;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .user-card:hover .user-info p {
            color: #374151;
        }

        .user-info i {
            width: 20px;
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .user-header {
            margin-bottom: 1.5rem;
        }

        .user-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .user-card:hover .user-name {
            color: var(--primary-light);
        }

        .user-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .user-detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #4a5568;
            font-size: 0.95rem;
        }

        .user-detail-item i {
            width: 20px;
            color: var(--primary);
            text-align: center;
        }

        .user-meta {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 77, 64, 0.1);
        }

        .user-meta small {
            color: #4a5568;
        }

        .add-user-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .add-user-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 77, 64, 0.2);
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .main-content {
            background: #f8f9fa;
            padding: 2rem;
        }

        h2 {
            color: var(--primary);
            margin-bottom: 2rem;
        }
.admin-user-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  padding: 1.5rem 2rem;
  margin-bottom: 2rem;
  border-left: 6px solid #0b6e6e;
  display: flex;
  flex-direction: column;
  height: 100%;
}
.admin-user-header {
  display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;
}
.admin-user-title { font-weight: 700; font-size: 1.1rem; letter-spacing: 0.5px; }
.admin-user-type {
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
  color: #fff;
  background: #218c74;
  font-size: 0.98rem;
  text-transform: capitalize;
}
.admin-user-type.admin { background: #0b6e6e; }
.admin-user-type.customer { background: #27ae60; }
.admin-user-type.pharmacy { background: #2980b9; }
.admin-user-info { color: #555; font-size: 0.97rem; margin-bottom: 0.5rem; }
.admin-user-meta { color: #888; font-size: 0.95rem; margin-bottom: 0.5rem; }
.admin-user-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }
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
            <a href="users.php" class="nav-link active">
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
        <?php if ($delete_error): ?>
            <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $delete_error; ?></div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Users</h2>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download me-2"></i>Export
                </button>
                <button class="btn add-user-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </button>
            </div>
        </div>

        <!-- Users Grid -->
        <div class="row g-4">
            <?php foreach ($users as $user): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="admin-user-card">
                        <div class="admin-user-header">
                            <div class="admin-user-title"><?php echo htmlspecialchars($user['name'] ?? $user['email']); ?></div>
                            <span class="admin-user-type <?php echo strtolower($user['user_type']); ?>"><?php echo ucfirst($user['user_type']); ?></span>
                        </div>
                        <div class="admin-user-info">
                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?><br>
                            <?php if ($user['pharmacy_name']): ?>
                                <i class="fas fa-clinic-medical me-1"></i>Pharmacy: <b><?php echo htmlspecialchars($user['pharmacy_name']); ?></b><br>
                            <?php endif; ?>
                            <i class="fas fa-wallet me-1"></i>MetaMask: <span style="font-family:monospace;">
                                <?php
                                  $addr = $user['metamask_address'] ?? '';
                                  if ($addr && strlen($addr) > 12) {
                                    echo htmlspecialchars(substr($addr,0,6) . '...' . substr($addr,-4));
                                  } else {
                                    echo htmlspecialchars($addr ?: 'Not provided');
                                  }
                                ?>
                                    </span>
                                </div>
                        <div class="admin-user-meta">
                            <i class="fas fa-clock me-1"></i>Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </div>
                        <div class="admin-user-actions">
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline delete-user-form">
                                        <input type="hidden" name="delete_user" value="1">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select class="form-select" required>
                                <option value="customer">Customer</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">MetaMask Address</label>
                            <input type="text" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addUserForm" class="btn btn-primary">Add User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modals -->
    <?php foreach ($users as $user): ?>
    <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select name="user_type" class="form-select" required>
                                <option value="customer" <?php echo $user['user_type'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="pharmacy" <?php echo $user['user_type'] === 'pharmacy' ? 'selected' : ''; ?>>Pharmacy</option>
                                <option value="admin" <?php echo $user['user_type'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">MetaMask Address</label>
                            <input type="text" name="metamask_address" class="form-control" value="<?php echo $user['metamask_address']; ?>" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm" action="export_users.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select name="format" class="form-select" required>
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select name="user_type" class="form-select">
                                <option value="">All Users</option>
                                <option value="customer">Customers</option>
                                <option value="pharmacy">Pharmacies</option>
                                <option value="admin">Admins</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status (for Pharmacies)</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.querySelectorAll('.delete-user-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the user and all related records!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit the form
                    this.submit();
                }
            });
        });
    });
    </script>
</body>
</html> 