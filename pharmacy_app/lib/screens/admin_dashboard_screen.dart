import 'package:flutter/material.dart';
import '../models/user.dart';
import 'admin_dashboard_page.dart';
import 'admin_pharmacies_page.dart';
import 'admin_users_page.dart';
import 'admin_medicines_page.dart';
import 'admin_orders_page.dart';
import 'admin_transactions_page.dart';
import 'admin_reports_page.dart';
import 'admin_profile_page.dart';

class AdminDashboardScreen extends StatefulWidget {
  final String token;
  final int adminId;
  const AdminDashboardScreen({
    super.key,
    required this.token,
    required this.adminId,
  });
  @override
  State<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> {
  int _selectedIndex = 0;
  final List<Map<String, dynamic>> _menuItems = [
    {'title': 'Dashboard', 'icon': Icons.dashboard, 'color': Color(0xFF0B6E6E)},
    {
      'title': 'Pharmacies',
      'icon': Icons.local_pharmacy,
      'color': Color(0xFF0B6E6E),
    },
    {'title': 'Users', 'icon': Icons.people, 'color': Color(0xFF0B6E6E)},
    {
      'title': 'Medicines',
      'icon': Icons.medication,
      'color': Color(0xFF0B6E6E),
    },
    {'title': 'Orders', 'icon': Icons.receipt, 'color': Color(0xFF0B6E6E)},
    {
      'title': 'Transactions',
      'icon': Icons.payment,
      'color': Color(0xFF0B6E6E),
    },
    {'title': 'Reports', 'icon': Icons.analytics, 'color': Color(0xFF0B6E6E)},
    {'title': 'Profile', 'icon': Icons.person, 'color': Color(0xFF0B6E6E)},
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          _menuItems[_selectedIndex]['title'],
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w600,
          ),
        ),
        backgroundColor: const Color(0xFF0B6E6E),
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.white),
            onPressed: () {
              Navigator.of(context).pushReplacementNamed('/login');
            },
          ),
        ],
      ),
      drawer: _buildDrawer(),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF0B6E6E), Colors.white],
          ),
        ),
        child: _buildPage(),
      ),
    );
  }

  Widget _buildDrawer() {
    return Drawer(
      child: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E)],
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
                      Icons.admin_panel_settings,
                      size: 40,
                      color: Color(0xFF0B6E6E),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Admin Panel',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const Text(
                    'System Management',
                    style: TextStyle(color: Colors.white70, fontSize: 14),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: _menuItems.length + 1, // +1 for logout
                itemBuilder: (context, index) {
                  if (index == _menuItems.length) {
                    // Logout button
                    return Container(
                      margin: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.red.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.red, width: 2),
                      ),
                      child: ListTile(
                        leading: const Icon(
                          Icons.logout,
                          color: Colors.red,
                          size: 24,
                        ),
                        title: const Text(
                          'Logout',
                          style: TextStyle(
                            color: Colors.red,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        onTap: () {
                          Navigator.of(context).pushReplacementNamed('/login');
                        },
                      ),
                    );
                  }

                  final item = _menuItems[index];
                  final isSelected = _selectedIndex == index;

                  return Container(
                    margin: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: isSelected
                          ? Colors.white.withOpacity(0.2)
                          : Colors.transparent,
                      borderRadius: BorderRadius.circular(12),
                      border: isSelected
                          ? Border.all(color: Colors.white, width: 2)
                          : null,
                    ),
                    child: ListTile(
                      leading: Icon(
                        item['icon'],
                        color: isSelected ? Colors.white : Colors.white70,
                        size: 24,
                      ),
                      title: Text(
                        item['title'],
                        style: TextStyle(
                          color: isSelected ? Colors.white : Colors.white70,
                          fontWeight: isSelected
                              ? FontWeight.w600
                              : FontWeight.normal,
                        ),
                      ),
                      onTap: () {
                        setState(() {
                          _selectedIndex = index;
                        });
                        Navigator.pop(context);
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPage() {
    switch (_selectedIndex) {
      case 0:
        return AdminDashboardPage(token: widget.token, adminId: widget.adminId);
      case 1:
        return AdminPharmaciesPage(token: widget.token);
      case 2:
        return AdminUsersPage(token: widget.token);
      case 3:
        return AdminMedicinesPage(token: widget.token);
      case 4:
        return AdminOrdersPage(token: widget.token);
      case 5:
        return AdminTransactionsPage(token: widget.token);
      case 6:
        return AdminReportsPage(token: widget.token);
      case 7:
        return AdminProfilePage(token: widget.token, adminId: widget.adminId);
      default:
        return AdminDashboardPage(token: widget.token, adminId: widget.adminId);
    }
  }
}
