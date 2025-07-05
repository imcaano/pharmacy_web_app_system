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
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Batch Source</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicines as $medicine): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['category']); ?></td>
                                    <td>$<?php echo number_format($medicine['price'], 2); ?></td>
                                    <td><?php echo $medicine['stock_quantity']; ?></td>
                                    <td>
                                        <?php if ($medicine['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            <small class="text-muted">Batch Source: <?php echo htmlspecialchars($medicine['batch_source_info'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <!-- Edit Button triggers modal -->
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit" data-bs-toggle="modal" data-bs-target="#editMedicineModal<?php echo $medicine['id']; ?>"><i class="fas fa-edit"></i></button>
                                        <!-- Delete Button triggers modal -->
                                        <button class="btn btn-sm btn-outline-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteMedicineModal<?php echo $medicine['id']; ?>"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <!-- Edit Medicine Modal -->
                                <div class="modal fade" id="editMedicineModal<?php echo $medicine['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Medicine</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Name</label>
                                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($medicine['name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Category</label>
                                                        <select class="form-select" name="category" required>
                                                            <option value="Antibiotic" <?php if ($medicine['category'] === 'Antibiotic') echo 'selected'; ?>>Antibiotic</option>
                                                            <option value="Analgesic" <?php if ($medicine['category'] === 'Analgesic') echo 'selected'; ?>>Analgesic</option>
                                                            <option value="Antipyretic" <?php if ($medicine['category'] === 'Antipyretic') echo 'selected'; ?>>Antipyretic</option>
                                                            <option value="Antiseptic" <?php if ($medicine['category'] === 'Antiseptic') echo 'selected'; ?>>Antiseptic</option>
                                                            <option value="Antiviral" <?php if ($medicine['category'] === 'Antiviral') echo 'selected'; ?>>Antiviral</option>
                                                            <option value="Vaccine" <?php if ($medicine['category'] === 'Vaccine') echo 'selected'; ?>>Vaccine</option>
                                                            <option value="Supplement" <?php if ($medicine['category'] === 'Supplement') echo 'selected'; ?>>Supplement</option>
                                                            <option value="Other" <?php if ($medicine['category'] === 'Other') echo 'selected'; ?>>Other</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Price</label>
                                                        <input type="number" class="form-control" name="price" step="0.01" value="<?php echo $medicine['price']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Stock Quantity</label>
                                                        <input type="number" class="form-control" name="stock_quantity" value="<?php echo $medicine['stock_quantity']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select" name="status" required>
                                                            <option value="active" <?php if ($medicine['status'] === 'active') echo 'selected'; ?>>Active</option>
                                                            <option value="inactive" <?php if ($medicine['status'] === 'inactive') echo 'selected'; ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="edit_medicine" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- Delete Medicine Modal -->
                                <div class="modal fade" id="deleteMedicineModal<?php echo $medicine['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Delete Medicine</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                                                    <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($medicine['name']); ?></strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_medicine" class="btn btn-danger">Delete</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 