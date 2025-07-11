<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    echo '<div style="background:yellow;color:black;padding:10px;">DEBUG: Delete POST received. medicine_id=' . htmlspecialchars($_POST['medicine_id'] ?? 'NULL') . '</div>';
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
        
        // Delete from prescription orders
        $stmt = $conn->prepare("DELETE FROM prescription_orders WHERE medicine_id = ?");
        $stmt->execute([$medicine_id]);
        
        // Finally delete the medicine
        $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$medicine_id]);
    
        // Commit transaction
        $conn->commit();
        echo '<div style="background:lime;color:black;padding:10px;">DEBUG: Transaction committed. Medicine deleted.</div>';
        
        // If we reach here, deletion was successful
        if (!headers_sent()) {
            header('Location: medicines.php');
            exit();
        }
        
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        echo '<div style="background:red;color:white;padding:10px;">DEBUG: Transaction rolled back. Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
        .expert-medicine-card {
            background: linear-gradient(135deg, #e0f7fa 0%, #f5fafd 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(11, 110, 110, 0.10), 0 1.5px 4px rgba(0,0,0,0.04);
            border: none;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
            margin-bottom: 2rem;
        }
        .expert-medicine-card:hover {
            box-shadow: 0 8px 32px rgba(11, 110, 110, 0.18), 0 3px 8px rgba(0,0,0,0.08);
            transform: translateY(-2px) scale(1.01);
            background: linear-gradient(135deg, #b2ebf2 0%, #e0f7fa 100%);
        }
        .expert-medicine-card .badge {
            font-size: 1rem;
            padding: 0.5em 1em;
            border-radius: 12px;
        }
        .expert-medicine-card .fw-bold {
            word-break: break-all;
            white-space: normal;
            font-size: 1.1rem;
            display: block;
        }
        .admin-medicine-card {
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
.admin-medicine-header {
  display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;
}
.admin-medicine-title { font-weight: 700; font-size: 1.1rem; letter-spacing: 0.5px; }
.admin-medicine-category {
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 8px;
  color: #fff;
  background: #218c74;
  font-size: 0.98rem;
  text-transform: capitalize;
}
.admin-medicine-info { color: #555; font-size: 0.97rem; margin-bottom: 0.5rem; }
.admin-medicine-meta { color: #888; font-size: 0.95rem; margin-bottom: 0.5rem; }
.admin-medicine-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }
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
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download me-2"></i>Export
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
                    <div class="admin-medicine-card">
                        <div class="admin-medicine-header">
                            <div class="admin-medicine-title"><?php echo htmlspecialchars($medicine['name']); ?></div>
                            <span class="admin-medicine-category"><?php echo htmlspecialchars($medicine['category']); ?></span>
                        </div>
                        <div class="admin-medicine-info">
                            <i class="fas fa-clinic-medical me-1"></i>Pharmacy: <b><?php echo htmlspecialchars($medicine['pharmacy_name']); ?></b><br>
                            <i class="fas fa-dollar-sign me-1"></i>Price: $<?php echo number_format($medicine['price'], 2); ?><br>
                            <i class="fas fa-box me-1"></i>Stock: <?php echo $medicine['stock_quantity']; ?><br>
                            <i class="fas fa-calendar-alt me-1"></i>Expiry: <?php echo htmlspecialchars($medicine['expiry_date'] ?? ''); ?>
                        </div>
                        <div class="admin-medicine-meta">
                            <i class="fas fa-globe me-1"></i>Origin: <?php echo htmlspecialchars($medicine['country_of_origin'] ?? ''); ?><br>
                            <i class="fas fa-industry me-1"></i>Manufacturer: <?php echo htmlspecialchars($medicine['manufacturer'] ?? ''); ?>
                        </div>
                        <div class="admin-medicine-actions">
                            <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewMedicineModal<?php echo $medicine['id']; ?>">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                            <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMedicineModal<?php echo $medicine['id']; ?>">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <form method="POST" class="d-inline delete-medicine-form" style="display:inline;">
                                <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                                <button type="submit" name="delete_medicine" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i>Delete</button>
                            </form>
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

    <!-- View Details Modal for each medicine -->
    <?php foreach ($medicines as $medicine): ?>
    <div class="modal fade" id="viewMedicineModal<?php echo $medicine['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Medicine Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($medicine['name']); ?></p>
                    <p><strong>Pharmacy:</strong> <?php echo htmlspecialchars($medicine['pharmacy_name']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($medicine['category']); ?></p>
                    <p><strong>Manufacturer:</strong> <?php echo htmlspecialchars($medicine['manufacturer']); ?></p>
                    <p><strong>Country of Origin:</strong> <?php echo htmlspecialchars($medicine['country_of_origin']); ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($medicine['price'], 2); ?></p>
                    <p><strong>Stock Quantity:</strong> <?php echo $medicine['stock_quantity']; ?></p>
                    <p><strong>Expiry Date:</strong> <?php echo htmlspecialchars($medicine['expiry_date'] ?? ''); ?></p>
                    <p><strong>Status:</strong> <?php echo $medicine['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?></p>
                    <p><strong>Requires Prescription:</strong> <?php echo $medicine['requires_prescription'] ? 'Yes' : 'No'; ?></p>
                    <p><strong>Batch Source Info:</strong> <?php echo htmlspecialchars($medicine['batch_source_info'] ?? ''); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                    <h5 class="modal-title">Export Medicines</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm" action="export_medicines.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select name="format" class="form-select" required>
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php
                                $categories = $conn->query("SELECT DISTINCT category FROM medicines ORDER BY category")->fetchAll();
                                foreach ($categories as $cat) {
                                    echo "<option value='" . htmlspecialchars($cat['category']) . "'>" . htmlspecialchars($cat['category']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pharmacy</label>
                            <select name="pharmacy_id" class="form-select">
                                <option value="">All Pharmacies</option>
                                <?php foreach ($pharmacies as $pharmacy): ?>
                                    <option value="<?php echo $pharmacy['id']; ?>">
                                        <?php echo htmlspecialchars($pharmacy['pharmacy_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Status</label>
                            <select name="stock_status" class="form-select">
                                <option value="">All</option>
                                <option value="in_stock">In Stock (>10)</option>
                                <option value="low_stock">Low Stock (1-10)</option>
                                <option value="out_of_stock">Out of Stock (0)</option>
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
    console.log('JavaScript loaded. Found ' + document.querySelectorAll('.delete-medicine-form').length + ' delete forms');
    
    document.querySelectorAll('.delete-medicine-form').forEach((form, index) => {
        console.log('Setting up delete form ' + index);
        form.addEventListener('submit', function(e) {
            console.log('Delete form submitted!');
            e.preventDefault();
            const _form = this;
            const medicineId = _form.querySelector('input[name="medicine_id"]').value;
            console.log('Attempting to delete medicine ID: ' + medicineId);
            
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
                    console.log('User confirmed deletion. Submitting form...');
                    _form.submit();
                } else {
                    console.log('User cancelled deletion');
                }
            });
        });
    });
    </script>
</body>
</html> 