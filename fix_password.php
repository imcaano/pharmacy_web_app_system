<?php
require_once 'config/database.php';

echo "=== Fix Password for Login ===\n\n";

try {
    // Set password for user ID 11
    $password = 'password123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = 11");
    $result = $updateStmt->execute([$hashedPassword]);
    
    if ($result) {
        echo "✅ Password updated successfully!\n";
        echo "Login credentials:\n";
        echo "- Email: iamcaano2@gmail.com\n";
        echo "- Password: password123\n\n";
        
        // Test login
        echo "Testing login...\n";
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
        
        echo "✅ Login should now work!\n";
    } else {
        echo "❌ Failed to update password\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 