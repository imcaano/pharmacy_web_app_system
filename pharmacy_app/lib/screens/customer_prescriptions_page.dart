import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:open_file/open_file.dart';
import '../models/prescription.dart';
import '../services/api_service.dart';

class CustomerPrescriptionsPage extends StatefulWidget {
  final String token;
  final int customerId;

  const CustomerPrescriptionsPage({
    Key? key,
    required this.token,
    required this.customerId,
  }) : super(key: key);

  @override
  State<CustomerPrescriptionsPage> createState() =>
      _CustomerPrescriptionsPageState();
}

class _CustomerPrescriptionsPageState extends State<CustomerPrescriptionsPage> {
  List<Prescription> _prescriptions = [];
  bool _loading = true;
  bool _uploading = false;
  String? _error;
  String? _success;

  @override
  void initState() {
    super.initState();
    _loadPrescriptions();
  }

  Future<void> _loadPrescriptions() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    // Simulate API delay
    await Future.delayed(const Duration(milliseconds: 1000));

    // Mock data for customer prescriptions
    setState(() {
      _prescriptions = [
        Prescription(
          id: 1,
          customerId: widget.customerId,
          fileUrl: 'uploads/prescriptions/presc_1234567890.pdf',
          status: 'pending',
          createdAt: DateTime.now().subtract(const Duration(hours: 2)),
        ),
        Prescription(
          id: 2,
          customerId: widget.customerId,
          fileUrl: 'uploads/prescriptions/presc_1234567891.jpg',
          status: 'approved',
          createdAt: DateTime.now().subtract(const Duration(hours: 4)),
        ),
        Prescription(
          id: 3,
          customerId: widget.customerId,
          fileUrl: 'uploads/prescriptions/presc_1234567892.png',
          status: 'rejected',
          createdAt: DateTime.now().subtract(const Duration(days: 1)),
        ),
      ];
      _loading = false;
    });
  }

  Future<void> _uploadPrescription() async {
    try {
      setState(() {
        _uploading = true;
        _error = null;
        _success = null;
      });

      // Pick file with support for images and PDFs
      final result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
        allowMultiple: false,
      );

      if (result != null && result.files.isNotEmpty) {
        final file = result.files.first;

        // Show file info to user
        showDialog(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('File Selected'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Name: ${file.name}'),
                Text('Size: ${(file.size / 1024).toStringAsFixed(2)} KB'),
                Text('Extension: ${file.extension}'),
                const SizedBox(height: 16),
                const Text(
                  'This file will be uploaded as your prescription. Make sure it\'s clear and readable.',
                  style: TextStyle(fontSize: 12, color: Colors.grey),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.pop(ctx);
                  setState(() {
                    _uploading = false;
                  });
                },
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () async {
                  Navigator.pop(ctx);

                  // Simulate upload delay
                  await Future.delayed(const Duration(seconds: 2));

                  // Create mock prescription record
                  final newPrescription = Prescription(
                    id: _prescriptions.length + 1,
                    customerId: widget.customerId,
                    fileUrl: 'uploads/prescriptions/${file.name}',
                    status: 'pending',
                    createdAt: DateTime.now(),
                  );

                  setState(() {
                    _prescriptions.add(newPrescription);
                    _success = 'Prescription uploaded successfully!';
                    _uploading = false;
                  });
                },
                child: const Text('Upload'),
              ),
            ],
          ),
        );
      } else {
        setState(() {
          _uploading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Failed to upload prescription: ${e.toString()}';
        _uploading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _loading
          ? const Center(child: CircularProgressIndicator())
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
                          'Upload and manage your prescriptions',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Upload your prescription to get medicines delivered',
                          style: TextStyle(fontSize: 16, color: Colors.white70),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Upload Button
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.1),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        const Text(
                          'Upload New Prescription',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF0b6e6e),
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          'Upload your prescription to get medicines delivered',
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey[600],
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        SizedBox(
                          width: double.infinity,
                          height: 50,
                          child: ElevatedButton.icon(
                            onPressed: _uploading ? null : _uploadPrescription,
                            icon: _uploading
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Icon(Icons.upload_file),
                            label: Text(
                              _uploading
                                  ? 'Uploading...'
                                  : 'Upload Prescription',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF0b6e6e),
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              elevation: 4,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Error/Success Messages
                  if (_error != null)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.red.shade200),
                      ),
                      child: Text(
                        _error!,
                        style: TextStyle(color: Colors.red.shade700),
                      ),
                    ),

                  if (_success != null)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.green.shade200),
                      ),
                      child: Text(
                        _success!,
                        style: TextStyle(color: Colors.green.shade700),
                      ),
                    ),

                  if (_error != null || _success != null)
                    const SizedBox(height: 20),

                  // Prescriptions List
                  const Text(
                    'My Prescriptions',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF0b6e6e),
                    ),
                  ),
                  const SizedBox(height: 16),

                  if (_prescriptions.isEmpty)
                    _buildEmptyState()
                  else
                    ..._prescriptions.map(
                      (prescription) => _buildPrescriptionCard(prescription),
                    ),
                ],
              ),
            ),
    );
  }

  Widget _buildEmptyState() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          children: [
            Icon(Icons.description_outlined, size: 80, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No prescriptions yet',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Upload your first prescription to get started',
              style: TextStyle(fontSize: 16, color: Colors.grey[500]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPrescriptionCard(Prescription prescription) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: _getStatusColor(prescription.status).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(
                Icons.description,
                color: _getStatusColor(prescription.status),
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Prescription #${prescription.id}',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Status: ${prescription.status.toUpperCase()}',
                    style: TextStyle(
                      color: _getStatusColor(prescription.status),
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    prescription.createdAt.toString().split(' ')[0],
                    style: TextStyle(color: Colors.grey[600], fontSize: 12),
                  ),
                ],
              ),
            ),
            PopupMenuButton<String>(
              onSelected: (value) {
                if (value == 'view') {
                  _showPrescriptionDetails(prescription);
                } else if (value == 'view_file') {
                  _viewPrescriptionFile(prescription);
                } else if (value == 'order') {
                  _createOrderFromPrescription(prescription);
                }
              },
              itemBuilder: (context) => [
                const PopupMenuItem(
                  value: 'view',
                  child: Row(
                    children: [
                      Icon(Icons.info),
                      SizedBox(width: 8),
                      Text('View Details'),
                    ],
                  ),
                ),
                const PopupMenuItem(
                  value: 'view_file',
                  child: Row(
                    children: [
                      Icon(Icons.visibility),
                      SizedBox(width: 8),
                      Text('View File'),
                    ],
                  ),
                ),
                const PopupMenuItem(
                  value: 'order',
                  child: Row(
                    children: [
                      Icon(Icons.shopping_cart),
                      SizedBox(width: 8),
                      Text('Create Order'),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return const Color(0xFF0b6e6e);
      case 'approved':
        return const Color(0xFF0b6e6e);
      case 'rejected':
        return Colors.red;
      default:
        return const Color(0xFF0b6e6e);
    }
  }

  void _showPrescriptionDetails(Prescription prescription) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Prescription Details'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('ID: ${prescription.id}'),
            Text('Status: ${prescription.status}'),
            Text('File: ${prescription.fileUrl}'),
            Text('Date: ${prescription.createdAt}'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  void _createOrderFromPrescription(Prescription prescription) {
    // TODO: Implement order creation from prescription
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Create Order'),
        content: Text('Create order from prescription #${prescription.id}?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              // TODO: Implement order creation
            },
            child: const Text('Order'),
          ),
        ],
      ),
    );
  }

  void _viewPrescriptionFile(Prescription prescription) async {
    try {
      // Show loading
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Opening prescription file...'),
          backgroundColor: Color(0xFF0b6e6e),
        ),
      );

      // For demo purposes, we'll show a dialog with file info
      // In a real app, you would open the actual file
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          title: Text('Prescription #${prescription.id}'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('File: ${prescription.fileUrl}'),
              Text('Status: ${prescription.status}'),
              Text('Date: ${prescription.createdAt.toString()}'),
              const SizedBox(height: 16),
              const Text(
                'File Preview:',
                style: TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                height: 200,
                decoration: BoxDecoration(
                  color: Colors.grey[100],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.grey[300]!),
                ),
                child: prescription.fileUrl.toLowerCase().contains('.pdf')
                    ? const Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.picture_as_pdf,
                              size: 48,
                              color: Color(0xFF0b6e6e),
                            ),
                            SizedBox(height: 8),
                            Text(
                              'PDF Document',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0b6e6e),
                              ),
                            ),
                          ],
                        ),
                      )
                    : const Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.image,
                              size: 48,
                              color: Color(0xFF0b6e6e),
                            ),
                            SizedBox(height: 8),
                            Text(
                              'Image File',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0b6e6e),
                              ),
                            ),
                          ],
                        ),
                      ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Close'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.pop(ctx);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('File opened successfully!'),
                    backgroundColor: Colors.green,
                  ),
                );
              },
              child: const Text('Open File'),
            ),
          ],
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error opening file: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }
}
