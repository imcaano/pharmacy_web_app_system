<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle medicine deletion
$delete_error = '';
if (isset($_POST['delete_medicine'])) {
    $medicine_id = $_POST['medicine_id'];
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Delete from cart
        $stmt = $conn->prepare("DELETE FROM cart WHERE medicine_id = ?");
        $stmt->execute([$medicine_id]);
        
        // Delete from order items
        $stmt = $conn->prepare("DELETE FROM order_items WHERE medicine_id = ?");
        $stmt->execute([$medicine_id]);
        
        // Finally delete the medicine
    $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->execute([$medicine_id]);
    
        // Commit transaction
        $conn->commit();
        
        // If we reach here, deletion was successful
        if (!headers_sent()) {
    header('Location: medicines.php');
    exit();
        }
        
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        $delete_error = "Error deleting medicine: " . $e->getMessage();
        error_log("Failed to delete medicine $medicine_id: " . $e->getMessage());
    }
}

// Handle medicine update
if (isset($_POST['update_medicine'])) {
    $medicine_id = $_POST['medicine_id'];
    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $country_of_origin = $_POST['country_of_origin'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $expiry_date = $_POST['expiry_date'];
    $requires_prescription = isset($_POST['requires_prescription']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE medicines SET 
        name = ?, 
        manufacturer = ?, 
        country_of_origin = ?, 
        category = ?, 
        price = ?, 
        stock_quantity = ?, 
        expiry_date = ?, 
        requires_prescription = ? 
        WHERE id = ?");
    $stmt->execute([
        $name, 
        $manufacturer, 
        $country_of_origin, 
        $category, 
        $price, 
        $stock_quantity, 
        $expiry_date, 
        $requires_prescription, 
        $medicine_id
    ]);
    
    header('Location: medicines.php');
    exit();
}

// Get all medicines with pharmacy details
$medicines = $conn->query("
    SELECT m.*, p.pharmacy_name 
    FROM medicines m 
    JOIN pharmacies p ON m.pharmacy_id = p.id 
    ORDER BY m.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Medicines - PharmaWeb Admin</title>
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

        .medicine-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(124, 58, 237, 0.1);
            position: relative;
            overflow: hidden;
        }

        .medicine-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-dark), var(--primary-light));
            transition: height 0.3s ease;
        }

        .medicine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.15);
            border-color: var(--primary-light);
        }

        .medicine-card:hover::before {
            height: 6px;
        }

        .medicine-image {
            height: 200px;
            object-fit: cover;
            border-radius: 12px 12px 0 0;
            background: #f8f9fa;
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .in-stock {
            background: #dcfce7;
            color: #166534;
        }

        .low-stock {
            background: #fff7ed;
            color: #9a3412;
        }

        .out-of-stock {
            background: #fee2e2;
            color: #991b1b;
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

        .medicine-info {
            padding: 1.5rem;
        }

        .medicine-info p {
            color: #6b7280;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .medicine-card:hover .medicine-info p {
            color: #374151;
        }

        .medicine-info i {
            width: 20px;
            color: #0b6e6e;
            margin-right: 0.5rem;
        }

        .medicine-name {
            color: #0b6e6e;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .medicine-card:hover .medicine-name {
            color: var(--primary);
        }

        .medicine-price {
            color: #0b6e6e;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .medicine-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .medicine-meta small {
            color: #6b7280;
        }

        .add-medicine-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .add-medicine-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }

        .search-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .search-box .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .search-box .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
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
            <a href="medicines.php" class="nav-link active">
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
            <h2>Manage Medicines</h2>
            <div>
                <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
                <button class="btn add-medicine-btn" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                    <i class="fas fa-plus me-2"></i>Add New Medicine
                </button>
            </div>
        </div>

        <!-- Medicines Grid -->
        <div class="row g-4">
            <?php foreach ($medicines as $medicine): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="medicine-card">
                        <div class="medicine-image">
                            <!-- No image is displayed for medicines -->
                        </div>
                        <?php
                        $stockBadgeClass = '';
                        $stockText = '';
                        if ($medicine['stock_quantity'] > 10) {
                            $stockBadgeClass = 'in-stock';
                            $stockText = 'In Stock';
                        } elseif ($medicine['stock_quantity'] > 0) {
                            $stockBadgeClass = 'low-stock';
                            $stockText = 'Low Stock';
                        } else {
                            $stockBadgeClass = 'out-of-stock';
                            $stockText = 'Out of Stock';
                        }
                        ?>
                        <span class="stock-badge <?php echo $stockBadgeClass; ?>">
                            <?php echo $stockText; ?>
                            </span>
                        <div class="medicine-info">
                            <h5 class="medicine-name"><?php echo htmlspecialchars($medicine['name']); ?></h5>
                            <p>
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($medicine['category']); ?>
                            </p>
                            <p>
                                <i class="fas fa-clinic-medical"></i>
                                <?php echo htmlspecialchars($medicine['pharmacy_name']); ?>
                            </p>
                            <p>
                                <i class="fas fa-industry"></i>
                                <?php echo htmlspecialchars($medicine['manufacturer']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="medicine-price">$<?php echo number_format($medicine['price'], 2); ?></span>
                                <small class="text-muted"><?php echo $medicine['stock_quantity']; ?> units left</small>
                        </div>
                            <div class="medicine-meta">
                                <small>
                                    <i class="fas fa-clock"></i>
                                    Added: <?php echo date('M d, Y', strtotime($medicine['created_at'])); ?>
                            </small>
                            <div class="btn-group">
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMedicineModal<?php echo $medicine['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                    <form method="POST" class="d-inline delete-medicine-form">
                                        <input type="hidden" name="delete_medicine" value="1">
                                    <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Medicine Modal -->
    <div class="modal fade" id="addMedicineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addMedicineForm">
                        <div class="mb-3">
                            <label class="form-label">Medicine Name</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pharmacy</label>
                            <select class="form-select" required>
                                <?php
                                $pharmacies = $conn->query("SELECT * FROM pharmacies WHERE status = 'active'")->fetchAll();
                                foreach ($pharmacies as $pharmacy) {
                                    echo "<option value='{$pharmacy['id']}'>{$pharmacy['pharmacy_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Manufacturer</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Country of Origin</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="requiresPrescription">
                                <label class="form-check-label" for="requiresPrescription">Requires Prescription</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batch Source Info</label>
                            <input type="text" class="form-control" name="batch_source_info">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addMedicineForm" class="btn btn-primary">Add Medicine</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Medicines</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="mb-3">
                            <label class="form-label">Pharmacy</label>
                            <select class="form-select">
                                <option value="">All Pharmacies</option>
                                <?php foreach ($pharmacies as $pharmacy): ?>
                                    <option value="<?php echo $pharmacy['id']; ?>">
                                        <?php echo $pharmacy['pharmacy_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="number" class="form-control" placeholder="Min">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" placeholder="Max">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Status</label>
                            <select class="form-select">
                                <option value="">All</option>
                                <option value="low">Low Stock (< 10)</option>
                                <option value="medium">Medium Stock (10-50)</option>
                                <option value="high">High Stock (> 50)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="prescriptionOnly">
                                <label class="form-check-label" for="prescriptionOnly">Prescription Required Only</label>
                            </div>
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

    <!-- Edit Medicine Modals -->
    <?php foreach ($medicines as $medicine): ?>
    <div class="modal fade" id="editMedicineModal<?php echo $medicine['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $medicine['name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Manufacturer</label>
                            <input type="text" name="manufacturer" class="form-control" value="<?php echo $medicine['manufacturer']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Country of Origin</label>
                            <input type="text" name="country_of_origin" class="form-control" value="<?php echo $medicine['country_of_origin']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="<?php echo $medicine['category']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $medicine['price']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" value="<?php echo $medicine['stock_quantity']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" value="<?php echo $medicine['expiry_date']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="requires_prescription" class="form-check-input" id="requiresPrescription<?php echo $medicine['id']; ?>" <?php echo $medicine['requires_prescription'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="requiresPrescription<?php echo $medicine['id']; ?>">Requires Prescription</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batch Source Info</label>
                            <input type="text" class="form-control" name="batch_source_info" value="<?php echo htmlspecialchars($medicine['batch_source_info'] ?? ''); ?>">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_medicine" class="btn btn-primary">Update Medicine</button>
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
    document.querySelectorAll('.delete-medicine-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the medicine and all related records!',
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