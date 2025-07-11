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
        .cart-container, .payment-section {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  padding: 2rem;
  margin-bottom: 2rem;
}
.cart-item-card {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding: 1rem 0;
}
.medicine-img { width: 60px; height: 60px; border-radius: 8px; margin-right: 1rem; }
.item-details { flex: 1; display: flex; flex-wrap: wrap; align-items: center; }
.item-title { font-weight: 600; }
.item-qty { display: flex; align-items: center; }
.qty-btn { background: #eee; border: none; width: 32px; height: 32px; }
.remove-btn { background: none; border: none; color: #d9534f; margin-left: 1rem; }
.cart-summary { border-top: 1px solid #eee; padding-top: 1rem; }
.summary-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
.summary-row.total { font-weight: bold; font-size: 1.2rem; }
.payment-options { display: flex; gap: 2rem; margin-bottom: 1rem; }
.payment-option { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.pay-logo { height: 32px; }
.waafipay-fields { margin-bottom: 1rem; }
#paymentResult { margin-top: 1rem; }
@media (max-width: 600px) {
  .cart-container, .payment-section { padding: 1rem; }
  .cart-item-card { flex-direction: column; align-items: flex-start; }
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

        <div class="cart-container">
            <h2>Your Cart</h2>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item-card">
                    <div class="item-details">
                        <div class="item-title" style="font-weight:700;font-size:1.2rem;letter-spacing:0.5px;line-height:1.3;margin-bottom:2px;"> 
                            <?php echo htmlspecialchars($item['name']); ?>
                        </div>
                        <div class="item-pharmacy" style="display:flex;align-items:center;color:#218c74;font-weight:400;font-size:0.98rem;margin-bottom:8px;margin-left:2px;">
                            <i class="fas fa-clinic-medical me-1" style="color:#218c74;font-size:1rem;"></i>
                            <span style="font-size:0.98rem;opacity:0.85;"> <?php echo htmlspecialchars($item['pharmacy_name']); ?> </span>
                        </div>
                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                        <div class="item-qty">
                            <form method="POST" style="display:inline-flex;align-items:center;">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="update_cart" class="qty-btn" onclick="this.form.quantity.value=Math.max(1,parseInt(this.form.quantity.value)-1);">-</button>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" style="width:40px;text-align:center;">
                                <button type="submit" name="update_cart" class="qty-btn" onclick="this.form.quantity.value=Math.min(<?php echo $item['stock_quantity']; ?>,parseInt(this.form.quantity.value)+1);">+</button>
                            </form>
                        </div>
                        <div class="item-subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="remove_from_cart" class="remove-btn"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    </div>
                <?php endforeach; ?>
                          </div>
            <div class="cart-summary">
                <div class="summary-row"><span>Subtotal</span><span>$<?php echo number_format($total, 2); ?></span></div>
                <div class="summary-row total"><span>Total</span><span>$<?php echo number_format($total, 2); ?></span></div>
                <a href="medicines.php" class="btn btn-outline-primary mt-3">Continue Shopping</a>
                          </div>
                        </div>
        <div class="payment-section">
            <h3>Choose Payment Method</h3>
            <div class="payment-options">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="waafipay">
                    <img src="../assets/img/waafi.jpg" alt="WaafiPay" class="pay-logo"> WaafiPay
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="blockchain" checked>
                    <img src="../assets/img/metamask.png" alt="MetaMask" class="pay-logo"> MetaMask
                </label>
                      </div>
            <div class="waafipay-fields" style="display:none;">
                <label for="waafiPhone">WaafiPay Account/Phone</label>
                <input type="text" id="waafiPhone" class="form-control" placeholder="e.g. 25261XXXXXXX">
                    </div>
            <button class="btn btn-success w-100 mt-3" id="payNowBtn">Pay Now</button>
            <div id="paymentResult"></div>
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
    async function placeWaafiPayOrder() {
        const phone = document.getElementById('waafiPhone').value;
        if (!/^2526[0-9]{8}$/.test(phone)) {
            alert('Please enter a valid WaafiPay phone/account number.');
            return;
        }
        // Send AJAX to waafipay_payment.php
        const response = await fetch('waafipay_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                phone: phone,
                amount: <?php echo json_encode($total); ?>,
                cart: <?php echo json_encode($cart_items); ?>
            })
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('paymentResult').innerHTML = '<span class="text-success">WaafiPay payment successful! Order placed.</span>';
            setTimeout(() => { window.location.href = 'orders.php?success=1'; }, 2000);
        } else {
            let msg = data.error;
            if (msg && msg.includes('RCS_USER_REJECTED')) {
                msg = 'Payment cancelled by customer.';
            }
            document.getElementById('paymentResult').innerHTML = '<span class="text-danger">WaafiPay payment failed: ' + (msg || 'Unknown error') + '</span>';
        }
        var waafiModal = bootstrap.Modal.getInstance(document.getElementById('waafipayModal'));
        if (waafiModal) waafiModal.hide();
    }

    function payWithSelectedMethod() {
        const selected = document.querySelector('input[name="payment_method"]:checked').value;
        if (selected === 'waafipay') {
            var waafiModal = new bootstrap.Modal(document.getElementById('waafipayModal'));
            waafiModal.show();
        } else {
            payAndCreateOrder();
        }
    }
    // Show/hide WaafiPay fields
    const waafiRadio = document.querySelector('input[value="waafipay"]');
    const metaRadio = document.querySelector('input[value="blockchain"]');
    const waafiFields = document.querySelector('.waafipay-fields');
    waafiRadio.addEventListener('change', () => { if(waafiRadio.checked) waafiFields.style.display='block'; });
    metaRadio.addEventListener('change', () => { if(metaRadio.checked) waafiFields.style.display='none'; });
    // Payment button logic
    const payBtn = document.getElementById('payNowBtn');
    payBtn.onclick = async function() {
        if (waafiRadio.checked) {
            const phone = document.getElementById('waafiPhone').value;
            if (!/^2526[0-9]{8}$/.test(phone)) {
                document.getElementById('paymentResult').innerHTML = '<span class="text-danger">Enter a valid WaafiPay phone/account number.</span>';
                return;
            }
            payBtn.disabled = true; payBtn.innerHTML = 'Processing...';
            const response = await fetch('waafipay_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    phone: phone,
                    amount: <?php echo json_encode($total); ?>,
                    cart: <?php echo json_encode($cart_items); ?>
                })
            });
            const data = await response.json();
            payBtn.disabled = false; payBtn.innerHTML = 'Pay Now';
            if (data.success) {
                document.getElementById('paymentResult').innerHTML = '<span class="text-success">WaafiPay payment successful! Order placed.</span>';
                setTimeout(() => { window.location.href = 'orders.php?success=1'; }, 2000);
            } else {
                let msg = data.error;
                if (msg && msg.includes('RCS_USER_REJECTED')) {
                    msg = 'Payment cancelled by customer.';
                }
                document.getElementById('paymentResult').innerHTML = '<span class="text-danger">WaafiPay payment failed: ' + (msg || 'Unknown error') + '</span>';
            }
        } else {
            // MetaMask logic (call your existing payAndCreateOrder or similar)
            payBtn.disabled = true; payBtn.innerHTML = 'Processing...';
            await payAndCreateOrder();
            payBtn.disabled = false; payBtn.innerHTML = 'Pay Now';
        }
    };
    </script>
</body>
</html> 