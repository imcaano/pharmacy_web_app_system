<?php
// Test new pharmacy signup process
require_once 'config/database.php';

echo "=== Test New Pharmacy Signup ===\n\n";

try {
    // Simulate new pharmacy signup
    $email = 'newpharmacy@example.com';
    $password = 'password123';
    $userType = 'pharmacy';
    $metamaskAddress = '0x' . uniqid() . 'abcdef';
    
    echo "1. Creating new pharmacy user...\n";
    
    // Create user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (email, password, user_type, metamask_address) VALUES (?, ?, ?, ?)');
    $result = $stmt->execute([$email, $hashed, $userType, $metamaskAddress]);
    
    if ($result) {
        $userId = $conn->lastInsertId();
        echo "✅ User created with ID: $userId\n";
        
        // Create pharmacy record
        $pharmacyStmt = $conn->prepare('
            INSERT INTO pharmacies (user_id, pharmacy_name, address, phone, license_number, status, wallet_address, trust_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $pharmacyResult = $pharmacyStmt->execute([
            $userId,
            'My New Pharmacy',
            'Enter your address',
            'Enter your phone',
            'LIC' . $userId,
            'active',
            $metamaskAddress,
            5.0
        ]);
        
        if ($pharmacyResult) {
            $pharmacyId = $conn->lastInsertId();
            echo "✅ Pharmacy record created with ID: $pharmacyId\n\n";
            
            // Test login
            echo "2. Testing login...\n";
            $loginData = json_encode([
                'email' => $email,
                'password' => $password
            ]);
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => $loginData
                ]
            ]);
            
            $loginUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/login.php';
            $loginResponse = file_get_contents($loginUrl, false, $context);
            echo "Login Response: $loginResponse\n\n";
            
            // Test pharmacy profile
            echo "3. Testing pharmacy profile...\n";
            $profileData = json_encode(['pharmacy_id' => $userId]);
            
            $profileContext = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => $profileData
                ]
            ]);
            
            $profileUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/pharmacy_profile.php';
            $profileResponse = file_get_contents($profileUrl, false, $profileContext);
            echo "Profile Response: $profileResponse\n\n";
            
            echo "✅ New pharmacy signup test completed!\n";
            echo "User should see their own pharmacy data, not 'shaafi' pharmacy.\n";
        } else {
            echo "❌ Failed to create pharmacy record\n";
        }
    } else {
        echo "❌ Failed to create user\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 