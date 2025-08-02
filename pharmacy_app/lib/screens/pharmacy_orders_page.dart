import 'package:flutter/material.dart';
import '../models/order.dart';
import '../services/api_service.dart';

class PharmacyOrdersPage extends StatefulWidget {
  final String token;
  final int pharmacyId;
  const PharmacyOrdersPage({
    super.key,
    required this.token,
    required this.pharmacyId,
  });
  @override
  State<PharmacyOrdersPage> createState() => _PharmacyOrdersPageState();
}

class _PharmacyOrdersPageState extends State<PharmacyOrdersPage> {
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
      final orders = await ApiService.fetchOrdersByPharmacy(widget.pharmacyId);
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
            DropdownMenuItem(value: 'shipped', child: Text('Shipped')),
            DropdownMenuItem(value: 'delivered', child: Text('Delivered')),
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
              try {
                await ApiService.updateOrder(order.id, status);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(
                      'Order #${order.id} status updated to $status',
                    ),
                    backgroundColor: const Color(0xFF0b6e6e),
                  ),
                );
                Navigator.pop(ctx);
                _fetchOrders();
              } catch (e) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Error updating order: ${e.toString()}'),
                    backgroundColor: Colors.red,
                  ),
                );
              }
            },
            child: const Text('Update'),
          ),
        ],
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return const Color(0xFF0b6e6e);
      case 'processing':
        return const Color(0xFF0b6e6e);
      case 'shipped':
        return const Color(0xFF0b6e6e);
      case 'delivered':
        return const Color(0xFF0b6e6e);
      case 'cancelled':
        return Colors.red;
      default:
        return const Color(0xFF0b6e6e);
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _fetchOrders,
      child: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text('Error: $_error'))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF0b6e6e), Color(0xFF0b6e6e)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Manage Orders',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          '${_orders.length} orders found',
                          style: const TextStyle(
                            fontSize: 16,
                            color: Colors.white70,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Orders List
                  if (_orders.isEmpty)
                    _buildEmptyState()
                  else
                    ..._orders.map((order) => _buildOrderCard(order)),
                ],
              ),
            ),
    );
  }

  Widget _buildOrderCard(Order order) {
    return Card(
      elevation: 4,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(
                    color: _getStatusColor(order.status).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(25),
                  ),
                  child: Icon(
                    Icons.receipt,
                    color: _getStatusColor(order.status),
                    size: 24,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Order #${order.id}',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Customer ID: ${order.customerId}',
                        style: TextStyle(fontSize: 14, color: Colors.grey[600]),
                      ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  icon: const Icon(Icons.more_vert),
                  onSelected: (value) {
                    if (value == 'status') {
                      _showStatusDialog(order);
                    }
                  },
                  itemBuilder: (context) => [
                    const PopupMenuItem(
                      value: 'status',
                      child: Row(
                        children: [
                          Icon(Icons.edit, size: 20),
                          SizedBox(width: 8),
                          Text('Update Status'),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildInfoItem(
                    'Status',
                    order.status.toUpperCase(),
                    Icons.circle,
                    color: _getStatusColor(order.status),
                  ),
                ),
                Expanded(
                  child: _buildInfoItem(
                    'Total',
                    '\$${order.totalAmount.toStringAsFixed(2)}',
                    Icons.attach_money,
                  ),
                ),
                Expanded(
                  child: _buildInfoItem(
                    'Date',
                    '${order.createdAt.day}/${order.createdAt.month}/${order.createdAt.year}',
                    Icons.calendar_today,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.edit),
                    label: const Text('Update Status'),
                    onPressed: () => _showStatusDialog(order),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.visibility),
                    label: const Text('View Details'),
                    onPressed: () {
                      // Show order details
                      _showOrderDetails(order);
                    },
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoItem(
    String label,
    String value,
    IconData icon, {
    Color? color,
  }) {
    return Column(
      children: [
        Icon(icon, color: color ?? const Color(0xFF0b6e6e), size: 20),
        const SizedBox(height: 4),
        Text(label, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
        Text(
          value,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: color ?? const Color(0xFF0b6e6e),
          ),
        ),
      ],
    );
  }

  void _showOrderDetails(Order order) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Order #${order.id} Details'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Customer ID: ${order.customerId}'),
            Text('Status: ${order.status}'),
            Text('Total Amount: \$${order.totalAmount.toStringAsFixed(2)}'),
            Text('Date: ${order.createdAt.toString()}'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Close'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _showStatusDialog(order);
            },
            child: const Text('Update Status'),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(40),
        child: Column(
          children: [
            Icon(Icons.receipt_outlined, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No orders yet',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Orders will appear here when customers place them',
              style: TextStyle(fontSize: 14, color: Colors.grey[500]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
