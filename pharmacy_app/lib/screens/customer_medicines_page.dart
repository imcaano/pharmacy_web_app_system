import 'package:flutter/material.dart';
import '../models/medicine.dart';
import '../services/api_service.dart';

class CustomerMedicinesPage extends StatefulWidget {
  final String token;
  final int customerId;
  const CustomerMedicinesPage({
    super.key,
    required this.token,
    required this.customerId,
  });
  @override
  State<CustomerMedicinesPage> createState() => _CustomerMedicinesPageState();
}

class _CustomerMedicinesPageState extends State<CustomerMedicinesPage> {
  List<Medicine> _medicines = [];
  bool _loading = true;
  String? _error;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _fetchMedicines();
  }

  Future<void> _fetchMedicines() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      // Use fetchAllMedicines for customers to see all medicines from all pharmacies
      final meds = await ApiService.fetchAllMedicines();
      setState(() {
        _medicines = meds;
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

  List<Medicine> get _filteredMedicines {
    if (_searchQuery.isEmpty) return _medicines;
    return _medicines
        .where(
          (medicine) =>
              medicine.name.toLowerCase().contains(
                _searchQuery.toLowerCase(),
              ) ||
              medicine.description.toLowerCase().contains(
                _searchQuery.toLowerCase(),
              ),
        )
        .toList();
  }

  void _addToCart(Medicine medicine) async {
    try {
      // Use customer ID from the widget
      final customerId = widget.customerId;

      await ApiService.addToCart(customerId, medicine.id, 1);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${medicine.name} added to cart!'),
          backgroundColor: const Color(0xFF0b6e6e),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error adding to cart: ${e.toString()}'),
          backgroundColor: Colors.red,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    }
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
                    'Error loading medicines',
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
              onRefresh: _fetchMedicines,
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
                          colors: [Color(0xFF0b6e6e), Color(0xFF0b6e6e)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 60,
                            height: 60,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(30),
                            ),
                            child: const Icon(
                              Icons.medication,
                              color: Color(0xFF0b6e6e),
                              size: 30,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Available Medicines',
                                  style: TextStyle(
                                    fontSize: 24,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                  ),
                                ),
                                const Text(
                                  'Find and order your medicines',
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
                    ),
                    const SizedBox(height: 20),

                    // Search Bar
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.grey.withOpacity(0.1),
                            spreadRadius: 1,
                            blurRadius: 4,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: TextField(
                        onChanged: (value) {
                          setState(() {
                            _searchQuery = value;
                          });
                        },
                        decoration: const InputDecoration(
                          hintText: 'Search medicines...',
                          border: InputBorder.none,
                          icon: Icon(Icons.search),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Medicines Grid
                    Text(
                      'Available Medicines (${_filteredMedicines.length})',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF0b6e6e),
                      ),
                    ),
                    const SizedBox(height: 16),

                    if (_filteredMedicines.isEmpty)
                      _buildEmptyState()
                    else
                      GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              crossAxisSpacing: 12,
                              mainAxisSpacing: 12,
                              childAspectRatio: 0.8,
                            ),
                        itemCount: _filteredMedicines.length,
                        itemBuilder: (context, index) {
                          return _buildMedicineCard(_filteredMedicines[index]);
                        },
                      ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildMedicineCard(Medicine medicine) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Medicine Icon
          Container(
            width: double.infinity,
            height: 80,
            decoration: const BoxDecoration(
              color: Color(0xFF0b6e6e),
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(12),
                topRight: Radius.circular(12),
              ),
            ),
            child: const Icon(Icons.medication, color: Colors.white, size: 40),
          ),

          // Medicine Details
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    medicine.name.toUpperCase(),
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Stock: ${medicine.stockQuantity}',
                    style: TextStyle(fontSize: 8, color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '\$${medicine.price.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF0b6e6e),
                    ),
                  ),
                  const Spacer(),
                  SizedBox(
                    width: double.infinity,
                    height: 25,
                    child: ElevatedButton(
                      onPressed: () => _addToCart(medicine),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0b6e6e),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(4),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 1),
                      ),
                      child: const Text('Add', style: TextStyle(fontSize: 8)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          children: [
            Icon(Icons.medication_outlined, size: 80, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No medicines found',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Try adjusting your search criteria',
              style: TextStyle(fontSize: 16, color: Colors.grey[500]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
