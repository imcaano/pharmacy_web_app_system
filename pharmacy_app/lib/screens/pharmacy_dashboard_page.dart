import 'package:flutter/material.dart';
import '../services/api_service.dart';

class PharmacyDashboardPage extends StatefulWidget {
  final String token;
  final int pharmacyId;
  const PharmacyDashboardPage({
    super.key,
    required this.token,
    required this.pharmacyId,
  });
  @override
  State<PharmacyDashboardPage> createState() => _PharmacyDashboardPageState();
}

class _PharmacyDashboardPageState extends State<PharmacyDashboardPage> {
  Map<String, dynamic> _stats = {};
  List<Map<String, dynamic>> _recentOrders = [];
  List<Map<String, dynamic>> _lowStockMedicines = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchDashboardData();
  }

  Future<void> _fetchDashboardData() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      // Use the user's pharmacy_id
      final pharmacyId = widget.pharmacyId;
      
      // Fetch pharmacy statistics
      final stats = await ApiService.fetchPharmacyStats(pharmacyId);
      final recentOrders = await ApiService.fetchRecentOrders(pharmacyId);
      final lowStockMedicines = await ApiService.fetchLowStockMedicines(pharmacyId);

      setState(() {
        _stats = stats;
        _recentOrders = recentOrders;
        _lowStockMedicines = lowStockMedicines;
      });
    } catch (e) {
      // Fallback to mock data if API fails
      setState(() {
        _stats = {
          'total_medicines': 45,
          'total_orders': 128,
          'pending_orders': 12,
          'total_revenue': 15420.50,
        };

        _recentOrders = [
          {
            'id': 1,
            'customer_email': 'john@example.com',
            'total_amount': 45.99,
            'status': 'pending',
            'created_at': '2024-01-15 10:30:00',
          },
          {
            'id': 2,
            'customer_email': 'sarah@example.com',
            'total_amount': 32.50,
            'status': 'processing',
            'created_at': '2024-01-15 09:15:00',
          },
          {
            'id': 3,
            'customer_email': 'mike@example.com',
            'total_amount': 78.25,
            'status': 'completed',
            'created_at': '2024-01-14 16:45:00',
          },
        ];

        _lowStockMedicines = [
          {
            'id': 1,
            'name': 'Paracetamol 500mg',
            'stock_quantity': 5,
            'price': 5.99,
          },
          {
            'id': 2,
            'name': 'Amoxicillin 250mg',
            'stock_quantity': 3,
            'price': 12.50,
          },
          {
            'id': 3,
            'name': 'Ibuprofen 400mg',
            'stock_quantity': 8,
            'price': 7.25,
          },
        ];

        _error = 'Using demo data (API error: ${e.toString()})';
      });
    }

    setState(() {
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _fetchDashboardData,
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
                          'Pharmacy Dashboard',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Manage your pharmacy operations',
                          style: TextStyle(fontSize: 16, color: Colors.white70),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Statistics Cards
                  GridView.count(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisCount: 2,
                    crossAxisSpacing: 16,
                    mainAxisSpacing: 16,
                    childAspectRatio: 1.5,
                    children: [
                      _buildStatCard(
                        'Total Medicines',
                        _stats['total_medicines']?.toString() ?? '0',
                        Icons.medication,
                        const Color(0xFF0b6e6e),
                      ),
                      _buildStatCard(
                        'Total Orders',
                        _stats['total_orders']?.toString() ?? '0',
                        Icons.receipt,
                        const Color(0xFF0b6e6e),
                      ),
                      _buildStatCard(
                        'Pending Orders',
                        _stats['pending_orders']?.toString() ?? '0',
                        Icons.pending,
                        const Color(0xFF0b6e6e),
                      ),
                      _buildStatCard(
                        'Total Revenue',
                        '\$${_stats['total_revenue']?.toString() ?? '0'}',
                        Icons.attach_money,
                        const Color(0xFF0b6e6e),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // Recent Orders Section
                  _buildSectionCard(
                    'Recent Orders',
                    Icons.receipt,
                    _recentOrders.isEmpty
                        ? _buildEmptyState('No recent orders')
                        : Column(
                            children: _recentOrders
                                .map((order) => _buildOrderItem(order))
                                .toList(),
                          ),
                  ),
                  const SizedBox(height: 20),

                  // Low Stock Medicines Section
                  _buildSectionCard(
                    'Low Stock Medicines',
                    Icons.warning,
                    _lowStockMedicines.isEmpty
                        ? _buildEmptyState('All medicines are well stocked')
                        : Column(
                            children: _lowStockMedicines
                                .map((medicine) => _buildMedicineItem(medicine))
                                .toList(),
                          ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildStatCard(
    String title,
    String value,
    IconData icon,
    Color color,
  ) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            colors: [color.withOpacity(0.1), color.withOpacity(0.05)],
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
                Icon(icon, color: color, size: 24),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              title,
              style: const TextStyle(
                fontSize: 14,
                color: Colors.grey,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionCard(String title, IconData icon, Widget content) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
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
            const SizedBox(height: 16),
            content,
          ],
        ),
      ),
    );
  }

  Widget _buildOrderItem(Map<String, dynamic> order) {
    return Container(
      padding: const EdgeInsets.all(12),
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: const Color(0xFF0b6e6e).withOpacity(0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(
              Icons.receipt,
              color: Color(0xFF0b6e6e),
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Order #${order['id']}',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
                Text(
                  'Customer: ${order['customer_email'] ?? 'N/A'}',
                  style: TextStyle(color: Colors.grey[600], fontSize: 12),
                ),
                Text(
                  'Status: ${order['status']}',
                  style: TextStyle(color: Colors.grey[600], fontSize: 12),
                ),
              ],
            ),
          ),
          Text(
            '\$${order['total_amount']?.toString() ?? '0'}',
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              color: Color(0xFF0b6e6e),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMedicineItem(Map<String, dynamic> medicine) {
    return Container(
      padding: const EdgeInsets.all(12),
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: const Color(0xFF0b6e6e).withOpacity(0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(
              Icons.medication,
              color: Color(0xFF0b6e6e),
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  medicine['name'] ?? 'Unknown',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
                Text(
                  'Stock: ${medicine['stock_quantity']} units',
                  style: TextStyle(color: Colors.grey[600], fontSize: 12),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.orange.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              'Low Stock',
              style: TextStyle(
                color: Colors.orange[700],
                fontSize: 10,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState(String message) {
    return Container(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          Icon(Icons.inbox, size: 48, color: Colors.grey[400]),
          const SizedBox(height: 8),
          Text(
            message,
            style: TextStyle(color: Colors.grey[600], fontSize: 14),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}
