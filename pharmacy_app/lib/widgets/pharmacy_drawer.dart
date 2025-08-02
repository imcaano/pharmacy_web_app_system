import 'package:flutter/material.dart';

class PharmacyDrawer extends StatelessWidget {
  final String pharmacyName;
  final VoidCallback onLogout;
  final Function(int) onPageChanged;
  final int selectedIndex;

  const PharmacyDrawer({
    super.key,
    required this.pharmacyName,
    required this.onLogout,
    required this.onPageChanged,
    required this.selectedIndex,
  });

  @override
  Widget build(BuildContext context) {
    return Drawer(
      child: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF0b6e6e), Color(0xFF0b6e6e)],
          ),
        ),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.only(top: 50, bottom: 20),
              child: Column(
                children: [
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(40),
                    ),
                    child: const Icon(
                      Icons.local_pharmacy,
                      size: 40,
                      color: Color(0xFF0b6e6e),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    pharmacyName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const Text(
                    'Pharmacy Portal',
                    style: TextStyle(color: Colors.white70, fontSize: 14),
                  ),
                ],
              ),
            ),
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: ListView(
                  padding: const EdgeInsets.only(top: 20),
                  children: [
                    _buildMenuItem(
                      context,
                      icon: Icons.dashboard,
                      title: 'Dashboard',
                      index: 0,
                    ),
                    _buildMenuItem(
                      context,
                      icon: Icons.medication,
                      title: 'Medicines',
                      index: 1,
                    ),
                    _buildMenuItem(
                      context,
                      icon: Icons.receipt,
                      title: 'Orders',
                      index: 2,
                    ),
                    _buildMenuItem(
                      context,
                      icon: Icons.file_copy,
                      title: 'Prescriptions',
                      index: 3,
                    ),
                    _buildMenuItem(
                      context,
                      icon: Icons.person,
                      title: 'Profile',
                      index: 4,
                    ),
                    const Divider(),
                    _buildMenuItem(
                      context,
                      icon: Icons.logout,
                      title: 'Logout',
                      isLogout: true,
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMenuItem(
    BuildContext context, {
    required IconData icon,
    required String title,
    int? index,
    bool isLogout = false,
  }) {
    final isSelected = !isLogout && index == selectedIndex;
    
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      decoration: BoxDecoration(
        color: isSelected
            ? const Color(0xFF0b6e6e).withOpacity(0.1)
            : Colors.transparent,
        borderRadius: BorderRadius.circular(12),
        border: isSelected
            ? Border.all(color: const Color(0xFF0b6e6e), width: 2)
            : null,
      ),
      child: ListTile(
        leading: Icon(
          icon,
          color: isLogout 
              ? Colors.red[600] 
              : (isSelected ? const Color(0xFF0b6e6e) : Colors.grey[600]),
          size: 24,
        ),
        title: Text(
          title,
          style: TextStyle(
            color: isLogout 
                ? Colors.red[600] 
                : (isSelected ? const Color(0xFF0b6e6e) : Colors.grey[800]),
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
          ),
        ),
        onTap: () {
          if (isLogout) {
            onLogout();
          } else {
            onPageChanged(index!);
            Navigator.pop(context);
          }
        },
      ),
    );
  }
}
