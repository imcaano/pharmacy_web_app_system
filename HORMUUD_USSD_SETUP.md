# Hormuud USSD Payment Integration Setup Guide

## Overview

This guide explains how to set up and use the Hormuud USSD payment integration for the Pharmacy Web App System. The integration allows customers to pay for their orders using Hormuud's USSD service.

## Prerequisites

1. **Hormuud USSD API Access**: You need to have an active Hormuud USSD API account
2. **USSD Short Code**: A registered USSD short code from Hormuud
3. **API Token**: Your unique API token from Hormuud
4. **Web Server**: A publicly accessible web server (HTTPS recommended for production)

## Database Setup

The system requires the following database columns in the `orders` table:

```sql
-- Add payment_method column (if not exists)
ALTER TABLE `orders` ADD COLUMN `payment_method` ENUM('blockchain', 'hormuud', 'evc_plus', 'cash') DEFAULT 'blockchain' AFTER `payment_status`;

-- Add phone_number column for payment tracking
ALTER TABLE `orders` ADD COLUMN `phone_number` VARCHAR(20) DEFAULT NULL AFTER `payment_method`;

-- Add ussd_session_id for Hormuud USSD tracking
ALTER TABLE `orders` ADD COLUMN `ussd_session_id` VARCHAR(50) DEFAULT NULL AFTER `phone_number`;
```

## Configuration

### 1. Update Hormuud Configuration

Edit `config/hormuud_config.php` and update the following values:

```php
// Replace with your actual Hormuud USSD API token
define('HORMUUD_USSD_TOKEN', 'your_actual_token_here');

// Replace with your USSD short code
define('HORMUUD_USSD_SHORTCODE', '000');

// Update with your actual domain
define('HORMUUD_USSD_ENDPOINT', 'https://your-domain.com/pharmacy_web_app_system/hormuud_ussd_payment.php');

// Add allowed phone numbers for testing
define('HORMUUD_ALLOWED_ORIGINS', [
    '615123456', // Add your test phone numbers
    '615123457',
    '615123458'
]);
```

### 2. Hormuud USSD Endpoint Setup

The USSD endpoint (`hormuud_ussd_payment.php`) must be accessible via HTTPS and should be configured in your Hormuud USSD dashboard.

**Endpoint URL**: `https://your-domain.com/pharmacy_web_app_system/hormuud_ussd_payment.php`

## How It Works

### 1. Customer Flow

1. **Add Items to Cart**: Customer adds medicines to their cart
2. **Choose Payment Method**: Customer selects "Pay with Hormuud USSD"
3. **Enter Phone Number**: Customer enters their Hormuud phone number
4. **Order Creation**: System creates a pending order with payment_method = 'hormuud'
5. **USSD Payment**: Customer dials *000# and follows the USSD prompts
6. **Payment Confirmation**: Order status is automatically updated to 'completed'

### 2. USSD Flow

1. **Dial *000#**: Customer initiates USSD session
2. **Select Option 1**: "Pay for Order"
3. **Enter Order ID**: Customer enters their order ID (e.g., ORDER123)
4. **Confirm Payment**: Customer confirms the payment amount
5. **Payment Complete**: Order status is updated automatically

### 3. System Flow

1. **Order Creation**: `create_order.php` creates order with payment_method = 'hormuud'
2. **USSD Request**: Hormuud sends USSD request to `hormuud_ussd_payment.php`
3. **Order Validation**: System validates order exists and is pending
4. **Payment Processing**: System processes payment confirmation
5. **Status Update**: Order status is updated to 'completed'

## Testing

### 1. Create Test Order

1. Add items to cart in the customer interface
2. Select "Pay with Hormuud USSD"
3. Enter a test phone number
4. Complete the order creation

### 2. Test USSD Flow

Use the test script to simulate USSD requests:

```bash
php test_hormuud_ussd.php
```

### 3. Manual Testing

1. Create an order with payment_method = 'hormuud'
2. Note the order ID
3. Simulate USSD requests using curl or Postman:

```bash
curl -X POST https://your-domain.com/pharmacy_web_app_system/hormuud_ussd_payment.php \
  -H "Content-Type: application/json" \
  -d '{
    "token": "your_token_here",
    "sessionid": "12345",
    "origin": "615123456",
    "ussdcontent": "*000#",
    "ussdstate": "begin",
    "shortcode": "000"
  }'
```

## Security Considerations

1. **Token Validation**: Always validate the Hormuud token
2. **Phone Number Validation**: Validate phone number format
3. **Rate Limiting**: Implement rate limiting to prevent abuse
4. **HTTPS**: Use HTTPS in production for secure communication
5. **Logging**: Enable logging for debugging and monitoring

## Error Handling

The system handles various error scenarios:

- **Invalid Token**: Returns authentication error
- **Invalid Order**: Returns order not found message
- **System Errors**: Returns generic error message
- **Invalid Phone**: Returns phone number format error

## Logging

USSD requests are logged to `logs/hormuud_ussd.log` for debugging purposes. Enable/disable logging in the configuration file.

## Production Deployment

1. **Update Configuration**: Set production values in `config/hormuud_config.php`
2. **Enable HTTPS**: Ensure your server supports HTTPS
3. **Update Hormuud Dashboard**: Configure the USSD endpoint in Hormuud dashboard
4. **Test Thoroughly**: Test the complete flow before going live
5. **Monitor Logs**: Monitor USSD logs for any issues

## Troubleshooting

### Common Issues

1. **"Column not found" Error**: Run the database setup SQL
2. **"Invalid token" Error**: Check token configuration
3. **"Order not found" Error**: Verify order exists and payment_method = 'hormuud'
4. **USSD not responding**: Check endpoint accessibility and server logs

### Debug Steps

1. Check PHP error logs
2. Check USSD request logs
3. Verify database connection
4. Test endpoint accessibility
5. Validate Hormuud configuration

## Support

For issues related to:
- **Hormuud USSD API**: Contact Hormuud support
- **System Integration**: Check this documentation and logs
- **Database Issues**: Verify database setup and permissions

## API Reference

### USSD Request Format

```json
{
  "token": "your_token",
  "requestid": "unique_request_id",
  "sessionid": "session_id",
  "origin": "phone_number",
  "ussdcontent": "user_input",
  "ussdstate": "begin|continue|end",
  "shortcode": "000",
  "issuedon": "timestamp"
}
```

### USSD Response Format

```json
{
  "sessionid": "session_id",
  "shortcode": "000",
  "origin": "phone_number",
  "ussdcontent": "response_message",
  "endreply": "true|false"
}
```

## Files Overview

- `hormuud_ussd_payment.php`: Main USSD endpoint
- `config/hormuud_config.php`: Configuration file
- `customer/cart.php`: Customer cart with Hormuud payment option
- `customer/create_order.php`: Order creation with payment method support
- `test_hormuud_ussd.php`: Test script for USSD simulation
- `database/add_payment_method.sql`: Database setup script 