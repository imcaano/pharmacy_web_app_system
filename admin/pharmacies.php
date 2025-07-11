<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle pharmacy status update
if (isset($_POST['update_status'])) {
    $pharmacy_id = $_POST['pharmacy_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE pharmacies SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $pharmacy_id]);
}

// Handle pharmacy deletion
$delete_error = '';
if (isset($_POST['delete_pharmacy'])) {
    $pharmacy_id = $_POST['pharmacy_id'];
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get the user_id of the pharmacy first
        $stmt = $conn->prepare("SELECT user_id FROM pharmacies WHERE id = ?");
        $stmt->execute([$pharmacy_id]);
        $pharmacy = $stmt->fetch();
        $user_id = $pharmacy['user_id'];
        
        // Delete medicines of this pharmacy first
        $stmt = $conn->prepare("DELETE FROM medicines WHERE pharmacy_id = ?");
        $stmt->execute([$pharmacy_id]);
        
        // Delete order items related to pharmacy's medicines
        $stmt = $conn->prepare("DELETE oi FROM order_items oi 
                              INNER JOIN orders o ON oi.order_id = o.id 
                              WHERE o.pharmacy_id = ?");
        $stmt->execute([$pharmacy_id]);
        
        // Delete orders related to this pharmacy
        $stmt = $conn->prepare("DELETE FROM orders WHERE pharmacy_id = ?");
        $stmt->execute([$pharmacy_id]);
        
        // Delete prescriptions related to this pharmacy
        $stmt = $conn->prepare("DELETE FROM prescriptions WHERE pharmacy_id = ?");
        $stmt->execute([$pharmacy_id]);
        
        // Delete dispute logs related to pharmacy's orders
        $stmt = $conn->prepare("DELETE dl FROM dispute_logs dl 
                              INNER JOIN orders o ON dl.order_id = o.id 
                              WHERE o.pharmacy_id = ?");
        $stmt->execute([$pharmacy_id]);
        
        // Delete the pharmacy record
    $stmt = $conn->prepare("DELETE FROM pharmacies WHERE id = ?");
    $stmt->execute([$pharmacy_id]);
    
        // Delete the user account associated with this pharmacy
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Commit transaction
        $conn->commit();
        
        // If we reach here, deletion was successful
        if (!headers_sent()) {
    header('Location: pharmacies.php');
    exit();
        }
        
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        $delete_error = "Error deleting pharmacy: " . $e->getMessage();
        error_log("Failed to delete pharmacy $pharmacy_id: " . $e->getMessage());
    }
}

// Handle pharmacy update
if (isset($_POST['update_pharmacy'])) {
    $pharmacy_id = $_POST['pharmacy_id'];
    $pharmacy_name = $_POST['pharmacy_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $license_number = $_POST['license_number'];
    
    $stmt = $conn->prepare("UPDATE pharmacies SET 
        pharmacy_name = ?, 
        address = ?, 
        phone = ?, 
        license_number = ? 
        WHERE id = ?");
    $stmt->execute([
        $pharmacy_name, 
        $address, 
        $phone, 
        $license_number, 
        $pharmacy_id
    ]);
    
    header('Location: pharmacies.php');
    exit();
}

// Get all pharmacies with user details
$pharmacies = $conn->query("
    SELECT p.*, u.email, u.created_at as user_created_at 
    FROM pharmacies p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pharmacies - PharmaWeb Admin</title>
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

        .pharmacy-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(124, 58, 237, 0.1);
            position: relative;
            overflow: hidden;
        }

        .pharmacy-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-dark), var(--primary-light));
            transition: height 0.3s ease;
        }

        .pharmacy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.15);
            border-color: var(--primary-light);
        }

        .pharmacy-card:hover::before {
            height: 6px;
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
            border-color: #0b6e6e;
            color: #0b6e6e;
        }

        .btn-outline-primary:hover {
            background: #0b6e6e;
            color: #fff;
            border-color: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }

        .btn-outline-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .pharmacy-info {
            padding: 1.5rem;
        }

        .pharmacy-info p {
            color: #6b7280;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .pharmacy-card:hover .pharmacy-info p {
            color: #374151;
        }

        .pharmacy-info i {
            width: 20px;
            color: #0b6e6e;
            margin-right: 0.5rem;
        }

        .pharmacy-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .pharmacy-name {
            color: #0b6e6e;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .pharmacy-card:hover .pharmacy-name {
            color: var(--primary);
        }

        .pharmacy-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .pharmacy-meta small {
            color: #6b7280;
        }

        .add-pharmacy-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .add-pharmacy-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
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
.admin-pharmacy-card {
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
.admin-pharmacy-header {
  display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;
}
.admin-pharmacy-title { font-weight: 700; font-size: 1.1rem; letter-spacing: 0.5px; }
.admin-pharmacy-status {
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
  color: #fff;
  background: #218c74;
  font-size: 0.98rem;
  text-transform: capitalize;
}
.admin-pharmacy-status.active { background: #27ae60; }
.admin-pharmacy-status.inactive { background: #e74c3c; }
.admin-pharmacy-info { color: #555; font-size: 0.97rem; margin-bottom: 0.5rem; }
.admin-pharmacy-meta { color: #888; font-size: 0.95rem; margin-bottom: 0.5rem; }
.admin-pharmacy-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }
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
            <a href="pharmacies.php" class="nav-link active">
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
            <a href="../logout.php" class="nav-link logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if ($delete_error): ?>
            <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $delete_error; ?></div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Pharmacies</h2>
            <button class="btn add-pharmacy-btn" data-bs-toggle="modal" data-bs-target="#addPharmacyModal">
                <i class="fas fa-plus me-2"></i>Add New Pharmacy
            </button>
        </div>

        <!-- Pharmacies Grid -->
        <div class="row g-4">
            <?php foreach ($pharmacies as $pharmacy): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="admin-pharmacy-card">
                        <div class="admin-pharmacy-header">
                            <div class="admin-pharmacy-title"><?php echo htmlspecialchars($pharmacy['pharmacy_name']); ?></div>
                            <span class="admin-pharmacy-status <?php echo strtolower($pharmacy['status']); ?>"><?php echo ucfirst($pharmacy['status']); ?></span>
                        </div>
                        <div class="admin-pharmacy-info">
                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($pharmacy['email']); ?><br>
                            <i class="fas fa-wallet me-1"></i>MetaMask: <span style="font-family:monospace;">
                                <?php
                                  $addr = $pharmacy['metamask_address'] ?? '';
                                  if ($addr && strlen($addr) > 12) {
                                    echo htmlspecialchars(substr($addr,0,6) . '...' . substr($addr,-4));
                                  } else {
                                    echo htmlspecialchars($addr ?: 'Not provided');
                                  }
                                ?>
                            </span><br>
                            <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($pharmacy['address']); ?><br>
                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($pharmacy['phone']); ?><br>
                            <i class="fas fa-id-card me-1"></i>License: <?php echo htmlspecialchars($pharmacy['license_number']); ?>
                            </div>
                        <div class="admin-pharmacy-meta">
                            <i class="fas fa-clock me-1"></i>Joined: <?php echo date('M d, Y', strtotime($pharmacy['user_created_at'])); ?>
                        </div>
                        <div class="admin-pharmacy-actions">
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPharmacyModal<?php echo $pharmacy['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                </button>
                                    <form method="POST" class="d-inline delete-pharmacy-form">
                                        <input type="hidden" name="delete_pharmacy" value="1">
                                            <input type="hidden" name="pharmacy_id" value="<?php echo $pharmacy['id']; ?>">
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

    <!-- Add Pharmacy Modal -->
    <div class="modal fade" id="addPharmacyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Pharmacy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPharmacyForm">
                        <div class="mb-3">
                            <label class="form-label">Pharmacy Name</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">License Number</label>
                            <input type="text" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addPharmacyForm" class="btn btn-primary">Add Pharmacy</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Pharmacy Modals -->
    <?php foreach ($pharmacies as $pharmacy): ?>
    <div class="modal fade" id="editPharmacyModal<?php echo $pharmacy['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pharmacy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="pharmacy_id" value="<?php echo $pharmacy['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Pharmacy Name</label>
                            <input type="text" name="pharmacy_name" class="form-control" value="<?php echo $pharmacy['pharmacy_name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" required><?php echo $pharmacy['address']; ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo $pharmacy['phone']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">License Number</label>
                            <input type="text" name="license_number" class="form-control" value="<?php echo $pharmacy['license_number']; ?>" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_pharmacy" class="btn btn-primary">Update Pharmacy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.querySelectorAll('.delete-pharmacy-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the pharmacy, its medicines, and all related records!',
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