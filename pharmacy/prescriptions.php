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
    SELECT * FROM pharmacies 
    WHERE user_id = " . $_SESSION['user_id']
)->fetch();

// Handle prescription verification
if (isset($_POST['verify_prescription'])) {
    $prescription_id = $_POST['prescription_id'];
    $verification_status = $_POST['verification_status'];
    $verification_notes = $_POST['verification_notes'];
    
    $stmt = $conn->prepare("
        UPDATE prescriptions 
        SET verification_status = ?, verification_notes = ?, verified_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$verification_status, $verification_notes, $prescription_id]);

    // Update related order status if prescription is verified or rejected
    if ($verification_status === 'verified') {
        $conn->prepare("UPDATE orders SET status = 'approved' WHERE prescription_id = ?")->execute([$prescription_id]);
    } elseif ($verification_status === 'rejected') {
        $conn->prepare("UPDATE orders SET status = 'rejected' WHERE prescription_id = ?")->execute([$prescription_id]);
    }
}

// Get all prescriptions with related orders
$prescriptions = $conn->query("
    SELECT p.*, 
           u.email as customer_email,
           o.id as order_id,
           o.status as order_status,
           o.total_amount
    FROM prescriptions p 
    JOIN users u ON p.customer_id = u.id 
    LEFT JOIN orders o ON p.id = o.prescription_id 
    WHERE o.pharmacy_id = " . $pharmacy['id'] . "
    ORDER BY p.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Prescriptions - PharmaWeb</title>
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

        .prescription-card i {
            color: #0b6e6e;
        }

        .prescription-info h5 {
            color: #0b6e6e;
        }

        .prescription-status {
            color: #0b6e6e;
        }

        .prescription-date {
            color: #0b6e6e;
        }

        .verify-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .verify-btn:hover {
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
            <a href="prescriptions.php" class="nav-link active">
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
            <h2 style="color: #0b6e6e;">Manage Prescriptions</h2>
            <div>
                <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Prescriptions Grid -->
        <div class="row g-4">
            <?php if (empty($prescriptions)): ?>
                <div class="col-12">
                    <div class="prescription-card text-center">
                        <h4 class="mb-2 text-muted"><i class="fas fa-file-medical me-2"></i>No prescriptions found</h4>
                        <p class="text-muted">There are currently no prescriptions for your pharmacy.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($prescriptions as $prescription): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="prescription-card p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">Prescription #<?php echo $prescription['id']; ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-user me-2"></i><?php echo $prescription['customer_email']; ?>
                                    </p>
                                </div>
                                <span class="verification-badge verification-<?php echo $prescription['verification_status']; ?>">
                                    <?php echo ucfirst($prescription['verification_status']); ?>
                                </span>
                            </div>
                            <div class="mb-3">
                                <p class="mb-1">
                                    <i class="fas fa-user-md me-2"></i>Dr. <?php echo $prescription['doctor_name']; ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-calendar me-2"></i><?php echo date('M d, Y', strtotime($prescription['created_at'])); ?>
                                </p>
                                <?php if ($prescription['order_id']): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-shopping-cart me-2"></i>Order #<?php echo $prescription['order_id']; ?>
                                        <span class="order-badge order-<?php echo $prescription['order_status']; ?> ms-2">
                                            <?php echo ucfirst($prescription['order_status']); ?>
                                        </span>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-dollar-sign me-2"></i><?php echo number_format($prescription['total_amount'], 2); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewPrescriptionModal<?php echo $prescription['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#verifyPrescriptionModal<?php echo $prescription['id']; ?>">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                                <a href="<?php echo $prescription['prescription_file']; ?>" class="btn btn-sm btn-outline-info" target="_blank">
                                    <i class="fas fa-file-medical me-1"></i>View File
                                </a>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm mt-2" onclick="verifyHashOnBlockchain('<?php echo $prescription['prescription_hash']; ?>')">
                                <i class="fas fa-link"></i> Verify Hash on Blockchain
                            </button>
                        </div>
                    </div>

                    <!-- View Prescription Modal -->
                    <div class="modal fade" id="viewPrescriptionModal<?php echo $prescription['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Prescription Details #<?php echo $prescription['id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6>Customer Information</h6>
                                            <p class="mb-1">Email: <?php echo $prescription['customer_email']; ?></p>
                                            <p class="mb-1">Date: <?php echo date('M d, Y', strtotime($prescription['created_at'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Doctor Information</h6>
                                            <p class="mb-1">Name: Dr. <?php echo $prescription['doctor_name']; ?></p>
                                            <p class="mb-1">License: <?php echo $prescription['doctor_license']; ?></p>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <h6>Prescription Details</h6>
                                        <p class="mb-1"><strong>Diagnosis:</strong> <?php echo $prescription['diagnosis']; ?></p>
                                        <p class="mb-1"><strong>Notes:</strong> <?php echo $prescription['notes']; ?></p>
                                    </div>
                                    <?php if ($prescription['verification_status'] !== 'pending'): ?>
                                        <div class="mb-4">
                                            <h6>Verification Details</h6>
                                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($prescription['verification_status']); ?></p>
                                            <p class="mb-1"><strong>Notes:</strong> <?php echo $prescription['verification_notes']; ?></p>
                                            <p class="mb-1"><strong>Verified At:</strong> <?php echo date('M d, Y H:i', strtotime($prescription['verified_at'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($prescription['order_id']): ?>
                                        <div>
                                            <h6>Related Order</h6>
                                            <p class="mb-1"><strong>Order ID:</strong> #<?php echo $prescription['order_id']; ?></p>
                                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($prescription['order_status']); ?></p>
                                            <p class="mb-1"><strong>Amount:</strong> $<?php echo number_format($prescription['total_amount'], 2); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verify Prescription Modal -->
                    <div class="modal fade" id="verifyPrescriptionModal<?php echo $prescription['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Verify Prescription</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Verification Status</label>
                                            <select name="verification_status" class="form-select" required>
                                                <option value="pending" <?php echo $prescription['verification_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="verified" <?php echo $prescription['verification_status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                                <option value="rejected" <?php echo $prescription['verification_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Verification Notes</label>
                                            <textarea class="form-control" name="verification_notes" rows="3" required><?php echo $prescription['verification_notes']; ?></textarea>
                                        </div>
                                        <button type="submit" name="verify_prescription" class="btn btn-primary">Update Verification</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Prescriptions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="mb-3">
                            <label class="form-label">Verification Status</label>
                            <select class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="verified">Verified</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="date" class="form-control" placeholder="From">
                                </div>
                                <div class="col">
                                    <input type="date" class="form-control" placeholder="To">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Doctor Name</label>
                            <input type="text" class="form-control" placeholder="Search by doctor name">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="filterForm" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Prescriptions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select class="form-select">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="date" class="form-control" placeholder="From">
                                </div>
                                <div class="col">
                                    <input type="date" class="form-control" placeholder="To">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="includeOrders">
                                <label class="form-check-label" for="includeOrders">Include Related Orders</label>
                            </div>
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
    <script>
    function verifyHashOnBlockchain(hash) {
        // TODO: Replace with real blockchain verification logic
        alert('Verifying hash on blockchain: ' + hash + '\n\n(Simulated: Hash found and valid!)');
    }
    </script>
</body>
</html> 