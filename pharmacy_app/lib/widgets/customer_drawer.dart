import 'package:flutter/material.dart';

class CustomerDrawer extends StatelessWidget {
  final String customerName;
  final String customerEmail;
  final VoidCallback onDashboardTap;
  final VoidCallback onMedicinesTap;
  final VoidCallback onCartTap;
  final VoidCallback onOrdersTap;
  final VoidCallback onPrescriptionsTap;
  final VoidCallback onProfileTap;
  final VoidCallback onLogoutTap;

  const CustomerDrawer({
    Key? key,
    required this.customerName,
    required this.customerEmail,
    required this.onDashboardTap,
    required this.onMedicinesTap,
    required this.onCartTap,
    required this.onOrdersTap,
    required this.onPrescriptionsTap,
    required this.onProfileTap,
    required this.onLogoutTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Drawer(
      child: Column(
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: const EdgeInsets.only(
              top: 50,
              bottom: 20,
              left: 20,
              right: 20,
            ),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF0b6e6e), Color(0xFF0a5a5a)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 30,
                  backgroundColor: Colors.white,
                  child: Icon(
                    Icons.person,
                    size: 35,
                    color: const Color(0xFF0b6e6e),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  customerName,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  customerEmail,
                  style: const TextStyle(color: Colors.white70, fontSize: 14),
                ),
              ],
            ),
          ),

          // Menu Items
          Expanded(
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                _buildMenuItem(
                  icon: Icons.dashboard,
                  title: 'Dashboard',
                  onTap: onDashboardTap,
                ),
                _buildMenuItem(
                  icon: Icons.medication,
                  title: 'Browse Medicines',
                  onTap: onMedicinesTap,
                ),
                _buildMenuItem(
                  icon: Icons.shopping_cart,
                  title: 'Shopping Cart',
                  onTap: onCartTap,
                ),
                _buildMenuItem(
                  icon: Icons.receipt_long,
                  title: 'My Orders',
                  onTap: onOrdersTap,
                ),
                _buildMenuItem(
                  icon: Icons.description,
                  title: 'Prescriptions',
                  onTap: onPrescriptionsTap,
                ),
                const Divider(height: 1),
                _buildMenuItem(
                  icon: Icons.person,
                  title: 'Profile',
                  onTap: onProfileTap,
                ),
                _buildMenuItem(
                  icon: Icons.logout,
                  title: 'Logout',
                  onTap: onLogoutTap,
                ),
              ],
            ),
          ),

          // Footer
          Container(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                const Text(
                  'Pharmacy App',
                  style: TextStyle(
                    color: Color(0xFF0b6e6e),
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Version 1.0.0',
                  style: TextStyle(color: Colors.grey[600], fontSize: 12),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuItem({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return ListTile(
      leading: Icon(icon, color: const Color(0xFF0b6e6e), size: 24),
      title: Text(
        title,
        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
      ),
      onTap: onTap,
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
    );
  }
}
