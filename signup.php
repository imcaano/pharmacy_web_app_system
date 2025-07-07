<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];
    $metamask_address = $_POST['metamask_address'];
    $user_hash = $_POST['user_hash'] ?? '';
    $tx_hash = $_POST['tx_hash'] ?? '';

    try {
        $stmt = $conn->prepare("INSERT INTO users (email, password, user_type, metamask_address, user_hash, tx_hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$email, $password, $user_type, $metamask_address, $user_hash, $tx_hash]);
        
        // Redirect to login page
        header('Location: login.php');
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            $error = "Email or MetaMask address already exists";
        } else {
            $error = "An error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - PharmaWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/ethers@5.7.2/dist/ethers.umd.min.js"></script>
    <script src="assets/js/blockchain.js"></script>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e6f0f0 0%, #b8dada 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .signup-container {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 8px 32px rgba(124,58,237,0.10);
            max-width: 600px;
            width: 100%;
            margin: 2rem auto;
            display: flex;
            flex-direction: row;
            overflow: hidden;
            padding: 0;
        }
        .signup-visual {
            background: linear-gradient(135deg, #0b6e6e 0%, #0b6e6e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 180px;
            min-height: 220px;
            padding: 0;
        }
        .signup-visual i {
            color: #fff;
            font-size: 3rem;
            text-shadow: 0 2px 12px rgba(124,58,237,0.10);
        }
        .signup-form-section {
            flex: 1;
            padding: 1.8rem 1.8rem 1.5rem 1.8rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .signup-header {
            text-align: center;
            margin-bottom: 1rem;
        }
        .signup-header h2 {
            font-weight: 800;
            color: #22223b;
            margin-bottom: 0.3rem;
            font-size: 1.3rem;
        }
        .signup-header p {
            color: #6c6a7c;
            font-size: 0.95rem;
        }
        .form-label {
            font-weight: 600;
            color: #7c3aed;
            font-size: 0.95rem;
        }
        .form-control {
            border-radius: 0.7rem;
            padding: 0.6rem 0.8rem;
            font-size: 0.95rem;
            margin-bottom: 0.8rem;
        }
        .btn-signup {
            border-radius: 1rem;
            padding: 0.6rem;
            background: linear-gradient(90deg, #0b6e6e 0%, #0b6e6e 100%);
            border: none;
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            box-shadow: 0 2px 8px rgba(11, 110, 110, 0.2);
            margin-top: 0.3rem;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-signup:hover {
            background: linear-gradient(90deg, #0b6e6e 0%, #0b6e6e 100%);
            box-shadow: 0 4px 16px rgba(11, 110, 110, 0.3);
        }
        .metamask-btn {
            background: #0b6e6e;
            color: white;
            border: none;
            border-radius: 1rem;
            padding: 0.6rem;
            width: 100%;
            margin-bottom: 0.8rem;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 1px 4px rgba(11, 110, 110, 0.2);
            transition: all 0.3s ease;
        }
        .metamask-btn:hover {
            background: #0b6e6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 110, 110, 0.3);
        }
        .metamask-btn i {
            margin-right: 0.4rem;
        }
        .user-type-option {
            border: 2px solid #e9ecef;
            border-radius: 0.6rem;
            padding: 0.6rem;
            margin-bottom: 0.6rem;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        .user-type-option:hover {
            border-color: #0b6e6e;
        }
        .user-type-option.selected {
            border-color: #0b6e6e;
            background-color: #e6f0f0;
        }
        .user-type-option i {
            font-size: 1.1rem;
            color: #0b6e6e;
            margin-bottom: 0.2rem;
        }
        .alert {
            border-radius: 0.6rem;
            font-size: 0.95rem;
        }
        @media (max-width: 700px) {
            .signup-container { flex-direction: column; max-width: 98vw; }
            .signup-visual { width: 100%; min-height: 80px; justify-content: center; padding: 1rem 0; }
            .signup-form-section { padding: 1rem; }
        }
        @media (max-width: 576px) {
            .signup-header h2 { font-size: 1.1rem; }
            .signup-visual i { font-size: 1.7rem; }
            .btn-signup, .metamask-btn { font-size: 0.9rem; padding: 0.5rem; }
            .form-control { font-size: 0.9rem; padding: 0.5rem; }
            .user-type-option { padding: 0.5rem; }
            .user-type-option i { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="signup-container mx-auto">
        <div class="signup-visual">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="signup-form-section">
            <div class="signup-header">
                <h2>Create Account</h2>
                <p class="text-muted">Join our healthcare platform</p>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="" id="signupForm">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" id="email" required>
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
                <label class="form-label">Select Account Type</label>
                <div class="row mb-2">
                    <div class="col-6">
                        <div class="user-type-option text-center" onclick="selectUserType('customer', event)">
                            <i class="fas fa-user"></i>
                            <h5 style="font-size:1rem; font-weight:600; color:#22223b; margin:0.2rem 0;">Customer</h5>
                            <input type="radio" name="user_type" value="customer" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="user-type-option text-center" onclick="selectUserType('pharmacy', event)">
                            <i class="fas fa-clinic-medical"></i>
                            <h5 style="font-size:1rem; font-weight:600; color:#22223b; margin:0.2rem 0;">Pharmacy</h5>
                            <input type="radio" name="user_type" value="pharmacy" required>
                        </div>
                    </div>
                </div>
                <label class="form-label">MetaMask Address</label>
                <input type="text" class="form-control" name="metamask_address" id="metamaskAddress" readonly>
                <button type="button" class="metamask-btn" onclick="setMetaMaskAddress()">
                    <i class="fab fa-ethereum"></i> Connect MetaMask
                </button>
                
                <!-- Hidden fields for blockchain data -->
                <input type="hidden" name="user_hash" id="userHash">
                <input type="hidden" name="tx_hash" id="txHash">
                
                <button type="submit" class="btn btn-signup w-100" id="signupBtn">Sign Up</button>
            </form>
            <div class="text-center mt-3">
                <p style="font-size:0.95rem;">Already have an account? <a href="login.php" style="color:#0b6e6e; font-weight:600;">Login</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function selectUserType(type, event) {
            document.querySelectorAll('.user-type-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            // Select the radio button inside the clicked card
            event.currentTarget.querySelector('input[type=radio]').checked = true;
        }
        window.selectUserType = selectUserType;

        window.setMetaMaskAddress = async function() {
            try {
                const { signer } = await window.connectMetaMask();
                const account = await signer.getAddress();
                document.getElementById('metamaskAddress').value = account;
            } catch (error) {
                alert('MetaMask connection failed: ' + error.message);
            }
        }

        document.getElementById('signupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const metamaskAddress = document.getElementById('metamaskAddress').value;
            if (!metamaskAddress) {
                alert('Please connect MetaMask first.');
                return;
            }
            const signupBtn = document.getElementById('signupBtn');
            signupBtn.disabled = true;
            signupBtn.textContent = 'Processing...';
            signupBtn.classList.add('disabled');
            try {
                console.log('Starting blockchain registration for email:', email);
                console.log('MetaMask address:', metamaskAddress);
                const result = await window.registerUserOnChain(email);
                console.log('Blockchain registration successful:', result);
                document.getElementById('userHash').value = result.userHash;
                document.getElementById('txHash').value = result.txHash;
                console.log('Form data prepared, submitting...');
                // Show a success message
                alert('Registration successful! Redirecting to login...');
                this.submit();
            } catch (error) {
                console.error('Blockchain registration failed:', error);
                console.error('Error details:', {
                    message: error.message,
                    code: error.code,
                    stack: error.stack
                });
                let errorMessage = 'Failed to register on blockchain. ';
                if (error.message && error.message.includes('MetaMask')) {
                    errorMessage += 'Please check MetaMask connection.';
                } else if (error.message && error.message.includes('network')) {
                    errorMessage += 'Please check your network connection.';
                } else if (error.message && error.message.includes('contract')) {
                    errorMessage += 'Contract not found. Please check deployment.';
                } else if (error.message && error.message.includes('reverted')) {
                    errorMessage += 'Transaction failed or was reverted.';
                } else {
                    errorMessage += 'Error: ' + error.message;
                }
                alert(errorMessage);
                signupBtn.disabled = false;
                signupBtn.textContent = 'Sign Up';
                signupBtn.classList.remove('disabled');
            }
        });
    });
    </script>
</body>
</html> 