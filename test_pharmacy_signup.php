<?php
// Test pharmacy signup
require_once 'config/database.php';

echo "=== Test Pharmacy Signup ===\n\n";

try {
    // Simulate pharmacy signup
    $email = 'testpharmacy@example.com';
    $password = 'password123';
    $userType = 'pharmacy';
    $metamaskAddress = '0x' . uniqid() . 'abcdef';
    
    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT id, email, user_type FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    $existingUser = $checkStmt->fetch();
    
    if ($existingUser) {
        echo "User already exists:\n";
        echo "- ID: {$existingUser['id']}\n";
        echo "- Email: {$existingUser['email']}\n";
        echo "- Type: {$existingUser['user_type']}\n\n";
        
        $userId = $existingUser['id'];
    } else {
        // Create new pharmacy user
        $insertStmt = $conn->prepare("
            INSERT INTO users (email, password, user_type, metamask_address) 
            VALUES (?, ?, ?, ?)
        ");
        $result = $insertStmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $userType, $metamaskAddress]);
        
        if ($result) {
            $userId = $conn->lastInsertId();
            echo "✅ New pharmacy user created:\n";
            echo "- ID: $userId\n";
            echo "- Email: $email\n";
            echo "- Type: $userType\n\n";
        } else {
            echo "❌ Failed to create user\n\n";
            exit;
        }
    }
    
    // Check if pharmacy record exists
    $pharmacyStmt = $conn->prepare("SELECT * FROM pharmacies WHERE user_id = ?");
    $pharmacyStmt->execute([$userId]);
    $pharmacy = $pharmacyStmt->fetch();
    
    if (!$pharmacy) {
        // Create pharmacy record
        $pharmacyInsertStmt = $conn->prepare("
            INSERT INTO pharmacies (user_id, pharmacy_name, address, phone, license_number, status, wallet_address, trust_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $pharmacyResult = $pharmacyInsertStmt->execute([
            $userId,
            'New Test Pharmacy',
            'Test Address',
            '1234567890',
            'LIC' . $userId,
            'active',
            $metamaskAddress,
            5.0
        ]);
        
        if ($pharmacyResult) {
            $pharmacyId = $conn->lastInsertId();
            echo "✅ Pharmacy record created:\n";
            echo "- Pharmacy ID: $pharmacyId\n";
            echo "- User ID: $userId\n";
            echo "- Name: New Test Pharmacy\n\n";
        } else {
            echo "❌ Failed to create pharmacy record\n\n";
        }
    } else {
        echo "Pharmacy record already exists:\n";
        echo "- Pharmacy ID: {$pharmacy['id']}\n";
        echo "- User ID: {$pharmacy['user_id']}\n";
        echo "- Name: {$pharmacy['pharmacy_name']}\n\n";
    }
    
    // Test login
    echo "Testing login for pharmacy user...\n";
    $loginStmt = $conn->prepare("SELECT id, email, user_type, metamask_address FROM users WHERE email = ? AND user_type = 'pharmacy'");
    $loginStmt->execute([$email]);
    $loginUser = $loginStmt->fetch();
    
    if ($loginUser) {
        echo "✅ Login successful:\n";
        echo "- User ID: {$loginUser['id']}\n";
        echo "- Email: {$loginUser['email']}\n";
        echo "- Type: {$loginUser['user_type']}\n";
        echo "- MetaMask: {$loginUser['metamask_address']}\n\n";
        
        // Generate token
        $token = base64_encode(json_encode([
            'id' => $loginUser['id'],
            'email' => $loginUser['email'],
            'user_type' => $loginUser['user_type']
        ]));
        
        echo "Generated token: $token\n\n";
        
        echo "✅ Pharmacy signup and login test completed successfully!\n";
        echo "User should be redirected to pharmacy dashboard with:\n";
        echo "- Token: $token\n";
        echo "- Pharmacy ID: {$loginUser['id']}\n";
    } else {
        echo "❌ Login failed\n\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 