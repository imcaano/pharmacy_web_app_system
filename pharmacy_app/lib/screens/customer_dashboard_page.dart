import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/order.dart';
import '../models/prescription.dart';

class CustomerDashboardPage extends StatefulWidget {
  final String token;
  final int customerId;

  const CustomerDashboardPage({
    Key? key,
    required this.token,
    required this.customerId,
  }) : super(key: key);

  @override
  State<CustomerDashboardPage> createState() => _CustomerDashboardPageState();
}

class _CustomerDashboardPageState extends State<CustomerDashboardPage> {
  List<Order> _recentOrders = [];
  List<Prescription> _recentPrescriptions = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  Future<void> _loadDashboardData() async {
    try {
      setState(() {
        _loading = true;
        _error = null;
      });

      // Load recent orders and prescriptions
      final orders = await ApiService.fetchOrders(widget.token);
      final prescriptions = await ApiService.fetchPrescriptions();

      setState(() {
        _recentOrders = orders
            .where((order) => order.customerId == widget.customerId)
            .take(3)
            .toList();
        _recentPrescriptions = prescriptions
            .where(
              (prescription) => prescription.customerId == widget.customerId,
            )
            .take(3)
            .toList();
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  double get _totalSpent {
    return _recentOrders.fold(0, (sum, order) => sum + order.totalAmount);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error, size: 64, color: Colors.red[400]),
                  const SizedBox(height: 16),
                  Text(
                    'Error loading dashboard',
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
            )
          : RefreshIndicator(
              onRefresh: _loadDashboardData,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Welcome Banner
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF0b6e6e), Color(0xFF0a5a5a)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text(
                                'Welcome back!',
                                style: TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Text('👋', style: TextStyle(fontSize: 24)),
                            ],
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            "Here's what's happening with your pharmacy orders",
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.white70,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Stats Cards
                    GridView.count(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      crossAxisCount: 2,
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                      childAspectRatio: 1.1,
                      children: [
                        _buildStatCard(
                          icon: Icons.shopping_cart,
                          title: 'Orders',
                          value: _recentOrders.length.toString(),
                          color: const Color(0xFF0b6e6e),
                        ),
                        _buildStatCard(
                          icon: Icons.schedule,
                          title: 'Pending',
                          value: _recentOrders
                              .where((order) => order.status == 'pending')
                              .length
                              .toString(),
                          color: Colors.orange,
                        ),
                        _buildStatCard(
                          icon: Icons.description,
                          title: 'Prescriptions',
                          value: _recentPrescriptions.length.toString(),
                          color: Colors.purple,
                        ),
                        _buildStatCard(
                          icon: Icons.attach_money,
                          title: 'Total Spent',
                          value: '\$${_totalSpent.toStringAsFixed(2)}',
                          color: Colors.blue,
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Recent Orders
                    _buildSection(
                      title: 'Recent Orders',
                      icon: Icons.receipt_long,
                      child: _recentOrders.isEmpty
                          ? _buildEmptyState(
                              icon: Icons.shopping_bag_outlined,
                              title: 'No orders yet',
                              subtitle:
                                  'Start shopping to see your orders here',
                            )
                          : Column(
                              children: _recentOrders
                                  .map((order) => _buildOrderCard(order))
                                  .toList(),
                            ),
                    ),
                    const SizedBox(height: 20),

                    // Recent Prescriptions
                    _buildSection(
                      title: 'Recent Prescriptions',
                      icon: Icons.description,
                      child: _recentPrescriptions.isEmpty
                          ? _buildEmptyState(
                              icon: Icons.description_outlined,
                              title: 'No prescriptions yet',
                              subtitle:
                                  'Upload your first prescription to get started',
                            )
                          : Column(
                              children: _recentPrescriptions
                                  .map(
                                    (prescription) =>
                                        _buildPrescriptionCard(prescription),
                                  )
                                  .toList(),
                            ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildStatCard({
    required IconData icon,
    required String title,
    required String value,
    required Color color,
  }) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 24),
            ),
            const SizedBox(height: 12),
            Text(
              value,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              title,
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({
    required String title,
    required IconData icon,
    required Widget child,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, color: const Color(0xFF0b6e6e), size: 24),
            const SizedBox(width: 8),
            Text(
              title,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF0b6e6e),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        child,
      ],
    );
  }

  Widget _buildEmptyState({
    required IconData icon,
    required String title,
    required String subtitle,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          children: [
            Icon(icon, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              title,
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 8),
            Text(
              subtitle,
              style: TextStyle(fontSize: 14, color: Colors.grey[500]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOrderCard(Order order) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: _getStatusColor(order.status).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(
                Icons.shopping_bag,
                color: _getStatusColor(order.status),
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Order #${order.id}',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Status: ${order.status.toUpperCase()}',
                    style: TextStyle(
                      color: _getStatusColor(order.status),
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '\$${order.totalAmount.toStringAsFixed(2)}',
                    style: const TextStyle(
                      color: Color(0xFF0b6e6e),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPrescriptionCard(Prescription prescription) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: _getStatusColor(prescription.status).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(
                Icons.description,
                color: _getStatusColor(prescription.status),
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Prescription #${prescription.id}',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Status: ${prescription.status.toUpperCase()}',
                    style: TextStyle(
                      color: _getStatusColor(prescription.status),
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    prescription.createdAt.toString().split(' ')[0],
                    style: TextStyle(color: Colors.grey[600], fontSize: 12),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return Colors.orange;
      case 'approved':
      case 'completed':
        return Colors.green;
      case 'rejected':
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
