import 'package:flutter/material.dart';
import '../models/order.dart';
import '../services/api_service.dart';

class AdminOrdersPage extends StatefulWidget {
  final String token;
  const AdminOrdersPage({super.key, required this.token});
  @override
  State<AdminOrdersPage> createState() => _AdminOrdersPageState();
}

class _AdminOrdersPageState extends State<AdminOrdersPage> {
  List<Order> _orders = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchOrders();
  }

  Future<void> _fetchOrders() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final orders = await ApiService.fetchOrders(widget.token);
      setState(() {
        _orders = orders;
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

  void _showStatusDialog(Order order) {
    String status = order.status;
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Update Order Status'),
        content: DropdownButton<String>(
          value: status,
          items: const [
            DropdownMenuItem(value: 'pending', child: Text('Pending')),
            DropdownMenuItem(value: 'processing', child: Text('Processing')),
            DropdownMenuItem(value: 'completed', child: Text('Completed')),
            DropdownMenuItem(value: 'cancelled', child: Text('Cancelled')),
          ],
          onChanged: (v) => setState(() => status = v ?? order.status),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              await ApiService.updateOrder(order.id, status);
              Navigator.pop(ctx);
              _fetchOrders();
            },
            child: const Text('Update'),
          ),
        ],
      ),
    );
  }

  void _deleteOrder(Order order) async {
    await ApiService.deleteOrder(widget.token, order.id);
    _fetchOrders();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(
          valueColor: AlwaysStoppedAnimation<Color>(Color(0xFF0B6E6E)),
        ),
      );
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.red[400]),
            const SizedBox(height: 16),
            Text(
              'Error loading orders',
              style: TextStyle(fontSize: 18, color: Colors.red[600]),
            ),
            const SizedBox(height: 8),
            Text(
              _error!,
              style: TextStyle(color: Colors.grey[600]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      );
    }

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Orders Management',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      '${_orders.length} total orders',
                      style: const TextStyle(
                        fontSize: 16,
                        color: Colors.white70,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Orders Grid
          Expanded(
            child: _orders.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.receipt_outlined,
                          size: 80,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'No orders found',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Orders will appear here when customers place them',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  )
                : GridView.builder(
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 16,
                          mainAxisSpacing: 16,
                          childAspectRatio: 0.65,
                        ),
                    itemCount: _orders.length,
                    itemBuilder: (context, index) {
                      final order = _orders[index];
                      return Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.grey.withOpacity(0.1),
                              spreadRadius: 1,
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            // Header
                            Container(
                              width: double.infinity,
                              height: 80,
                              decoration: BoxDecoration(
                                color: _getOrderStatusColor(order.status),
                                borderRadius: const BorderRadius.only(
                                  topLeft: Radius.circular(16),
                                  topRight: Radius.circular(16),
                                ),
                              ),
                              child: const Icon(
                                Icons.shopping_cart,
                                color: Colors.white,
                                size: 40,
                              ),
                            ),

                            // Content
                            Expanded(
                              child: Padding(
                                padding: const EdgeInsets.all(8),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Order #${order.id}',
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      'Customer: ${order.customerId}',
                                      style: TextStyle(
                                        fontSize: 8,
                                        color: Colors.grey[600],
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 4),
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 4,
                                        vertical: 1,
                                      ),
                                      decoration: BoxDecoration(
                                        color: _getOrderStatusColor(
                                          order.status,
                                        ).withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        order.status.toUpperCase(),
                                        style: TextStyle(
                                          color: _getOrderStatusColor(
                                            order.status,
                                          ),
                                          fontSize: 7,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                    const Spacer(),
                                    Text(
                                      '\$${order.totalAmount.toStringAsFixed(2)}',
                                      style: const TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFF0B6E6E),
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceEvenly,
                                      children: [
                                        IconButton(
                                          icon: const Icon(
                                            Icons.edit,
                                            color: Color(0xFF0B6E6E),
                                            size: 16,
                                          ),
                                          onPressed: () =>
                                              _showStatusDialog(order),
                                          padding: EdgeInsets.zero,
                                          constraints: const BoxConstraints(
                                            minWidth: 28,
                                            minHeight: 28,
                                          ),
                                        ),
                                        IconButton(
                                          icon: const Icon(
                                            Icons.delete,
                                            color: Colors.red,
                                            size: 16,
                                          ),
                                          onPressed: () => _deleteOrder(order),
                                          padding: EdgeInsets.zero,
                                          constraints: const BoxConstraints(
                                            minWidth: 28,
                                            minHeight: 28,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Color _getOrderStatusColor(String status) {
    switch (status) {
      case 'completed':
        return Colors.green;
      case 'processing':
        return Colors.orange;
      case 'pending':
        return Colors.blue;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
