import 'package:flutter/material.dart';
import '../models/order.dart';
import '../services/api_service.dart';

class AdminTransactionsPage extends StatefulWidget {
  final String token;
  const AdminTransactionsPage({super.key, required this.token});
  @override
  State<AdminTransactionsPage> createState() => _AdminTransactionsPageState();
}

class _AdminTransactionsPageState extends State<AdminTransactionsPage> {
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

  void _viewTransaction(dynamic transaction) {
    // Placeholder for viewing transaction details
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('View transaction ${transaction.id}'),
        backgroundColor: const Color(0xFF0B6E6E),
      ),
    );
  }

  void _deleteTransaction(dynamic transaction) {
    // Placeholder for deleting transaction
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Delete transaction ${transaction.id}'),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _showFilterDialog() {
    // Placeholder for filter dialog
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Filter transactions'),
        backgroundColor: Color(0xFF0B6E6E),
      ),
    );
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
              'Error loading transactions',
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

    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFF0B6E6E), Colors.white],
        ),
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(24),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.only(
                bottomLeft: Radius.circular(24),
                bottomRight: Radius.circular(24),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Transactions Management',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      Text(
                        '${_orders.length} total transactions',
                        style: const TextStyle(
                          fontSize: 14,
                          color: Colors.white70,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                ElevatedButton.icon(
                  icon: const Icon(Icons.filter_list, size: 16),
                  label: const Text('Filter', style: TextStyle(fontSize: 12)),
                  onPressed: () => _showFilterDialog(),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFF0B6E6E),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 8,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Transactions Table
          Expanded(
            child: _orders.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.receipt_long_outlined,
                          size: 80,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'No transactions found',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Transactions will appear here',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  )
                : Container(
                    margin: const EdgeInsets.symmetric(horizontal: 16),
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
                    child: SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: DataTable(
                        columnSpacing: 32,
                        headingRowColor: MaterialStateProperty.all(
                          const Color(0xFF0B6E6E).withOpacity(0.1),
                        ),
                        columns: const [
                          DataColumn(
                            label: Text(
                              'ID',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Order ID',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Amount',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Status',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Date',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Actions',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                        ],
                        rows: _orders.map((order) {
                          return DataRow(
                            cells: [
                              DataCell(Text('#${order.id}')),
                              DataCell(Text('${order.id}')),
                              DataCell(
                                Text(
                                  '\$${order.totalAmount.toStringAsFixed(2)}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF0B6E6E),
                                  ),
                                ),
                              ),
                              DataCell(
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 4,
                                  ),
                                  decoration: BoxDecoration(
                                    color: Colors.green.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: const Text(
                                    'COMPLETED',
                                    style: TextStyle(
                                      color: Colors.green,
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ),
                              DataCell(Text(order.createdAt.toString())),
                              DataCell(
                                Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      icon: const Icon(
                                        Icons.visibility,
                                        color: Color(0xFF0B6E6E),
                                        size: 16,
                                      ),
                                      onPressed: () => _viewTransaction(order),
                                      padding: EdgeInsets.zero,
                                      constraints: const BoxConstraints(
                                        minWidth: 24,
                                        minHeight: 24,
                                      ),
                                    ),
                                    IconButton(
                                      icon: const Icon(
                                        Icons.delete,
                                        color: Colors.red,
                                        size: 16,
                                      ),
                                      onPressed: () =>
                                          _deleteTransaction(order),
                                      padding: EdgeInsets.zero,
                                      constraints: const BoxConstraints(
                                        minWidth: 24,
                                        minHeight: 24,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          );
                        }).toList(),
                      ),
                    ),
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
