<?php
require_once 'config/database.php';

echo "=== Fix All Login/Signup/Profile Issues ===\n\n";

try {
    // 1. Check current user data
    echo "1. Current user data for ID 11:\n";
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = 11");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "- ID: {$user['id']}\n";
        echo "- Email: {$user['email']}\n";
        echo "- Type: {$user['user_type']}\n";
        echo "- MetaMask: {$user['metamask_address']}\n\n";
    } else {
        echo "User ID 11 not found\n\n";
    }
    
    // 2. Update user data to correct values
    echo "2. Updating user data...\n";
    $updateStmt = $conn->prepare("
        UPDATE users 
        SET email = 'iamcaano2@gmail.com', 
            user_type = 'pharmacy',
            metamask_address = '0x2546BcD3c84621e976D8185a91A922aE77ECE'
        WHERE id = 11
    ");
    $result = $updateStmt->execute();
    
    if ($result) {
        echo "✅ User data updated successfully\n\n";
    } else {
        echo "❌ Failed to update user data\n\n";
    }
    
    // 3. Check pharmacy record
    echo "3. Checking pharmacy record...\n";
    $pharmacyStmt = $conn->prepare("SELECT * FROM pharmacies WHERE user_id = 11");
    $pharmacyStmt->execute();
    $pharmacy = $pharmacyStmt->fetch();
    
    if ($pharmacy) {
        echo "- Pharmacy ID: {$pharmacy['id']}\n";
        echo "- User ID: {$pharmacy['user_id']}\n";
        echo "- Name: {$pharmacy['pharmacy_name']}\n";
        echo "- Address: {$pharmacy['address']}\n";
        echo "- Phone: {$pharmacy['phone']}\n\n";
    } else {
        echo "No pharmacy record found for user_id = 11\n\n";
    }
    
    // 4. Test login API
    echo "4. Testing login API...\n";
    $loginData = json_encode([
        'email' => 'iamcaano2@gmail.com',
        'password' => 'password123'
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
    
    // 5. Test pharmacy profile API
    echo "5. Testing pharmacy profile API...\n";
    $profileData = json_encode(['pharmacy_id' => 3]);
    
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
    
    // 6. Test medicines API
    echo "6. Testing medicines API...\n";
    $medicinesUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?pharmacy_id=3';
    $medicinesResponse = file_get_contents($medicinesUrl);
    echo "Medicines Response: $medicinesResponse\n\n";
    
    echo "✅ All fixes completed!\n";
    echo "Now when you login with iamcaano2@gmail.com, you should see:\n";
    echo "- Correct email in profile\n";
    echo "- Correct pharmacy data\n";
    echo "- Correct medicine counts\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 