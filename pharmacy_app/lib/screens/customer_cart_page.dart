import 'package:flutter/material.dart';
import '../models/medicine.dart';
import '../models/order.dart';
import '../services/api_service.dart';
import '../services/payment_service.dart';

class CustomerCartPage extends StatefulWidget {
  final String token;
  final int customerId;
  final Function(int)? onNavigateToPage;

  const CustomerCartPage({
    Key? key,
    required this.token,
    required this.customerId,
    this.onNavigateToPage,
  }) : super(key: key);

  @override
  State<CustomerCartPage> createState() => _CustomerCartPageState();
}

class _CustomerCartPageState extends State<CustomerCartPage> {
  List<Map<String, dynamic>> _cartItems = [];
  bool _loading = true;
  String? _error;
  bool _processingPayment = false;
  String _phoneNumber = '';
  final _phoneController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchCartItems();
  }

  Future<void> _fetchCartItems() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final cartItems = await ApiService.fetchCartItems(widget.customerId);
      setState(() {
        _cartItems = cartItems;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    }

    setState(() {
      _loading = false;
    });
  }

  double get _totalAmount {
    return _cartItems.fold(0, (sum, item) {
      return sum + (double.parse(item['price'].toString()) * item['quantity']);
    });
  }

  void _updateQuantity(int index, int newQuantity) async {
    if (newQuantity > 0) {
      try {
        final cartId = _cartItems[index]['id'] as int;
        await ApiService.updateCartItem(widget.customerId, cartId, newQuantity);

        setState(() {
          _cartItems[index]['quantity'] = newQuantity;
        });

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Cart updated successfully!'),
            backgroundColor: Color(0xFF0b6e6e),
          ),
        );
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error updating cart: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } else {
      _removeItem(index);
    }
  }

  void _removeItem(int index) async {
    try {
      final cartId = _cartItems[index]['id'] as int;
      await ApiService.removeFromCart(widget.customerId, cartId);

      setState(() {
        _cartItems.removeAt(index);
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Item removed from cart!'),
          backgroundColor: Color(0xFF0b6e6e),
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error removing item: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _checkout() async {
    if (_cartItems.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Your cart is empty!'),
          backgroundColor: Color(0xFF0b6e6e),
        ),
      );
      return;
    }

    // Show Waafi Pay phone number dialog
    await _showWaafiPayDialog();
  }

  Future<void> _showWaafiPayDialog() async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Waafi Pay Payment'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Enter your Waafi Pay phone number to complete the payment:',
                style: TextStyle(fontSize: 16),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _phoneController,
                decoration: const InputDecoration(
                  labelText: 'Phone Number',
                  hintText: '2526XXXXXXXX',
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 8),
              Text(
                'Total Amount: \$${_totalAmount.toStringAsFixed(2)}',
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0b6e6e),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: _processPayment,
              child: const Text('Pay Now'),
            ),
          ],
        );
      },
    );
  }

  Future<void> _processPayment() async {
    final phone = _phoneController.text.trim();

    if (!PaymentService.isValidWaafiPayPhone(phone)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Please enter a valid Waafi Pay phone number (2526XXXXXXXX)',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() {
      _processingPayment = true;
    });

    try {
      // Convert cart items to the format expected by the API
      final cartData = _cartItems
          .map(
            (item) => {
              'medicine_id': item['medicine_id'],
              'quantity': item['quantity'],
              'price': item['price'],
              'name': item['name'],
            },
          )
          .toList();

      // Process payment with Waafi Pay
      final paymentResult = await PaymentService.processOrderPayment(
        token: widget.token,
        orderId: 1, // You might need to get the actual order ID
        amount: _totalAmount,
        customerPhone: phone,
        customerName: 'Customer',
        cart: cartData,
      );

      if (paymentResult['success'] == true) {
        // Clear cart after successful payment
        setState(() {
          _cartItems.clear();
        });

        Navigator.of(context).pop(); // Close dialog

        // Show success dialog
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Payment Successful!'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Your order has been placed successfully.'),
                const SizedBox(height: 8),
                Text('Transaction ID: ${paymentResult['transaction_id']}'),
                Text('Order Ref: ${paymentResult['order_ref']}'),
                const SizedBox(height: 8),
                const Text('You will receive a confirmation SMS shortly.'),
              ],
            ),
            actions: [
              ElevatedButton(
                onPressed: () {
                  Navigator.pop(context);
                  Navigator.pop(context); // Go back to previous screen
                },
                child: const Text('OK'),
              ),
            ],
          ),
        );
      } else {
        throw Exception('Payment failed');
      }
    } catch (e) {
      Navigator.of(context).pop(); // Close dialog

      // Show error dialog
      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Payment Failed'),
          content: Text('An error occurred: ${e.toString()}'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('OK'),
            ),
          ],
        ),
      );
    } finally {
      setState(() {
        _processingPayment = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF0b6e6e), Color(0xFF0b6e6e)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Your Cart',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        '${_cartItems.length} items',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'Total: \$${_totalAmount.toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 18,
                    color: Colors.white,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),

          // Cart Items
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                ? Center(
                    child: Text(
                      'Error: $_error',
                      style: const TextStyle(color: Colors.red),
                    ),
                  )
                : _cartItems.isEmpty
                ? _buildEmptyState()
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _cartItems.length,
                    itemBuilder: (context, index) {
                      return _buildCartItemCard(index, _cartItems[index]);
                    },
                  ),
          ),

          // Checkout Button
          if (_cartItems.isNotEmpty)
            Container(
              padding: const EdgeInsets.all(16),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _processingPayment ? null : _checkout,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0b6e6e),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: _processingPayment
                      ? const Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            ),
                            SizedBox(width: 12),
                            Text('Processing Payment...'),
                          ],
                        )
                      : const Text(
                          'Proceed to Checkout',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.shopping_cart_outlined, size: 80, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            'Your cart is empty',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.grey[600],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Add some medicines to get started',
            style: TextStyle(fontSize: 16, color: Colors.grey[500]),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: () {
              // Navigate to medicines page (index 1) using callback
              widget.onNavigateToPage?.call(1);
            },
            icon: const Icon(Icons.shopping_bag),
            label: const Text('Browse Medicines'),
          ),
        ],
      ),
    );
  }

  Widget _buildCartItemCard(int index, Map<String, dynamic> item) {
    // API data format: {'id': 1, 'name': 'Medicine Name', 'price': '10.00', 'quantity': 2, 'pharmacy_name': 'Pharmacy Name'}
    final name = item['name'] as String;
    final price = double.parse(item['price'].toString());
    final quantity = item['quantity'] as int;
    final pharmacyName = item['pharmacy_name'] as String;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'From: $pharmacyName',
                        style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '\$${price.toStringAsFixed(2)}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF0b6e6e),
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: () => _removeItem(index),
                  icon: const Icon(Icons.delete, color: Colors.red),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    IconButton(
                      onPressed: () => _updateQuantity(index, quantity - 1),
                      icon: const Icon(Icons.remove_circle_outline),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.grey[100],
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        quantity.toString(),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    IconButton(
                      onPressed: () => _updateQuantity(index, quantity + 1),
                      icon: const Icon(Icons.add_circle_outline),
                    ),
                  ],
                ),
                Text(
                  '\$${(price * quantity).toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF0b6e6e),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
