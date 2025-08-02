import 'dart:convert';
import 'package:http/http.dart' as http;

class PaymentService {
  static const String baseUrl =
      'http://192.168.137.1/pharmacy_web_app_system/customer';

  // Waafi Pay configuration (matching web implementation)
  static const String merchantUid = 'M0913963';
  static const String apiUserId = '1008217';
  static const String apiKey = 'API-u5EgPyUot2qISzI1GOYJ9vUEpT8m';
  static const String waafiEndpoint = 'https://api.waafipay.net/asm';

  static Future<Map<String, dynamic>> createWaafiPayOrder({
    required String phone,
    required double amount,
    required List<Map<String, dynamic>> cart,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/waafipay_payment.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'phone': phone, 'amount': amount, 'cart': cart}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data;
      } else {
        throw Exception(
          'Failed to create payment order: ${response.statusCode}',
        );
      }
    } catch (e) {
      throw Exception('Payment service error: $e');
    }
  }

  static Future<Map<String, dynamic>> checkPaymentStatus({
    required String transactionId,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/check_payment_status.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'transaction_id': transactionId}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data;
      } else {
        throw Exception(
          'Failed to check payment status: ${response.statusCode}',
        );
      }
    } catch (e) {
      throw Exception('Payment status check error: $e');
    }
  }

  static Future<Map<String, dynamic>> processOrderPayment({
    required String token,
    required int orderId,
    required double amount,
    required String customerPhone,
    required String customerName,
    required List<Map<String, dynamic>> cart,
  }) async {
    try {
      // Create payment order using Waafi Pay
      final paymentOrder = await createWaafiPayOrder(
        phone: customerPhone,
        amount: amount,
        cart: cart,
      );

      if (paymentOrder['success'] == true) {
        // Update order with payment information
        final response = await http.post(
          Uri.parse('$baseUrl/create_order.php'),
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $token',
          },
          body: jsonEncode({
            'order_id': orderId,
            'payment_id': paymentOrder['transactionId'] ?? '',
            'transaction_id': paymentOrder['transactionId'] ?? '',
            'amount': amount,
            'status': 'pending',
            'cart': cart,
          }),
        );

        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          return {
            'success': true,
            'transaction_id': paymentOrder['transactionId'] ?? '',
            'order_ref': paymentOrder['orderRef'] ?? '',
            'order_id': orderId,
            'message': 'Payment processed successfully',
          };
        } else {
          throw Exception(
            'Failed to update order payment: ${response.statusCode}',
          );
        }
      } else {
        throw Exception(
          'Payment failed: ${paymentOrder['error'] ?? 'Unknown error'}',
        );
      }
    } catch (e) {
      throw Exception('Payment processing error: $e');
    }
  }

  static bool isValidWaafiPayPhone(String phone) {
    // Validate Waafi Pay phone number format: 2526XXXXXXXX
    final regex = RegExp(r'^2526[0-9]{8}$');
    return regex.hasMatch(phone);
  }
}
