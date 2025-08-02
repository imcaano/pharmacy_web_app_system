<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Debug logging
error_log("Pharmacy Profile API - Method: $method");
error_log("Pharmacy Profile API - Input: " . json_encode($input));

try {
    if ($method === 'POST') {
        // Fetch pharmacy profile
        $pharmacyId = $input['pharmacy_id'] ?? null;
        
        if (!$pharmacyId) {
            echo json_encode([
                'success' => false, 
                'error' => 'Pharmacy ID is required.',
                'debug' => 'No pharmacy_id provided in request'
            ]);
            exit();
        }

        // Get pharmacy data from pharmacies table
        $stmt = $conn->prepare('
            SELECT p.*, u.email, u.metamask_address 
            FROM pharmacies p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ?
        ');
        $stmt->execute([$pharmacyId]);
        $pharmacy = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pharmacy) {
            $pharmacy['id'] = (int)$pharmacy['id'];
            echo json_encode([
                'success' => true, 
                'pharmacy' => $pharmacy,
                'debug' => "Found pharmacy with ID: $pharmacyId"
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'Pharmacy not found.',
                'debug' => "No pharmacy found with ID: $pharmacyId"
            ]);
        }
        exit();
    }

    if ($method === 'PUT') {
        // Update pharmacy profile
        $pharmacyId = $input['pharmacy_id'] ?? null;
        $pharmacyName = $input['pharmacy_name'] ?? null;
        $address = $input['address'] ?? null;
        $phone = $input['phone'] ?? null;
        $email = $input['email'] ?? null;
        $metamaskAddress = $input['metamask_address'] ?? null;

        if (!$pharmacyId) {
            echo json_encode([
                'success' => false, 
                'error' => 'Pharmacy ID is required.',
                'debug' => 'Missing pharmacy_id'
            ]);
            exit();
        }

        try {
            $conn->beginTransaction();

            // Update pharmacies table
            if ($pharmacyName || $address || $phone) {
                $updateFields = [];
                $updateValues = [];
                
                if ($pharmacyName) {
                    $updateFields[] = 'pharmacy_name = ?';
                    $updateValues[] = $pharmacyName;
                }
                if ($address) {
                    $updateFields[] = 'address = ?';
                    $updateValues[] = $address;
                }
                if ($phone) {
                    $updateFields[] = 'phone = ?';
                    $updateValues[] = $phone;
                }
                
                if (!empty($updateFields)) {
                    $updateValues[] = $pharmacyId;
                    $stmt = $conn->prepare('UPDATE pharmacies SET ' . implode(', ', $updateFields) . ' WHERE user_id = ?');
                    $stmt->execute($updateValues);
                }
            }

            // Update users table
            if ($email || $metamaskAddress) {
                $updateFields = [];
                $updateValues = [];
                
                if ($email) {
                    $updateFields[] = 'email = ?';
                    $updateValues[] = $email;
                }
                if ($metamaskAddress) {
                    $updateFields[] = 'metamask_address = ?';
                    $updateValues[] = $metamaskAddress;
                }
                
                if (!empty($updateFields)) {
                    $updateValues[] = $pharmacyId;
                    $stmt = $conn->prepare('UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ?');
                    $stmt->execute($updateValues);
                }
            }

            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pharmacy profile updated successfully.',
                'debug' => "Updated pharmacy ID: $pharmacyId"
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode([
                'success' => false, 
                'error' => 'Failed to update pharmacy profile: ' . $e->getMessage(),
                'debug' => 'Database update failed: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    echo json_encode([
        'success' => false, 
        'error' => 'Method not allowed.',
        'debug' => "Unsupported method: $method"
    ]);

} catch (Exception $e) {
    error_log("Pharmacy Profile API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage(),
        'debug' => 'Exception caught: ' . $e->getMessage()
    ]);
}
?> 