<?php
// Test medicine creation
$url = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php';

// Test POST (create medicine for pharmacy_id = 11)
$postData = json_encode([
    'name' => 'Test Medicine for Pharmacy 11',
    'description' => 'Test description',
    'price' => 15.50,
    'stock_quantity' => 50,
    'pharmacy_id' => 11
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

echo "Testing POST (create medicine for pharmacy_id = 11):\n";
$response = file_get_contents($url, false, $context);
echo "Response: $response\n\n";

// Check if medicine was created
echo "Checking medicines for pharmacy_id = 11:\n";
$medicinesUrl = 'http://192.168.18.12/pharmacy_web_app_system/api/medicines.php?pharmacy_id=11';
$medicinesResponse = file_get_contents($medicinesUrl);
echo "Response: $medicinesResponse\n\n";

// Check all medicines in database
require_once 'config/database.php';
$medicines = $conn->query("SELECT * FROM medicines ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "Latest 5 medicines in database:\n";
foreach ($medicines as $medicine) {
    echo "- ID: {$medicine['id']}, Name: {$medicine['name']}, Pharmacy ID: {$medicine['pharmacy_id']}, Price: {$medicine['price']}, Stock: {$medicine['stock_quantity']}\n";
}
?> 