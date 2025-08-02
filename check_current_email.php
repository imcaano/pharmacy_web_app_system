<?php
require_once 'config/database.php';

echo "=== Check Current Email ===\n\n";

try {
    // Check current user email
    $stmt = $conn->prepare("SELECT id, email, user_type FROM users WHERE id = 11");
    $stmt->execute();
    $user = $stmt->fetch();
    
    echo "Current user data for ID 11:\n";
    echo "- ID: {$user['id']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- Type: {$user['user_type']}\n\n";
    
    // Check pharmacy data
    $stmt = $conn->prepare("SELECT * FROM pharmacies WHERE user_id = 11");
    $stmt->execute();
    $pharmacy = $stmt->fetch();
    
    echo "Pharmacy data for user_id = 11:\n";
    echo "- Pharmacy ID: {$pharmacy['id']}\n";
    echo "- Name: {$pharmacy['pharmacy_name']}\n";
    echo "- Address: {$pharmacy['address']}\n";
    echo "- Phone: {$pharmacy['phone']}\n\n";
    
    // Update email to match login email
    echo "Updating email to match login email...\n";
    $updateStmt = $conn->prepare("UPDATE users SET email = 'iamcaano2@gmail.com' WHERE id = 11");
    $result = $updateStmt->execute();
    
    if ($result) {
        echo "✅ Email updated successfully!\n\n";
    } else {
        echo "❌ Failed to update email\n\n";
    }
    
    // Check updated user data
    $stmt = $conn->prepare("SELECT id, email, user_type FROM users WHERE id = 11");
    $stmt->execute();
    $user = $stmt->fetch();
    
    echo "Updated user data for ID 11:\n";
    echo "- ID: {$user['id']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- Type: {$user['user_type']}\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 