<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle prescription upload
$upload_error = '';
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
                // Save to DB
                $stmt = $conn->prepare("INSERT INTO prescriptions (customer_id, prescription_file, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $newFileName]);
                header('Location: prescriptions.php?success=1');
                exit();
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

// Fetch prescriptions
$stmt = $conn->prepare("SELECT * FROM prescriptions WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$prescriptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - PharmaWeb</title>
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

        .upload-btn {
            background: #0b6e6e;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.2);
        }

        .prescription-status {
            color: #0b6e6e;
            font-weight: 600;
        }

        .prescription-date {
            color: #0b6e6e;
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
            <a href="dashboard.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo ' active'; ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="medicines.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='medicines.php') echo ' active'; ?>">
                <i class="fas fa-pills me-2"></i> Browse Medicines
            </a>
            <a href="cart.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='cart.php') echo ' active'; ?>">
                <i class="fas fa-shopping-cart me-2"></i> Cart
            </a>
            <a href="orders.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='orders.php') echo ' active'; ?>">
                <i class="fas fa-box me-2"></i> My Orders
            </a>
            <a href="prescriptions.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='prescriptions.php') echo ' active'; ?>">
                <i class="fas fa-file-medical me-2"></i> Prescriptions
            </a>
            <a href="profile.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='profile.php') echo ' active'; ?>">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link logout-btn" style="background:none;color:#fff;font-weight:500;padding:12px 0 12px 18px;text-align:left;display:flex;align-items:center;border-radius:0;font-size:1.1rem;">
                <i class="fas fa-sign-out-alt me-2" style="font-size:1.2rem;"></i> Logout
            </a>
        </nav>
    </div>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Prescriptions</h2>
            <div class="user-info">
                <span class="me-3">Welcome, <?php echo $user['email']; ?></span>
                <i class="fas fa-user-circle fa-2x"></i>
            </div>
        </div>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>Prescription uploaded successfully!
            </div>
        <?php endif; ?>
        <?php if ($upload_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $upload_error; ?>
            </div>
        <?php endif; ?>
        <div class="prescription-card mb-4">
            <h5 class="mb-3"><i class="fas fa-upload me-2"></i>Upload New Prescription</h5>
            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-8">
                    <input type="file" name="prescription_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="col-md-4 text-end">
                    <button type="submit" name="upload_prescription" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload
                    </button>
                </div>
            </form>
        </div>
        <div class="prescription-card">
            <h5 class="mb-3"><i class="fas fa-file-medical me-2"></i>Your Prescriptions</h5>
            <?php if (empty($prescriptions)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-file-medical fa-2x text-muted mb-2"></i>
                    <div class="text-muted">No prescriptions uploaded yet.</div>
                </div>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($prescriptions as $presc): ?>
                        <li class="list-group-item prescription-file d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <?php if (in_array(strtolower(pathinfo($presc['prescription_file'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])): ?>
                                    <img src="../uploads/prescriptions/<?php echo $presc['prescription_file']; ?>" class="prescription-thumb me-3" alt="Prescription Image">
                                <?php else: ?>
                                    <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                <?php endif; ?>
                                <div>
                                    <div><a href="../uploads/prescriptions/<?php echo $presc['prescription_file']; ?>" target="_blank">View File</a></div>
                                    <div class="prescription-date">Uploaded on <?php echo date('M d, Y H:i', strtotime($presc['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <strong>Hash:</strong> <span style="font-family:monospace;"> <?php echo $presc['prescription_hash']; ?> </span>
                                <button class="btn btn-outline-secondary btn-sm ms-2" onclick="verifyHashOnBlockchain('<?php echo $presc['prescription_hash']; ?>', <?php echo $presc['id']; ?>)">
                                    <i class="fas fa-link"></i> Verify on Blockchain
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/metamask.js"></script>
    <script>
    async function verifyHashOnBlockchain(hash, prescriptionId) {
        // 1. Connect MetaMask
        const address = await window.connectWallet();
        if (!address) {
            alert('Please connect MetaMask to continue.');
            return;
        }
        // 2. Fetch ETH/USD rate for $0.10
        let ethRate = 0;
        let ethAmount = 0;
        await fetch('https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=usd')
            .then(res => res.json())
            .then(data => {
                ethRate = data.ethereum.usd;
                ethAmount = (0.10 / ethRate).toFixed(6);
            });
        if (!ethAmount || ethAmount <= 0) {
            alert('ETH/USD rate not loaded. Please try again.');
            return;
        }
        if (!confirm('A $0.10 gas fee (~' + ethAmount + ' ETH) will be paid to verify on blockchain. Continue?')) return;
        // 3. Trigger MetaMask payment
        const toAddress = '0x19523a25be5533a3080B07859580e62294235523';
        const result = await window.sendPayment(toAddress, ethAmount);
        if (!result.txHash) {
            alert('Payment failed: ' + (result.error || 'Unknown error'));
            return;
        }
        // 4. Save TxHash to backend
        const saveResp = await fetch('save_verification_tx.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prescription_id: prescriptionId, tx_hash: result.txHash })
        });
        const saveData = await saveResp.json();
        if (!saveData.success) {
            alert('Failed to save verification transaction: ' + (saveData.error || 'Unknown error'));
            return;
        }
        // 5. Show simulated verification result
        alert('Verifying hash on blockchain: ' + hash + '\n\n(Simulated: Hash found and valid!)\nTxHash: ' + result.txHash);
    }
    </script>
</body>
</html> 