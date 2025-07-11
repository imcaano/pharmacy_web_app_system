<?php
/**
 * Hormuud USSD Configuration
 * 
 * This file contains configuration settings for the Hormuud USSD payment integration.
 * Update these values according to your Hormuud USSD API credentials.
 */

// Hormuud USSD API Configuration
define('HORMUUD_USSD_TOKEN', 'your_hormuud_token_here'); // Replace with your actual token
define('HORMUUD_USSD_SHORTCODE', '000'); // Your USSD short code
define('HORMUUD_USSD_ENDPOINT', 'http://your-domain.com/pharmacy_web_app_system/hormuud_ussd_payment.php');

// USSD Menu Configuration
define('HORMUUD_MENU_OPTIONS', [
    '1' => 'Pay for Order',
    '2' => 'Check Balance',
    '3' => 'Exit'
]);

// Payment Status Configuration
define('HORMUUD_PAYMENT_STATUS', [
    'pending' => 'pending',
    'completed' => 'completed',
    'failed' => 'failed'
]);

// Error Messages
define('HORMUUD_ERROR_MESSAGES', [
    'invalid_token' => 'Invalid token. Access denied.',
    'invalid_order' => 'Order not found or already paid.',
    'system_error' => 'System error. Please try again later.',
    'invalid_phone' => 'Invalid phone number format.',
    'insufficient_balance' => 'Insufficient balance in your account.'
]);

// Success Messages
define('HORMUUD_SUCCESS_MESSAGES', [
    'payment_success' => 'Payment successful! Your order has been confirmed.',
    'order_placed' => 'Order placed as pending. Please complete payment via USSD.',
    'balance_check' => 'Your account balance: $50.00'
]);

// USSD Response Templates
define('HORMUUD_RESPONSE_TEMPLATES', [
    'welcome' => "Welcome to Pharmacy Payment\n\n1. Pay for Order\n2. Check Balance\n3. Exit\n\nEnter your choice:",
    'enter_order' => "Enter Order ID:\n\nFormat: ORDER123\n\nEnter order ID:",
    'order_details' => "Order #%s\nAmount: $%s\n\n1. Confirm Payment\n2. Cancel\n\nEnter your choice:",
    'goodbye' => "Thank you for using our service. Goodbye!",
    'invalid_choice' => "Invalid choice. Please try again.\n\n1. Pay for Order\n2. Check Balance\n3. Exit\n\nEnter your choice:"
]);

// Logging Configuration
define('HORMUUD_LOG_ENABLED', true);
define('HORMUUD_LOG_FILE', 'logs/hormuud_ussd.log');

// Security Configuration
define('HORMUUD_ALLOWED_ORIGINS', [
    '615123456', // Add allowed phone numbers for testing
    '615123457',
    '615123458'
]);

// Rate Limiting (requests per minute per session)
define('HORMUUD_RATE_LIMIT', 10);

// Session Timeout (in seconds)
define('HORMUUD_SESSION_TIMEOUT', 300); // 5 minutes
?> 