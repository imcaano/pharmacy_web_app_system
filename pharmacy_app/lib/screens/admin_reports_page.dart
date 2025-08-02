import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/order.dart';
import '../services/api_service.dart';

class AdminReportsPage extends StatefulWidget {
  final String token;
  const AdminReportsPage({super.key, required this.token});
  @override
  State<AdminReportsPage> createState() => _AdminReportsPageState();
}

class _AdminReportsPageState extends State<AdminReportsPage> {
  List<Order> _orders = [];
  bool _loading = true;
  String? _error;
  String _selectedReportType = 'Sales Report';
  DateTime _startDate = DateTime.now().subtract(const Duration(days: 30));
  DateTime _endDate = DateTime.now();
  String _selectedPharmacy = 'All Pharmacies';
  List<Map<String, dynamic>> _reportData = [];
  List<String> _pharmacies = ['All Pharmacies'];

  final List<String> _reportTypes = [
    'Sales Report',
    'Inventory Report',
    'User Activity Report',
    'Revenue Report',
    'Order Status Report',
  ];

  @override
  void initState() {
    super.initState();
    _fetchOrders();
    _loadReportData();
  }

  Future<void> _fetchOrders() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final orders = await ApiService.fetchOrders(widget.token);
      setState(() {
        _orders = orders;
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

  Future<void> _loadReportData() async {
    // Generate sample data based on report type
    _reportData = _generateSampleData();
  }

  List<Map<String, dynamic>> _generateSampleData() {
    switch (_selectedReportType) {
      case 'Sales Report':
        return [
          {
            'date': '2024-01-01',
            'pharmacy': 'Shaafi Pharmacy',
            'sales': 1250.50,
            'orders': 15,
          },
          {
            'date': '2024-01-02',
            'pharmacy': 'Kalkaal Hospital',
            'sales': 890.75,
            'orders': 12,
          },
          {
            'date': '2024-01-03',
            'pharmacy': 'Shaafi Pharmacy',
            'sales': 2100.25,
            'orders': 25,
          },
          {
            'date': '2024-01-04',
            'pharmacy': 'Kalkaal Hospital',
            'sales': 1567.80,
            'orders': 18,
          },
          {
            'date': '2024-01-05',
            'pharmacy': 'Shaafi Pharmacy',
            'sales': 980.30,
            'orders': 11,
          },
        ];
      case 'Inventory Report':
        return [
          {
            'medicine': 'Paracetamol',
            'pharmacy': 'Shaafi Pharmacy',
            'stock': 150,
            'category': 'Pain Relief',
          },
          {
            'medicine': 'Amoxicillin',
            'pharmacy': 'Kalkaal Hospital',
            'stock': 75,
            'category': 'Antibiotic',
          },
          {
            'medicine': 'Ibuprofen',
            'pharmacy': 'Shaafi Pharmacy',
            'stock': 200,
            'category': 'Pain Relief',
          },
          {
            'medicine': 'Omeprazole',
            'pharmacy': 'Kalkaal Hospital',
            'stock': 45,
            'category': 'Gastric',
          },
          {
            'medicine': 'Cetirizine',
            'pharmacy': 'Shaafi Pharmacy',
            'stock': 120,
            'category': 'Allergy',
          },
        ];
      case 'User Activity Report':
        return [
          {
            'user': 'John Doe',
            'role': 'Customer',
            'lastLogin': '2024-01-05 14:30',
            'status': 'Active',
          },
          {
            'user': 'Jane Smith',
            'role': 'Pharmacy',
            'lastLogin': '2024-01-05 12:15',
            'status': 'Active',
          },
          {
            'user': 'Mike Johnson',
            'role': 'Customer',
            'lastLogin': '2024-01-04 09:45',
            'status': 'Inactive',
          },
          {
            'user': 'Sarah Wilson',
            'role': 'Pharmacy',
            'lastLogin': '2024-01-05 16:20',
            'status': 'Active',
          },
          {
            'user': 'David Brown',
            'role': 'Customer',
            'lastLogin': '2024-01-03 11:30',
            'status': 'Active',
          },
        ];
      case 'Revenue Report':
        return [
          {'month': 'January', 'revenue': 45000.00, 'growth': 12.5},
          {'month': 'February', 'revenue': 52000.00, 'growth': 15.6},
          {'month': 'March', 'revenue': 48000.00, 'growth': -7.7},
          {'month': 'April', 'revenue': 61000.00, 'growth': 27.1},
          {'month': 'May', 'revenue': 55000.00, 'growth': -9.8},
        ];
      case 'Order Status Report':
        return [
          {'status': 'Pending', 'count': 25, 'percentage': 20.0},
          {'status': 'Processing', 'count': 35, 'percentage': 28.0},
          {'status': 'Shipped', 'count': 40, 'percentage': 32.0},
          {'status': 'Delivered', 'count': 20, 'percentage': 16.0},
          {'status': 'Cancelled', 'count': 5, 'percentage': 4.0},
        ];
      default:
        return [];
    }
  }

  void _showDateRangePicker() async {
    final DateTimeRange? picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: DateTimeRange(start: _startDate, end: _endDate),
    );

    if (picked != null) {
      setState(() {
        _startDate = picked.start;
        _endDate = picked.end;
      });
      _loadReportData();
    }
  }

  void _exportToPDF() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Export Report'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.picture_as_pdf),
              title: const Text('Export as PDF'),
              onTap: () {
                Navigator.pop(context);
                _performPDFExport();
              },
            ),
            ListTile(
              leading: const Icon(Icons.table_chart),
              title: const Text('Export as Excel'),
              onTap: () {
                Navigator.pop(context);
                _performExcelExport();
              },
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
        ],
      ),
    );
  }

  void _performPDFExport() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('PDF export started...'),
        backgroundColor: Colors.green,
      ),
    );
  }

  void _performExcelExport() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Excel export started...'),
        backgroundColor: Colors.green,
      ),
    );
  }

  void _generateReport() {
    // Placeholder for generating report
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Generate new report'),
        backgroundColor: Color(0xFF0B6E6E),
      ),
    );
  }

  void _viewReport(String reportId) {
    // Placeholder for viewing report
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('View report $reportId'),
        backgroundColor: const Color(0xFF0B6E6E),
      ),
    );
  }

  void _exportReport(String reportId) {
    // Placeholder for downloading report
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Download report $reportId'),
        backgroundColor: const Color(0xFF0B6E6E),
      ),
    );
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
              'Error loading reports',
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

    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFF0B6E6E), Colors.white],
        ),
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(24),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.only(
                bottomLeft: Radius.circular(24),
                bottomRight: Radius.circular(24),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Reports Management',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      Text(
                        '${_orders.length} total reports',
                        style: const TextStyle(
                          fontSize: 14,
                          color: Colors.white70,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                ElevatedButton.icon(
                  icon: const Icon(Icons.add, size: 16),
                  label: const Text('Generate', style: TextStyle(fontSize: 12)),
                  onPressed: () => _generateReport(),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFF0B6E6E),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 8,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Reports Table
          Expanded(
            child: _orders.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.assessment_outlined,
                          size: 80,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'No reports found',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Generate your first report to get started',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  )
                : Container(
                    margin: const EdgeInsets.symmetric(horizontal: 16),
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
                    child: SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: DataTable(
                        columnSpacing: 32,
                        headingRowColor: MaterialStateProperty.all(
                          const Color(0xFF0B6E6E).withOpacity(0.1),
                        ),
                        columns: const [
                          DataColumn(
                            label: Text(
                              'ID',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Type',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Generated',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Status',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                          DataColumn(
                            label: Text(
                              'Actions',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0B6E6E),
                              ),
                            ),
                          ),
                        ],
                        rows: _orders.map((order) {
                          return DataRow(
                            cells: [
                              DataCell(Text('#${order.id}')),
                              DataCell(Text(order.status)),
                              DataCell(Text(order.createdAt.toString())),
                              DataCell(
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 4,
                                  ),
                                  decoration: BoxDecoration(
                                    color: Colors.green.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: const Text(
                                    'COMPLETED',
                                    style: TextStyle(
                                      color: Colors.green,
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ),
                              DataCell(
                                Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      icon: const Icon(
                                        Icons.visibility,
                                        color: Color(0xFF0B6E6E),
                                        size: 16,
                                      ),
                                      onPressed: () =>
                                          _viewReport(order.id.toString()),
                                      padding: EdgeInsets.zero,
                                      constraints: const BoxConstraints(
                                        minWidth: 24,
                                        minHeight: 24,
                                      ),
                                    ),
                                    IconButton(
                                      icon: const Icon(
                                        Icons.download,
                                        color: Color(0xFF0B6E6E),
                                        size: 16,
                                      ),
                                      onPressed: () =>
                                          _exportReport(order.id.toString()),
                                      padding: EdgeInsets.zero,
                                      constraints: const BoxConstraints(
                                        minWidth: 24,
                                        minHeight: 24,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          );
                        }).toList(),
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}
