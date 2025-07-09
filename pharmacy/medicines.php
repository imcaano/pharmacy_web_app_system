<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is pharmacy
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'pharmacy') {
    header('Location: ../login.php');
    exit();
}

// Get pharmacy details
$pharmacy = $conn->query("SELECT * FROM pharmacies WHERE user_id = " . $_SESSION['user_id'])->fetch();

$success = $error = '';

// Handle Add Medicine
if (isset($_POST['add_medicine'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $status = $_POST['status'];
    if ($name && $category && $price >= 0 && $stock_quantity >= 0 && in_array($status, ['active','inactive'])) {
        $stmt = $conn->prepare("INSERT INTO medicines (pharmacy_id, name, category, price, stock_quantity, status) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$pharmacy['id'], $name, $category, $price, $stock_quantity, $status])) {
            $success = 'Medicine added successfully!';
        } else {
            $error = 'Failed to add medicine.';
        }
    } else {
        $error = 'Please fill all fields correctly.';
    }
}

// Handle Edit Medicine
if (isset($_POST['edit_medicine'])) {
    $id = intval($_POST['medicine_id']);
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $status = $_POST['status'];
    if ($id && $name && $category && $price >= 0 && $stock_quantity >= 0 && in_array($status, ['active','inactive'])) {
        $stmt = $conn->prepare("UPDATE medicines SET name=?, category=?, price=?, stock_quantity=?, status=? WHERE id=? AND pharmacy_id=?");
        if ($stmt->execute([$name, $category, $price, $stock_quantity, $status, $id, $pharmacy['id']])) {
            $success = 'Medicine updated successfully!';
        } else {
            $error = 'Failed to update medicine.';
        }
    } else {
        $error = 'Please fill all fields correctly.';
    }
}

// Handle Delete Medicine
if (isset($_POST['delete_medicine'])) {
    $id = intval($_POST['medicine_id']);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM medicines WHERE id=? AND pharmacy_id=?");
        if ($stmt->execute([$id, $pharmacy['id']])) {
            $success = 'Medicine deleted successfully!';
        } else {
            $error = 'Failed to delete medicine.';
        }
    } else {
        $error = 'Invalid medicine ID.';
    }
}

// Get medicines for this pharmacy
$medicines = [];
if ($pharmacy) {
    $medicines = $conn->query("SELECT * FROM medicines WHERE pharmacy_id = " . $pharmacy['id'] . " ORDER BY name ASC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines - PharmaWeb</title>
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

        .medicine-card i {
            color: #0b6e6e;
        }

        .medicine-name {
            color: #0b6e6e;
        }

        .medicine-price {
            color: #0b6e6e;
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

        .edit-btn {
            color: #0b6e6e;
            border: 1px solid #0b6e6e;
        }

        .edit-btn:hover {
            background: #0b6e6e;
            color: #fff;
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
            <a href="medicines.php" class="nav-link active">
                <i class="fas fa-pills me-2"></i> Medicines
            </a>
            <a href="orders.php" class="nav-link">
                <i class="fas fa-shopping-cart me-2"></i> Orders
            </a>
            <a href="prescriptions.php" class="nav-link">
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
            <h2 style="color: #0b6e6e;">Medicines</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                <i class="fas fa-plus me-2"></i>Add Medicine
            </button>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success"> <?php echo $success; ?> </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"> <?php echo $error; ?> </div>
        <?php endif; ?>
        <div class="card p-4">
            <?php if (empty($medicines)): ?>
                <div class="text-center py-5">
                    <h4 class="mb-2 text-muted"><i class="fas fa-pills me-2"></i>No medicines found</h4>
                    <p class="text-muted">There are currently no medicines in your pharmacy. Click "Add Medicine" to get started.</p>
                </div>
            <?php else: ?>
                <!-- Medicines Grid -->
                <div class="row g-4">
                    <?php foreach ($medicines as $medicine): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="expert-medicine-card p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($medicine['name']); ?></h5>
                                    <span class="badge bg-info fs-6"><?php echo htmlspecialchars($medicine['category']); ?></span>
                                </div>
                                <div class="mb-2">
                                    <i class="fas fa-dollar-sign me-2"></i>
                                    $<?php echo number_format($medicine['price'], 2); ?>
                                </div>
                                <div class="mb-2">
                                    <i class="fas fa-box me-2"></i>
                                    Stock: <?php echo $medicine['stock_quantity']; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewMedicineModal<?php echo $medicine['id']; ?>">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Add Medicine Modal -->
    <div class="modal fade" id="addMedicineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Medicine</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" required>
                                <option value="Antibiotic">Antibiotic</option>
                                <option value="Blood Pressure">Blood Pressure</option>
                                <option value="Cardiac">Cardiac</option>
                                <option value="Diabetes">Diabetes</option>
                                <option value="Eye / Ear / Nose">Eye / Ear / Nose</option>
                                <option value="Fungal (Antifungal)">Fungal (Antifungal)</option>
                                <option value="Gastrointestinal">Gastrointestinal</option>
                                <option value="Hormonal">Hormonal</option>
                                <option value="Pain Reliever">Pain Reliever</option>
                                <option value="Vitamins">Vitamins</option>
                                <option value="Analgesic">Analgesic</option>
                                <option value="Antipyretic">Antipyretic</option>
                                <option value="Antiseptic">Antiseptic</option>
                                <option value="Antiviral">Antiviral</option>
                                <option value="Vaccine">Vaccine</option>
                                <option value="Supplement">Supplement</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" name="stock_quantity" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_medicine" class="btn btn-primary">Add Medicine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php foreach ($medicines as $medicine): ?>
        <!-- View Details Modal for each medicine -->
        <div class="modal fade" id="viewMedicineModal<?php echo $medicine['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Medicine Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($medicine['name']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($medicine['category']); ?></p>
                        <p><strong>Price:</strong> $<?php echo number_format($medicine['price'], 2); ?></p>
                        <p><strong>Stock Quantity:</strong> <?php echo $medicine['stock_quantity']; ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($medicine['status']); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 