import 'package:flutter/material.dart';
import '../models/medicine.dart';
import '../services/api_service.dart';

class PharmacyMedicinesPage extends StatefulWidget {
  final String token;
  final int pharmacyId;

  const PharmacyMedicinesPage({
    super.key,
    required this.token,
    required this.pharmacyId,
  });

  @override
  State<PharmacyMedicinesPage> createState() => _PharmacyMedicinesPageState();
}

class _PharmacyMedicinesPageState extends State<PharmacyMedicinesPage> {
  List<Medicine> _medicines = [];
  bool _loading = true;
  String? _error;
  String _searchQuery = '';
  String _filterCategory = 'All';
  String _sortBy = 'Name';

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
      final meds = await ApiService.fetchMedicinesByPharmacy(widget.pharmacyId);
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

  List<Medicine> get _filteredAndSortedMedicines {
    var filtered = _medicines.where((medicine) {
      final matchesSearch = medicine.name.toLowerCase().contains(_searchQuery.toLowerCase()) ||
          medicine.description.toLowerCase().contains(_searchQuery.toLowerCase());
      final matchesCategory = _filterCategory == 'All' || medicine.category == _filterCategory;
      return matchesSearch && matchesCategory;
    }).toList();

    // Sort medicines
    switch (_sortBy) {
      case 'Name':
        filtered.sort((a, b) => a.name.compareTo(b.name));
        break;
      case 'Price (Low to High)':
        filtered.sort((a, b) => a.price.compareTo(b.price));
        break;
      case 'Price (High to Low)':
        filtered.sort((a, b) => b.price.compareTo(a.price));
        break;
      case 'Stock (High to Low)':
        filtered.sort((a, b) => b.stockQuantity.compareTo(a.stockQuantity));
        break;
      case 'Stock (Low to High)':
        filtered.sort((a, b) => a.stockQuantity.compareTo(b.stockQuantity));
        break;
    }

    return filtered;
  }

  List<String> get _categories {
    final categories = _medicines.map((m) => m.category).where((c) => c != null).map((c) => c!).toSet().toList();
    categories.sort();
    return ['All', ...categories];
  }

  List<String> get _sortOptions => [
    'Name',
    'Price (Low to High)',
    'Price (High to Low)',
    'Stock (High to Low)',
    'Stock (Low to High)',
  ];

  void _showMedicineDialog({Medicine? med}) {
    final nameController = TextEditingController(text: med?.name ?? '');
    final descController = TextEditingController(text: med?.description ?? '');
    final priceController = TextEditingController(
      text: med?.price.toString() ?? '',
    );
    final stockController = TextEditingController(
      text: med?.stockQuantity.toString() ?? '',
    );
    final categoryController = TextEditingController(text: med?.category ?? '');

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(med == null ? 'Add Medicine' : 'Edit Medicine'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameController,
                decoration: const InputDecoration(
                  labelText: 'Name',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: descController,
                decoration: const InputDecoration(
                  labelText: 'Description',
                  border: OutlineInputBorder(),
                ),
                maxLines: 3,
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: priceController,
                      decoration: const InputDecoration(
                        labelText: 'Price',
                        border: OutlineInputBorder(),
                        prefixText: '\$',
                      ),
                      keyboardType: TextInputType.number,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: TextField(
                      controller: stockController,
                      decoration: const InputDecoration(
                        labelText: 'Stock',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              TextField(
                controller: categoryController,
                decoration: const InputDecoration(
                  labelText: 'Category',
                  border: OutlineInputBorder(),
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              final medicine = Medicine(
                id: med?.id ?? 0,
                name: nameController.text,
                description: descController.text,
                price: double.tryParse(priceController.text) ?? 0,
                stockQuantity: int.tryParse(stockController.text) ?? 0,
                pharmacyId: widget.pharmacyId,
                category: categoryController.text,
              );
              try {
                if (med == null) {
                  await ApiService.createMedicine(medicine);
                } else {
                  await ApiService.updateMedicine(medicine);
                }
                Navigator.pop(ctx);
                _fetchMedicines();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(med == null ? 'Medicine added successfully!' : 'Medicine updated successfully!'),
                    backgroundColor: Colors.green,
                  ),
                );
              } catch (e) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Error: ${e.toString()}'),
                    backgroundColor: Colors.red,
                  ),
                );
              }
            },
            child: Text(med == null ? 'Add' : 'Save'),
          ),
        ],
      ),
    );
  }

  void _deleteMedicine(Medicine med) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Medicine'),
        content: Text('Are you sure you want to delete "${med.name}"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        await ApiService.deleteMedicine(med.id);
        _fetchMedicines();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Medicine deleted successfully!'),
            backgroundColor: Colors.green,
          ),
        );
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      body: Column(
        children: [
          // Header with gradient background
          Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF0B6E6E), Color(0xFF0A5A5A)],
              ),
              borderRadius: BorderRadius.only(
                bottomLeft: Radius.circular(24),
                bottomRight: Radius.circular(24),
              ),
            ),
            child: SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    // Title and Add Button
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'My Medicines',
                                style: TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${_medicines.length} medicines in inventory',
                                style: const TextStyle(
                                  fontSize: 16,
                                  color: Colors.white70,
                                ),
                              ),
                            ],
                          ),
                        ),
                        ElevatedButton.icon(
                          icon: const Icon(Icons.add),
                          label: const Text('Add'),
                          onPressed: () => _showMedicineDialog(),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: const Color(0xFF0B6E6E),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    
                    // Search Bar
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.1),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: TextField(
                        onChanged: (value) => setState(() => _searchQuery = value),
                        decoration: const InputDecoration(
                          hintText: 'Search medicines...',
                          prefixIcon: Icon(Icons.search),
                          border: InputBorder.none,
                          contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        ),
                      ),
                    ),
                    
                    const SizedBox(height: 16),
                    
                    // Filters Row
                    Row(
                      children: [
                        // Category Filter
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<String>(
                                value: _filterCategory,
                                isExpanded: true,
                                dropdownColor: const Color(0xFF0B6E6E),
                                style: const TextStyle(color: Colors.white),
                                items: _categories.map((category) {
                                  return DropdownMenuItem(
                                    value: category,
                                    child: Text(
                                      category,
                                      style: const TextStyle(color: Colors.white),
                                    ),
                                  );
                                }).toList(),
                                onChanged: (value) {
                                  setState(() {
                                    _filterCategory = value!;
                                  });
                                },
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        // Sort Filter
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<String>(
                                value: _sortBy,
                                isExpanded: true,
                                dropdownColor: const Color(0xFF0B6E6E),
                                style: const TextStyle(color: Colors.white),
                                items: _sortOptions.map((option) {
                                  return DropdownMenuItem(
                                    value: option,
                                    child: Text(
                                      option,
                                      style: const TextStyle(color: Colors.white),
                                    ),
                                  );
                                }).toList(),
                                onChanged: (value) {
                                  setState(() {
                                    _sortBy = value!;
                                  });
                                },
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Content
          Expanded(
            child: _loading
                ? const Center(
                    child: CircularProgressIndicator(
                      color: Color(0xFF0B6E6E),
                    ),
                  )
                : _error != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.error_outline,
                              size: 64,
                              color: Colors.red[300],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              'Error loading medicines',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Colors.grey[700],
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              _error!,
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.grey[600],
                              ),
                              textAlign: TextAlign.center,
                            ),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _fetchMedicines,
                              child: const Text('Retry'),
                            ),
                          ],
                        ),
                      )
                    : _filteredAndSortedMedicines.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  Icons.medication_outlined,
                                  size: 80,
                                  color: Colors.grey[400],
                                ),
                                const SizedBox(height: 16),
                                Text(
                                  _searchQuery.isNotEmpty || _filterCategory != 'All'
                                      ? 'No medicines found'
                                      : 'No medicines in inventory',
                                  style: TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.grey[600],
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  _searchQuery.isNotEmpty || _filterCategory != 'All'
                                      ? 'Try adjusting your search or filters'
                                      : 'Add your first medicine to get started',
                                  style: TextStyle(
                                    fontSize: 16,
                                    color: Colors.grey[500],
                                  ),
                                ),
                              ],
                            ),
                          )
                        : GridView.builder(
                            padding: const EdgeInsets.all(16),
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              crossAxisSpacing: 16,
                              mainAxisSpacing: 16,
                              childAspectRatio: 0.8,
                            ),
                            itemCount: _filteredAndSortedMedicines.length,
                            itemBuilder: (context, index) {
                              final medicine = _filteredAndSortedMedicines[index];
                              return Container(
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
                                  children: [
                                    // Header with category badge
                                    Container(
                                      width: double.infinity,
                                      padding: const EdgeInsets.all(12),
                                      decoration: const BoxDecoration(
                                        color: Color(0xFF0B6E6E),
                                        borderRadius: BorderRadius.only(
                                          topLeft: Radius.circular(16),
                                          topRight: Radius.circular(16),
                                        ),
                                      ),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            medicine.name,
                                            style: const TextStyle(
                                              fontSize: 14,
                                              fontWeight: FontWeight.bold,
                                              color: Colors.white,
                                            ),
                                            maxLines: 2,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                          if (medicine.category != null) ...[
                                            const SizedBox(height: 4),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: Colors.white.withOpacity(0.2),
                                                borderRadius: BorderRadius.circular(8),
                                              ),
                                              child: Text(
                                                medicine.category!,
                                                style: const TextStyle(
                                                  fontSize: 10,
                                                  color: Colors.white,
                                                  fontWeight: FontWeight.w500,
                                                ),
                                              ),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                    
                                    // Content
                                    Expanded(
                                      child: Padding(
                                        padding: const EdgeInsets.all(12),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            if (medicine.description.isNotEmpty) ...[
                                              Text(
                                                medicine.description,
                                                style: TextStyle(
                                                  fontSize: 11,
                                                  color: Colors.grey[600],
                                                ),
                                                maxLines: 2,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                              const SizedBox(height: 8),
                                            ],
                                            
                                            const Spacer(),
                                            
                                            // Price and Stock
                                            Row(
                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                              children: [
                                                Text(
                                                  '\$${medicine.price.toStringAsFixed(2)}',
                                                  style: const TextStyle(
                                                    fontSize: 16,
                                                    fontWeight: FontWeight.bold,
                                                    color: Color(0xFF0B6E6E),
                                                  ),
                                                ),
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                  decoration: BoxDecoration(
                                                    color: medicine.stockQuantity > 10 
                                                        ? Colors.green.withOpacity(0.1)
                                                        : Colors.orange.withOpacity(0.1),
                                                    borderRadius: BorderRadius.circular(8),
                                                  ),
                                                  child: Text(
                                                    '${medicine.stockQuantity}',
                                                    style: TextStyle(
                                                      fontSize: 10,
                                                      color: medicine.stockQuantity > 10 
                                                          ? Colors.green[700]
                                                          : Colors.orange[700],
                                                      fontWeight: FontWeight.bold,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                            
                                            const SizedBox(height: 8),
                                            
                                            // Action Buttons
                                            Row(
                                              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                                              children: [
                                                Expanded(
                                                  child: IconButton(
                                                    icon: const Icon(
                                                      Icons.edit,
                                                      color: Color(0xFF0B6E6E),
                                                      size: 18,
                                                    ),
                                                    onPressed: () => _showMedicineDialog(med: medicine),
                                                    padding: EdgeInsets.zero,
                                                    constraints: const BoxConstraints(
                                                      minWidth: 32,
                                                      minHeight: 32,
                                                    ),
                                                  ),
                                                ),
                                                Expanded(
                                                  child: IconButton(
                                                    icon: const Icon(
                                                      Icons.delete,
                                                      color: Colors.red,
                                                      size: 18,
                                                    ),
                                                    onPressed: () => _deleteMedicine(medicine),
                                                    padding: EdgeInsets.zero,
                                                    constraints: const BoxConstraints(
                                                      minWidth: 32,
                                                      minHeight: 32,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
          ),
        ],
      ),
    );
  }
}
