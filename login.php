<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $metamask_address = $_POST['metamask_address'];

    if (empty($metamask_address)) {
        $error = "MetaMask connection is required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password']) && strtolower($user['metamask_address']) === strtolower($metamask_address)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['metamask_address'] = $user['metamask_address'];
            
            // Redirect based on user type
            switch($user['user_type']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'pharmacy':
                    header('Location: pharmacy/dashboard.php');
                    break;
                case 'customer':
                    header('Location: customer/dashboard.php');
                    break;
            }
            exit();
        } else {
            $error = "Invalid credentials or MetaMask address does not match.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PharmaWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e6f0f0 0%, #b8dada 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 8px 32px rgba(11, 110, 110, 0.1);
            max-width: 600px;
            width: 100%;
            margin: 2rem auto;
            display: flex;
            flex-direction: row;
            overflow: hidden;
            padding: 0;
        }
        .login-visual {
            background: linear-gradient(135deg, #0b6e6e 0%, #0b6e6e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 180px;
            min-height: 220px;
            padding: 0;
        }
        .login-visual i {
            color: #fff;
            font-size: 3rem;
            text-shadow: 0 2px 12px rgba(11, 110, 110, 0.1);
        }
        .login-form-section {
            flex: 1;
            padding: 1.8rem 1.8rem 1.5rem 1.8rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-header {
            text-align: center;
            margin-bottom: 1rem;
        }
        .login-header h2 {
            font-weight: 800;
            color: #22223b;
            margin-bottom: 0.3rem;
            font-size: 1.3rem;
        }
        .login-header p {
            color: #6c6a7c;
            font-size: 0.95rem;
        }
        .form-label {
            font-weight: 600;
            color: #0b6e6e;
            font-size: 0.95rem;
        }
        .form-control {
            border-radius: 0.7rem;
            padding: 0.6rem 0.8rem;
            font-size: 0.95rem;
            margin-bottom: 0.8rem;
        }
        .btn-login {
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
        .btn-login:hover {
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
        }
        .metamask-btn i {
            margin-right: 0.4rem;
        }
        .alert {
            border-radius: 0.6rem;
            font-size: 0.95rem;
        }
        @media (max-width: 700px) {
            .login-container { flex-direction: column; max-width: 98vw; }
            .login-visual { width: 100%; min-height: 80px; justify-content: center; padding: 1rem 0; }
            .login-form-section { padding: 1rem; }
        }
        @media (max-width: 576px) {
            .login-header h2 { font-size: 1.1rem; }
            .login-visual i { font-size: 1.7rem; }
            .btn-login, .metamask-btn { font-size: 0.9rem; padding: 0.5rem; }
            .form-control { font-size: 0.9rem; padding: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="login-container mx-auto">
        <div class="login-visual">
            <i class="fas fa-prescription-bottle-medical"></i>
        </div>
        <div class="login-form-section">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p class="text-muted">Please login to your account</p>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" required>
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
                <label class="form-label">MetaMask Address</label>
                <input type="text" class="form-control" name="metamask_address" id="metamaskAddress" readonly>
                <button type="button" class="metamask-btn" onclick="connectMetaMask()">
                    <i class="fab fa-ethereum"></i> Connect MetaMask
                </button>
                <button type="submit" class="btn btn-login w-100">Login</button>
            </form>
            <div class="text-center mt-3">
                <p style="font-size:0.95rem;">Don't have an account? <a href="signup.php" style="color:#0b6e6e; font-weight:600;">Sign Up</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function connectMetaMask() {
            if (typeof window.ethereum !== 'undefined') {
                try {
                    // Request account access
                    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                    const account = accounts[0];
                    document.getElementById('metamaskAddress').value = account;
                } catch (error) {
                    console.error('Error connecting to MetaMask:', error);
                    alert('Error connecting to MetaMask. Please make sure MetaMask is installed and unlocked.');
                }
            } else {
                alert('MetaMask is not installed. Please install MetaMask to use this feature.');
            }
        }
    </script>
</body>
</html> 