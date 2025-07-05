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

// Handle medicine search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$pharmacy = isset($_GET['pharmacy']) ? $_GET['pharmacy'] : '';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';

// Build query
$query = "
    SELECT m.*, p.pharmacy_name, p.address as pharmacy_address
    FROM medicines m 
    JOIN pharmacies p ON m.pharmacy_id = p.id 
    WHERE p.status = 'active' AND m.stock_quantity > 0
";

$params = [];

if ($search) {
    $query .= " AND (m.name LIKE ? OR m.manufacturer LIKE ? OR m.category LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($category) {
    $query .= " AND m.category = ?";
    $params[] = $category;
}

if ($pharmacy) {
    $query .= " AND m.pharmacy_id = ?";
    $params[] = $pharmacy;
}

if ($min_price) {
    $query .= " AND m.price >= ?";
    $params[] = $min_price;
}

if ($max_price) {
    $query .= " AND m.price <= ?";
    $params[] = $max_price;
}

$query .= " ORDER BY m.name ASC";

// Get medicines
$stmt = $conn->prepare($query);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM medicines ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Get pharmacies for filter
$pharmacies = $conn->query("SELECT id, pharmacy_name FROM pharmacies WHERE status = 'active' ORDER BY pharmacy_name")->fetchAll();

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    
    // Check if medicine exists and has enough stock
    $stmt = $conn->prepare("SELECT * FROM medicines WHERE id = ? AND stock_quantity >= ?");
    $stmt->execute([$medicine_id, $quantity]);
    $medicine = $stmt->fetch();
    
    if ($medicine) {
        // Check if medicine is already in cart
        $stmt = $conn->prepare("SELECT * FROM cart WHERE customer_id = ? AND medicine_id = ?");
        $stmt->execute([$_SESSION['user_id'], $medicine_id]);
        $cart_item = $stmt->fetch();
        
        if ($cart_item) {
            // Check if total quantity (existing + new) doesn't exceed stock
            $total_quantity = $cart_item['quantity'] + $quantity;
            if ($total_quantity <= $medicine['stock_quantity']) {
                // Update quantity
                $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE customer_id = ? AND medicine_id = ?");
                $stmt->execute([$quantity, $_SESSION['user_id'], $medicine_id]);
                header('Location: medicines.php?success=1');
                exit();
            } else {
                $error = "Cannot add " . $quantity . " more units. Only " . ($medicine['stock_quantity'] - $cart_item['quantity']) . " additional units available.";
            }
        } else {
            // Add to cart
            $stmt = $conn->prepare("INSERT INTO cart (customer_id, medicine_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $medicine_id, $quantity]);
            header('Location: medicines.php?success=1');
            exit();
        }
    } else {
        $error = "Medicine not available or insufficient stock.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Medicines - PharmaWeb</title>
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

        .add-to-cart-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }

        .medicine-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .medicine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
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
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
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
        
        .category-filter {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.1);
            color: var(--primary);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .category-filter:hover,
        .category-filter.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pharmacy-name {
            color: var(--primary);
            font-weight: 500;
        }
        
        .medicine-details {
            padding: 1.5rem;
            }
        
        .medicine-category {
            color: #6b7280;
            font-size: 0.875rem;
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
            <a href="medicines.php" class="nav-link active">
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
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Item added to cart successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search medicines..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo ($cat === ($category ?? '')) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="pharmacy">
                        <option value="">All Pharmacies</option>
                        <?php foreach ($pharmacies as $pharm): ?>
                            <option value="<?php echo $pharm['id']; ?>" <?php echo ($pharm['id'] == ($pharmacy ?? '')) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pharm['pharmacy_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <div class="row g-4">
            <?php foreach ($medicines as $medicine): ?>
                <div class="col-md-4">
                    <div class="medicine-card">
                        <img src="<?php echo $medicine['image_url'] ?: '../assets/images/medicine-placeholder.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($medicine['name']); ?>" 
                             class="medicine-image w-100">
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
                        <div class="medicine-details">
                            <h5 class="medicine-name"><?php echo htmlspecialchars($medicine['name']); ?></h5>
                            <p class="medicine-category">
                                <i class="fas fa-tag me-1"></i>
                                <?php echo htmlspecialchars($medicine['category']); ?>
                            </p>
                            <p class="pharmacy-name">
                                <i class="fas fa-clinic-medical me-1"></i>
                                <?php echo htmlspecialchars($medicine['pharmacy_name']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="medicine-price">$<?php echo number_format($medicine['price'], 2); ?></span>
                                <small class="text-muted"><?php echo $medicine['stock_quantity']; ?> units left</small>
                            </div>
                            <?php if ($medicine['stock_quantity'] > 0): ?>
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $medicine['id']; ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                            <?php else: ?>
                                <button class="add-to-cart-btn" disabled style="background: #e5e7eb; cursor: not-allowed;">
                                    <i class="fas fa-times me-2"></i>Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function addToCart(medicineId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'medicine_id=' + medicineId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Added to Cart!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message
                    });
                }
            });
        }
    </script>
</body>
</html> 