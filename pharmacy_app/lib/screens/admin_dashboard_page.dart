import 'package:flutter/material.dart';
import '../models/user.dart';
import '../models/order.dart';
import '../models/medicine.dart';
import '../services/api_service.dart';

class AdminDashboardPage extends StatefulWidget {
  final String token;
  final int adminId;
  const AdminDashboardPage({
    super.key,
    required this.token,
    required this.adminId,
  });
  @override
  State<AdminDashboardPage> createState() => _AdminDashboardPageState();
}

class _AdminDashboardPageState extends State<AdminDashboardPage> {
  int _totalUsers = 0;
  int _totalPharmacies = 0;
  int _totalOrders = 0;
  int _totalMedicines = 0;
  double _totalRevenue = 0;
  List<User> _recentUsers = [];
  List<Order> _recentOrders = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchStats();
  }

  Future<void> _fetchStats() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final users = await ApiService.fetchUsers(widget.token);
      final orders = await ApiService.fetchOrders(widget.token);
      final medicines =
          await ApiService.fetchAllMedicines(); // Use fetchAllMedicines for admin

      final pharmacies = users.where((u) => u.userType == 'pharmacy').toList();
      final customers = users.where((u) => u.userType == 'customer').toList();
      final revenue = orders
          .where((o) => o.status == 'completed')
          .fold<double>(0, (sum, o) => sum + o.totalAmount);

      setState(() {
        _totalUsers = customers.length;
        _totalPharmacies = pharmacies.length;
        _totalOrders = orders.length;
        _totalMedicines = medicines.length;
        _totalRevenue = revenue;
        _recentUsers = users.take(5).toList();
        _recentOrders = orders.take(5).toList();
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
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchStats,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
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
                  colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E)],
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
                      Container(
                        width: 60,
                        height: 60,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(30),
                        ),
                        child: const Icon(
                          Icons.admin_panel_settings,
                          color: Color(0xFF0B6E6E),
                          size: 30,
                        ),
                      ),
                      const SizedBox(width: 16),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Admin Dashboard',
                              style: TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                            Text(
                              'System Overview',
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.white70,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Statistics Cards
            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              childAspectRatio: 1.2,
              children: [
                _buildStatCard(
                  'Total Users',
                  _totalUsers,
                  Icons.people,
                  const Color(0xFF0B6E6E),
                ),
                _buildStatCard(
                  'Pharmacies',
                  _totalPharmacies,
                  Icons.local_pharmacy,
                  const Color(0xFF0B6E6E),
                ),
                _buildStatCard(
                  'Total Orders',
                  _totalOrders,
                  Icons.receipt,
                  const Color(0xFF0B6E6E),
                ),
                _buildStatCard(
                  'Medicines',
                  _totalMedicines,
                  Icons.medication,
                  const Color(0xFF0B6E6E),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Revenue Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
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
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0B6E6E).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.attach_money,
                          color: Color(0xFF0B6E6E),
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 16),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Total Revenue',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    '\$${_totalRevenue.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF0B6E6E),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Recent Users
            _buildSectionCard(
              'Recent Users',
              Icons.people,
              _recentUsers.map((user) => _buildUserTile(user)).toList(),
            ),
            const SizedBox(height: 16),

            // Recent Orders
            _buildSectionCard(
              'Recent Orders',
              Icons.receipt,
              _recentOrders.map((order) => _buildOrderTile(order)).toList(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String title, int value, IconData icon, Color color) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: color, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: Colors.grey[600],
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            value.toString(),
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Color(0xFF0B6E6E),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionCard(String title, IconData icon, List<Widget> children) {
    return Container(
      width: double.infinity,
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                Icon(icon, color: const Color(0xFF0B6E6E), size: 24),
                const SizedBox(width: 12),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF0B6E6E),
                  ),
                ),
              ],
            ),
          ),
          if (children.isNotEmpty) ...[
            const Divider(height: 1),
            ...children,
          ] else ...[
            const Divider(height: 1),
            const Padding(
              padding: EdgeInsets.all(20),
              child: Text(
                'No data available',
                style: TextStyle(
                  color: Colors.grey,
                  fontStyle: FontStyle.italic,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildUserTile(User user) {
    return ListTile(
      leading: CircleAvatar(
        backgroundColor: const Color(0xFF0B6E6E).withOpacity(0.1),
        child: Icon(
          user.userType == 'pharmacy' ? Icons.local_pharmacy : Icons.person,
          color: const Color(0xFF0B6E6E),
        ),
      ),
      title: Text(
        user.email,
        style: const TextStyle(fontWeight: FontWeight.w600),
      ),
      subtitle: Text(
        'Type: ${user.userType}',
        style: TextStyle(color: Colors.grey[600]),
      ),
      trailing: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: _getUserTypeColor(user.userType).withOpacity(0.1),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(
          user.userType.toUpperCase(),
          style: TextStyle(
            color: _getUserTypeColor(user.userType),
            fontSize: 10,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _buildOrderTile(Order order) {
    return ListTile(
      leading: CircleAvatar(
        backgroundColor: _getOrderStatusColor(order.status).withOpacity(0.1),
        child: Icon(
          Icons.shopping_cart,
          color: _getOrderStatusColor(order.status),
        ),
      ),
      title: Text(
        'Order #${order.id}',
        style: const TextStyle(fontWeight: FontWeight.w600),
      ),
      subtitle: Text(
        'Customer: ${order.customerId} | Pharmacy: ${order.pharmacyId}',
        style: TextStyle(color: Colors.grey[600]),
      ),
      trailing: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: _getOrderStatusColor(order.status).withOpacity(0.1),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(
          order.status.toUpperCase(),
          style: TextStyle(
            color: _getOrderStatusColor(order.status),
            fontSize: 10,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Color _getUserTypeColor(String userType) {
    switch (userType) {
      case 'admin':
        return Colors.red;
      case 'pharmacy':
        return Colors.orange;
      case 'customer':
        return Colors.green;
      default:
        return Colors.grey;
    }
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

  Widget _buildEmptyState(String title, String subtitle) {
    return Container(
      padding: EdgeInsets.all(32),
      child: Column(
        children: [
          Icon(Icons.inbox_outlined, size: 64, color: Colors.grey[400]),
          SizedBox(height: 16),
          Text(
            title,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey[600],
            ),
          ),
          SizedBox(height: 8),
          Text(
            subtitle,
            style: TextStyle(fontSize: 14, color: Colors.grey[500]),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}
