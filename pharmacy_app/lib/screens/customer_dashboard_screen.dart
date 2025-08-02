import 'package:flutter/material.dart';
import '../models/user.dart';
import 'customer_dashboard_page.dart';
import 'customer_medicines_page.dart';
import 'customer_cart_page.dart';
import 'customer_orders_page.dart';
import 'customer_prescriptions_page.dart';
import 'customer_profile_page.dart';

class CustomerDashboardScreen extends StatefulWidget {
  final String token;
  final int customerId;
  const CustomerDashboardScreen({
    super.key,
    required this.token,
    required this.customerId,
  });
  @override
  State<CustomerDashboardScreen> createState() =>
      _CustomerDashboardScreenState();
}

class _CustomerDashboardScreenState extends State<CustomerDashboardScreen> {
  int _selectedIndex = 0;
  final List<Map<String, dynamic>> _menuItems = [
    {'title': 'Dashboard', 'icon': Icons.dashboard, 'color': Color(0xFF0B6E6E)},
    {
      'title': 'Browse Medicines',
      'icon': Icons.medication,
      'color': Color(0xFF0B6E6E),
    },
    {'title': 'Cart', 'icon': Icons.shopping_cart, 'color': Color(0xFF0B6E6E)},
    {'title': 'Orders', 'icon': Icons.receipt, 'color': Color(0xFF0B6E6E)},
    {
      'title': 'Prescriptions',
      'icon': Icons.file_copy,
      'color': Color(0xFF0B6E6E),
    },
    {'title': 'Profile', 'icon': Icons.person, 'color': Color(0xFF0B6E6E)},
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF0b6e6e),
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          _menuItems[_selectedIndex]['title'],
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      drawer: _buildDrawer(),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF0b6e6e), Colors.white],
          ),
        ),
        child: _buildPage(),
      ),
    );
  }

  Widget _buildDrawer() {
    return Drawer(
      child: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E).withOpacity(0.8)],
          ),
        ),
        child: Column(
          children: [
            Container(
              padding: EdgeInsets.only(top: 50, bottom: 20),
              child: Column(
                children: [
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(40),
                    ),
                    child: Icon(
                      Icons.person,
                      size: 40,
                      color: Color(0xFF0B6E6E),
                    ),
                  ),
                  SizedBox(height: 16),
                  Text(
                    'Customer Portal',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    'Welcome back!',
                    style: TextStyle(color: Colors.white70, fontSize: 14),
                  ),
                ],
              ),
            ),
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: ListView.builder(
                  padding: EdgeInsets.only(top: 20),
                  itemCount: _menuItems.length + 1, // +1 for logout
                  itemBuilder: (context, index) {
                    if (index == _menuItems.length) {
                      // Logout item
                      return Container(
                        margin: EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.transparent,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: ListTile(
                          leading: Icon(
                            Icons.logout,
                            color: Colors.red[600],
                            size: 24,
                          ),
                          title: Text(
                            'Logout',
                            style: TextStyle(
                              color: Colors.red[600],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          onTap: () {
                            Navigator.of(
                              context,
                            ).pushReplacementNamed('/login');
                          },
                        ),
                      );
                    }

                    final item = _menuItems[index];
                    final isSelected = _selectedIndex == index;

                    return Container(
                      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? item['color'].withOpacity(0.1)
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(12),
                        border: isSelected
                            ? Border.all(color: item['color'], width: 2)
                            : null,
                      ),
                      child: ListTile(
                        leading: Icon(
                          item['icon'],
                          color: isSelected ? item['color'] : Colors.grey[600],
                          size: 24,
                        ),
                        title: Text(
                          item['title'],
                          style: TextStyle(
                            color: isSelected
                                ? item['color']
                                : Colors.grey[800],
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
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPage() {
    switch (_selectedIndex) {
      case 0:
        return CustomerDashboardPage(
          token: widget.token,
          customerId: widget.customerId,
        );
      case 1:
        return CustomerMedicinesPage(
          token: widget.token,
          customerId: widget.customerId,
        );
      case 2:
        return CustomerCartPage(
          token: widget.token,
          customerId: widget.customerId,
          onNavigateToPage: (index) {
            setState(() {
              _selectedIndex = index;
            });
          },
        );
      case 3:
        return CustomerOrdersPage(
          token: widget.token,
          customerId: widget.customerId,
        );
      case 4:
        return CustomerPrescriptionsPage(
          token: widget.token,
          customerId: widget.customerId,
        );
      case 5:
        return CustomerProfilePage(
          token: widget.token,
          customerId: widget.customerId,
        );
      default:
        return CustomerDashboardPage(
          token: widget.token,
          customerId: widget.customerId,
        );
    }
  }
}
