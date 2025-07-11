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

// Handle cart updates
if (isset($_POST['update_cart'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = $_POST['quantity'];
    
    // Check if quantity is valid
    if ($quantity > 0) {
        // Get current cart item and medicine details
        $stmt = $conn->prepare("
            SELECT c.*, m.stock_quantity, m.name 
            FROM cart c 
            JOIN medicines m ON c.medicine_id = m.id 
            WHERE c.id = ? AND c.customer_id = ?
        ");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        $cart_item = $stmt->fetch();
        
        if ($cart_item) {
            // Check if requested quantity doesn't exceed available stock
            if ($quantity <= $cart_item['stock_quantity']) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
                $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
                header('Location: cart.php?success=1');
                exit();
            } else {
                $error = "Not enough stock available for " . $cart_item['name'] . ". Only " . $cart_item['stock_quantity'] . " units left.";
            }
        } else {
            $error = "Cart item not found.";
        }
    } else {
        // Remove item if quantity is 0
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        header('Location: cart.php?success=1');
        exit();
    }
}

// Handle remove from cart
if (isset($_POST['remove_from_cart'])) {
    $cart_id = $_POST['cart_id'];
    
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    
    header('Location: cart.php?success=1');
    exit();
}

// Get cart items with medicine details
$stmt = $conn->prepare("
    SELECT c.*, m.name, m.price, m.stock_quantity, m.requires_prescription, p.pharmacy_name
    FROM cart c 
    JOIN medicines m ON c.medicine_id = m.id 
    JOIN pharmacies p ON m.pharmacy_id = p.id
    WHERE c.customer_id = ?
    ORDER BY c.id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();
// Filter out any cart items where pharmacy_id is missing (should not happen, but for robustness)
$cart_items = array_filter($cart_items, function($item) {
    return !empty($item['pharmacy_name']);
});

// Calculate total
$total = 0;
$requires_prescription = false;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
    if ($item['requires_prescription']) {
        $requires_prescription = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - PharmaWeb</title>
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

        .cart-item i {
            color: #0b6e6e;
        }

        .cart-item-name {
            color: #0b6e6e;
        }

        .cart-total {
            color: #0b6e6e;
            font-weight: 600;
        }

        .checkout-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }

        .quantity-btn {
            color: #0b6e6e;
            border: 1px solid #0b6e6e;
        }

        .quantity-btn:hover {
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
        <nav class="mt-4">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="medicines.php" class="nav-link">
                <i class="fas fa-pills me-2"></i> Browse Medicines
            </a>
            <a href="cart.php" class="nav-link active">
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
            <a href="../logout.php" class="nav-link" style="background-color: #0b6e6e; color: white;">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Shopping Cart</h2>
            <div class="user-info">
                <span class="me-3">Welcome, <?php echo $user['email']; ?></span>
                <i class="fas fa-user-circle fa-2x"></i>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>Cart updated successfully!
            </div>
        <?php endif; ?>

        <div class="cart-card">
            <?php if (empty($cart_items)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Browse our medicines and add items to your cart</p>
                    <a href="medicines.php" class="btn btn-primary">
                        <i class="fas fa-pills me-2"></i>Browse Medicines
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1"><?php echo $item['name']; ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-clinic-medical me-2"></i><?php echo $item['pharmacy_name']; ?>
                                </p>
                                <?php if ($item['requires_prescription']): ?>
                                    <span class="prescription-required mt-2 d-inline-block">
                                        <i class="fas fa-file-medical me-2"></i>Prescription Required
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <p class="mb-0">
                                    <i class="fas fa-dollar-sign me-2"></i><?php echo number_format($item['price'], 2); ?>
                                </p>
                            </div>
                            <div class="col-md-2">
                                <form method="POST" class="d-flex align-items-center">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" class="form-control quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="0" max="<?php echo $item['stock_quantity']; ?>" required>
                                    <button type="submit" name="update_cart" class="btn btn-link">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                                <?php if ($item['stock_quantity'] <= 5): ?>
                                    <small class="text-warning d-block mt-1">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Only <?php echo $item['stock_quantity']; ?> left in stock
                                    </small>
                                <?php endif; ?>
                                <?php if ($item['quantity'] >= $item['stock_quantity']): ?>
                                    <small class="text-danger d-block mt-1">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        Maximum available quantity
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2 text-end">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="remove_from_cart" class="btn btn-link text-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="total-card">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-0">Total Amount</h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <h3 class="mb-0">$<?php echo number_format($total, 2); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <a href="medicines.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                    </a>
                    <?php if ($requires_prescription): ?>
                        <form id="prescriptionForm" enctype="multipart/form-data" class="d-inline">
                            <label class="form-label">Upload Prescription (PDF, JPG, PNG):</label>
                            <input type="file" name="prescription_file" id="prescriptionFile" accept=".jpg,.jpeg,.png,.pdf" required class="form-control mb-2" style="display:inline-block;width:auto;">
                        </form>
                    <?php endif; ?>
                    <div class="mb-2">
                        <strong>Total:</strong> $<?php echo number_format($total, 2); ?>
                        <span id="ethAmount" class="ms-3"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Choose Payment Method:</label><br>
                        <button class="btn btn-primary me-2" type="button" onclick="payAndCreateOrder()">
                            <i class="fab fa-ethereum me-2"></i>Pay with MetaMask
                        </button>
                        <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#hormuudModal">
                            <i class="fas fa-mobile-alt me-2"></i> Pay with Hormuud USSD
                        </button>
                    </div>
                    <!-- Hormuud USSD Modal -->
                    <div class="modal fade" id="hormuudModal" tabindex="-1">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-mobile-alt me-2"></i> Pay with Hormuud USSD</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <form id="hormuudForm" onsubmit="event.preventDefault(); placeHormuudOrder();">
                              <div class="mb-3">
                                <label for="hormuudPhone" class="form-label">Hormuud Phone Number</label>
                                <input type="text" class="form-control" id="hormuudPhone" name="hormuudPhone" placeholder="e.g. 615XXXXXX" maxlength="9" pattern="6[0-9]{8}" required>
                                <div class="form-text">Enter your Hormuud mobile number</div>
                              </div>
                              <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Payment Instructions:</h6>
                                <ol class="mb-0">
                                  <li>Your order will be placed as <b>pending</b> with order ID.</li>
                                  <li>Dial <b>*000#</b> on your Hormuud line.</li>
                                  <li>Select <b>1. Pay for Order</b></li>
                                  <li>Enter your order ID (e.g., <b>ORDER123</b>)</li>
                                  <li>Confirm the payment amount.</li>
                                  <li>Your order will be automatically updated once payment is confirmed.</li>
                                </ol>
                              </div>
                              <div class="alert alert-warning">
                                <small><i class="fas fa-exclamation-triangle me-1"></i>Make sure you have sufficient balance in your Hormuud account.</small>
                              </div>
                              <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-mobile-alt me-2"></i> Place Order & Pay with Hormuud USSD
                              </button>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div id="paymentResult" class="mt-2"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/metamask.js"></script>
    <script>
    let ethRate = 0;
    let ethAmount = 0;
    const usdTotal = <?php echo json_encode($total); ?>;

    // Add stock validation for quantity inputs
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInputs = document.querySelectorAll('input[name="quantity"]');
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.getAttribute('max'));
                const value = parseInt(this.value);
                
                if (value > max) {
                    alert('Cannot exceed available stock of ' + max + ' units.');
                    this.value = max;
                } else if (value < 0) {
                    alert('Quantity cannot be negative.');
                    this.value = 0;
                }
            });
        });
    });

    // Fetch ETH/USD rate
    fetch('https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=usd')
        .then(res => res.json())
        .then(data => {
            ethRate = data.ethereum.usd;
            ethAmount = (usdTotal / ethRate).toFixed(6);
            document.getElementById('ethAmount').innerHTML = '(~' + ethAmount + ' ETH)';
        });

    async function payAndCreateOrder() {
        if (<?php echo $requires_prescription ? 'true' : 'false'; ?>) {
            const fileInput = document.getElementById('prescriptionFile');
            if (!fileInput.files.length) {
                alert('Please upload a prescription file.');
                return;
            }
        }
        if (!ethAmount || ethAmount <= 0) {
            alert('ETH/USD rate not loaded. Please wait and try again.');
            return;
        }
        document.getElementById('paymentResult').innerHTML = 'Waiting for MetaMask...';
        const toAddress = '0x19523a25be5533a3080B07859580e62294235523';
        const result = await window.sendPayment(toAddress, ethAmount);
        if (result.txHash) {
            // If prescription required, upload it first
            let prescriptionId = null;
            if (<?php echo $requires_prescription ? 'true' : 'false'; ?>) {
                const formData = new FormData(document.getElementById('prescriptionForm'));
                const uploadResp = await fetch('upload_prescription_ajax.php', { method: 'POST', body: formData });
                const uploadData = await uploadResp.json();
                if (!uploadData.success) {
                    document.getElementById('paymentResult').innerHTML = '<span class="text-danger">Prescription upload failed: ' + (uploadData.error || 'Unknown error') + '</span>';
                    return;
                }
                prescriptionId = uploadData.prescription_id;
            }
            // After payment and prescription upload, send order details and txHash to PHP via AJAX
            const response = await fetch('create_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    txHash: result.txHash,
                    amount: usdTotal,
                    cart: <?php echo json_encode($cart_items); ?>,
                    prescription_id: prescriptionId
                })
            });
            const data = await response.json();
            if (data.success) {
                document.getElementById('paymentResult').innerHTML = '<span class="text-success">Order placed! TxHash: ' + result.txHash.substring(0, 12) + '...</span>';
                setTimeout(() => { window.location.href = 'orders.php?success=1'; }, 1500);
            } else {
                document.getElementById('paymentResult').innerHTML = '<span class="text-danger">Order failed: ' + (data.error || 'Unknown error') + '</span>';
            }
        } else {
            document.getElementById('paymentResult').innerHTML = '<span class="text-danger">Payment failed: ' + (result.error || 'Unknown error') + '</span>';
        }
    }

    function showHormuudInstructions() {
        // Deprecated: now handled by modal
    }
    async function placeHormuudOrder() {
        // Optionally handle prescription upload first
        let prescriptionId = null;
        if (<?php echo $requires_prescription ? 'true' : 'false'; ?>) {
            const fileInput = document.getElementById('prescriptionFile');
            if (!fileInput.files.length) {
                alert('Please upload a prescription file.');
                return;
            }
            const formData = new FormData(document.getElementById('prescriptionForm'));
            const uploadResp = await fetch('upload_prescription_ajax.php', { method: 'POST', body: formData });
            const uploadData = await uploadResp.json();
            if (!uploadData.success) {
                document.getElementById('paymentResult').innerHTML = '<span class="text-danger">Prescription upload failed: ' + (uploadData.error || 'Unknown error') + '</span>';
                return;
            }
            prescriptionId = uploadData.prescription_id;
        }
        // Get phone number
        const phone = document.getElementById('hormuudPhone').value;
        if (!/^6[0-9]{8}$/.test(phone)) {
            alert('Please enter a valid Hormuud phone number.');
            return;
        }
        // Place order as pending with Hormuud
        const response = await fetch('create_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                payment_method: 'hormuud',
                amount: <?php echo json_encode($total); ?>,
                cart: <?php echo json_encode($cart_items); ?>,
                prescription_id: prescriptionId,
                phone: phone
            })
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('paymentResult').innerHTML = '<span class="text-success">Order placed as pending. Please dial <b>*000#</b> on your Hormuud line or check your phone for a USSD prompt to complete payment.</span>';
            var hormuudModal = bootstrap.Modal.getInstance(document.getElementById('hormuudModal'));
            if (hormuudModal) hormuudModal.hide();
            setTimeout(() => { window.location.href = 'orders.php?success=1'; }, 4000);
        } else {
            document.getElementById('paymentResult').innerHTML = '<span class="text-danger">Order failed: ' + (data.error || 'Unknown error') + '</span>';
        }
    }
    </script>
</body>
</html> 