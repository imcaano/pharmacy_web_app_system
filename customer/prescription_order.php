<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle prescription upload for order
$upload_error = '';
$upload_success = '';
if (isset($_POST['upload_prescription'])) {
    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['prescription_file']['tmp_name'];
        $fileName = $_FILES['prescription_file']['name'];
        $fileSize = $_FILES['prescription_file']['size'];
        $fileType = $_FILES['prescription_file']['type'];
        $fileNameCmps = explode('.', $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = uniqid('presc_', true) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/prescriptions/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Save to DB with blockchain integration
                $stmt = $conn->prepare("INSERT INTO prescriptions (customer_id, prescription_file, status, created_at) VALUES (?, ?, 'pending', NOW())");
                $stmt->execute([$_SESSION['user_id'], $newFileName]);
                $prescription_id = $conn->lastInsertId();
                
                // Store prescription ID in session for order creation
                $_SESSION['current_prescription_id'] = $prescription_id;
                $upload_success = 'Prescription uploaded successfully! You can now select medicines.';
            } else {
                $upload_error = 'Error moving the uploaded file.';
            }
        } else {
            $upload_error = 'Invalid file type. Only JPG, PNG, and PDF allowed.';
        }
    } else {
        $upload_error = 'No file uploaded or upload error.';
    }
}

// Handle medicine selection for prescription order
if (isset($_POST['add_medicine_to_order'])) {
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    $prescription_id = $_SESSION['current_prescription_id'] ?? null;
    
    if (!$prescription_id) {
        $error = "Please upload a prescription first.";
    } else {
        // Check if medicine exists and has enough stock
        $stmt = $conn->prepare("SELECT * FROM medicines WHERE id = ? AND stock_quantity >= ?");
        $stmt->execute([$medicine_id, $quantity]);
        $medicine = $stmt->fetch();
        
        if ($medicine) {
            // Add to prescription order (temporary cart for prescription orders)
            $stmt = $conn->prepare("INSERT INTO prescription_orders (prescription_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$prescription_id, $medicine_id, $quantity, $medicine['price']]);
            $success = "Medicine added to prescription order.";
        } else {
            $error = "Medicine not available or insufficient stock.";
        }
    }
}

// Handle prescription order submission
if (isset($_POST['submit_prescription_order'])) {
    $prescription_id = $_SESSION['current_prescription_id'] ?? null;
    
    if (!$prescription_id) {
        $error = "No prescription order to submit.";
    } else {
        // Get prescription order items
        $stmt = $conn->prepare("
            SELECT po.*, m.name, m.pharmacy_id, p.pharmacy_name 
            FROM prescription_orders po 
            JOIN medicines m ON po.medicine_id = m.id 
            JOIN pharmacies p ON m.pharmacy_id = p.id 
            WHERE po.prescription_id = ?
        ");
        $stmt->execute([$prescription_id]);
        $order_items = $stmt->fetchAll();
        
        if (empty($order_items)) {
            $error = "No medicines selected for this prescription order.";
        } else {
            // Group by pharmacy
            $pharmacy_orders = [];
            foreach ($order_items as $item) {
                $pharmacy_id = $item['pharmacy_id'];
                if (!isset($pharmacy_orders[$pharmacy_id])) {
                    $pharmacy_orders[$pharmacy_id] = [
                        'pharmacy_name' => $item['pharmacy_name'],
                        'items' => [],
                        'total' => 0
                    ];
                }
                $pharmacy_orders[$pharmacy_id]['items'][] = $item;
                $pharmacy_orders[$pharmacy_id]['total'] += $item['price'] * $item['quantity'];
            }
            
            // Create orders for each pharmacy
            foreach ($pharmacy_orders as $pharmacy_id => $pharmacy_order) {
                // Create main order
                $stmt = $conn->prepare("
                    INSERT INTO orders (customer_id, pharmacy_id, prescription_id, total_amount, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending_approval', NOW())
                ");
                $stmt->execute([$_SESSION['user_id'], $pharmacy_id, $prescription_id, $pharmacy_order['total']]);
                $order_id = $conn->lastInsertId();
                
                // Add order items
                foreach ($pharmacy_order['items'] as $item) {
                    $stmt = $conn->prepare("
                        INSERT INTO order_items (order_id, medicine_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$order_id, $item['medicine_id'], $item['quantity'], $item['price']]);
                }
            }
            
            // Clear prescription orders and session
            $stmt = $conn->prepare("DELETE FROM prescription_orders WHERE prescription_id = ?");
            $stmt->execute([$prescription_id]);
            unset($_SESSION['current_prescription_id']);
            
            $success = "Prescription order submitted successfully! Pharmacies will review and approve your order.";
        }
    }
}

// Get current prescription order items
$current_prescription_items = [];
if (isset($_SESSION['current_prescription_id'])) {
    $stmt = $conn->prepare("
        SELECT po.*, m.name, m.pharmacy_name 
        FROM prescription_orders po 
        JOIN medicines m ON po.medicine_id = m.id 
        WHERE po.prescription_id = ?
    ");
    $stmt->execute([$_SESSION['current_prescription_id']]);
    $current_prescription_items = $stmt->fetchAll();
}

// Get available medicines
$stmt = $conn->prepare("
    SELECT m.*, p.pharmacy_name 
    FROM medicines m 
    JOIN pharmacies p ON m.pharmacy_id = p.id 
    WHERE m.stock_quantity > 0 
    ORDER BY m.name
");
$stmt->execute();
$medicines = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Order - PharmaWeb</title>
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

        .step-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .step-number {
            background: #0b6e6e;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }

        .medicine-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            margin-bottom: 1rem;
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
                <i class="fas fa-pills me-2"></i> Browse Medicines
            </a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart me-2"></i> Cart
            </a>
            <a href="prescription_order.php" class="nav-link active">
                <i class="fas fa-file-medical me-2"></i> Prescription Order
            </a>
            <a href="orders.php" class="nav-link">
                <i class="fas fa-box me-2"></i> My Orders
            </a>
            <a href="prescriptions.php" class="nav-link">
                <i class="fas fa-file-medical me-2"></i> Prescriptions
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link mt-5">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4">Prescription-Based Order</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($upload_error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $upload_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($upload_success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $upload_success; ?>
            </div>
        <?php endif; ?>

        <!-- Step 1: Upload Prescription -->
        <div class="step-card">
            <div class="d-flex align-items-center mb-3">
                <div class="step-number">1</div>
                <h4 class="mb-0">Upload Prescription</h4>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <input type="file" name="prescription_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="upload_prescription" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Upload Prescription
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (isset($_SESSION['current_prescription_id'])): ?>
            <!-- Step 2: Select Medicines -->
            <div class="step-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="step-number">2</div>
                    <h4 class="mb-0">Select Medicines</h4>
                </div>
                
                <!-- Current Order Items -->
                <?php if (!empty($current_prescription_items)): ?>
                    <div class="mb-4">
                        <h5>Selected Medicines:</h5>
                        <?php foreach ($current_prescription_items as $item): ?>
                            <div class="medicine-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <br>
                                        <small class="text-muted">Pharmacy: <?php echo htmlspecialchars($item['pharmacy_name']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div>Qty: <?php echo $item['quantity']; ?></div>
                                        <div>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <form method="POST" class="mt-3">
                            <button type="submit" name="submit_prescription_order" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i>Submit Prescription Order
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- Available Medicines -->
                <h5>Available Medicines:</h5>
                <div class="row">
                    <?php foreach ($medicines as $medicine): ?>
                        <div class="col-md-6 mb-3">
                            <div class="medicine-card">
                                <h6><?php echo htmlspecialchars($medicine['name']); ?></h6>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-clinic-medical me-1"></i>
                                    <?php echo htmlspecialchars($medicine['pharmacy_name']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-primary fw-bold">$<?php echo number_format($medicine['price'], 2); ?></span>
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $medicine['stock_quantity']; ?>" class="form-control me-2" style="width: 80px;">
                                        <button type="submit" name="add_medicine_to_order" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Add
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 