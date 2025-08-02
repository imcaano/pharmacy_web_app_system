import 'package:flutter/material.dart';
import '../models/user.dart';
import '../widgets/pharmacy_drawer.dart';
import 'pharmacy_dashboard_page.dart';
import 'pharmacy_medicines_page.dart';
import 'pharmacy_orders_page.dart';
import 'pharmacy_prescriptions_page.dart';
import 'pharmacy_profile_page.dart';

class PharmacyDashboardScreen extends StatefulWidget {
  final String token;
  final int pharmacyId;
  final String userEmail; // Add user email
  const PharmacyDashboardScreen({
    super.key,
    required this.token,
    required this.pharmacyId,
    required this.userEmail, // Add user email parameter
  });
  @override
  State<PharmacyDashboardScreen> createState() =>
      _PharmacyDashboardScreenState();
}

class _PharmacyDashboardScreenState extends State<PharmacyDashboardScreen> {
  int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF0b6e6e),
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Pharmacy Dashboard',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        ),
      ),
      drawer: PharmacyDrawer(
        pharmacyName: 'My Pharmacy',
        onLogout: () {
          Navigator.of(context).pushReplacementNamed('/login');
        },
        onPageChanged: (index) {
          setState(() {
            _selectedIndex = index;
          });
        },
        selectedIndex: _selectedIndex,
      ),
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

  Widget _buildPage() {
    switch (_selectedIndex) {
      case 0:
        return PharmacyDashboardPage(
          token: widget.token,
          pharmacyId: widget.pharmacyId,
        );
      case 1:
        return PharmacyMedicinesPage(
          token: widget.token,
          pharmacyId: widget.pharmacyId,
        );
      case 2:
        return PharmacyOrdersPage(
          token: widget.token,
          pharmacyId: widget.pharmacyId,
        );
      case 3:
        return PharmacyPrescriptionsPage(
          token: widget.token,
          pharmacyId: widget.pharmacyId,
        );
      case 4:
        return PharmacyProfilePage(
          token: widget.token,
          pharmacyId: widget.pharmacyId,
          userEmail: widget.userEmail,
        );
      default:
        return PharmacyDashboardPage(
          token: widget.token,
          pharmacyId: widget.pharmacyId,
        );
    }
  }
}
