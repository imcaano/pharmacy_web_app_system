import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:open_file/open_file.dart';
import '../models/prescription.dart';
import '../services/api_service.dart';

class PharmacyPrescriptionsPage extends StatefulWidget {
  final String token;
  final int pharmacyId;
  const PharmacyPrescriptionsPage({
    super.key,
    required this.token,
    required this.pharmacyId,
  });
  @override
  State<PharmacyPrescriptionsPage> createState() =>
      _PharmacyPrescriptionsPageState();
}

class _PharmacyPrescriptionsPageState extends State<PharmacyPrescriptionsPage> {
  List<Prescription> _prescriptions = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchPrescriptions();
  }

  Future<void> _fetchPrescriptions() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final prescs = await ApiService.fetchPrescriptions();
      setState(() {
        _prescriptions = prescs;
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

  void _showPrescriptionDetails(Prescription prescription) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Prescription #${prescription.id}'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Customer ID: ${prescription.customerId}'),
            Text('Status: ${prescription.status}'),
            Text('File: ${prescription.fileUrl}'),
            Text('Date: ${prescription.createdAt.toString()}'),
            const SizedBox(height: 16),
            const Text(
              'File Information:',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            Text('File URL: ${prescription.fileUrl}'),
            Text(
              'File Type: ${prescription.fileUrl.split('.').last.toUpperCase()}',
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
              _viewPrescriptionFile(prescription);
            },
            child: const Text('View File'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _downloadPrescription(prescription);
            },
            child: const Text('Download'),
          ),
        ],
      ),
    );
  }

  void _downloadPrescription(Prescription prescription) async {
    try {
      // Show download progress
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Downloading prescription...'),
          backgroundColor: Color(0xFF0b6e6e),
        ),
      );

      // In a real app, you would download the file here
      // For now, we'll show a success message
      await Future.delayed(const Duration(seconds: 2));

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Prescription #${prescription.id} downloaded successfully!',
          ),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Download failed: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    }
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
              Text('Customer ID: ${prescription.customerId}'),
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
                _downloadPrescription(prescription);
              },
              child: const Text('Download'),
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

  Future<void> _acceptPrescription(Prescription prescription) async {
    try {
      // Show confirmation dialog
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Accept Prescription'),
          content: const Text(
            'Are you sure you want to accept this prescription? '
            'This will approve the prescription for processing.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx, true),
              style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
              child: const Text('Accept'),
            ),
          ],
        ),
      );

      if (confirmed != true) return;

      // Show loading
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Accepting prescription...'),
          backgroundColor: Color(0xFF0b6e6e),
        ),
      );

      // Call API to accept prescription
      final response = await ApiService.acceptPrescription(
        prescription.id,
        'accept',
      );

      if (response['success']) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Prescription accepted successfully!'),
            backgroundColor: Colors.green,
          ),
        );

        // Refresh the prescriptions list
        _fetchPrescriptions();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Error: ${response['error'] ?? 'Failed to accept prescription'}',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _rejectPrescription(Prescription prescription) async {
    try {
      // Show rejection reason dialog
      final reason = await showDialog<String>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Reject Prescription'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Please provide a reason for rejecting this prescription:',
              ),
              const SizedBox(height: 16),
              TextField(
                decoration: const InputDecoration(
                  hintText: 'Enter rejection reason...',
                  border: OutlineInputBorder(),
                ),
                maxLines: 3,
                onChanged: (value) {
                  // Store the value for later use
                  Navigator.pop(ctx, value);
                },
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () =>
                  Navigator.pop(ctx, 'Prescription rejected by pharmacy'),
              style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
              child: const Text('Reject'),
            ),
          ],
        ),
      );

      if (reason == null) return;

      // Show loading
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Rejecting prescription...'),
          backgroundColor: Color(0xFF0b6e6e),
        ),
      );

      // Call API to reject prescription
      final response = await ApiService.acceptPrescription(
        prescription.id,
        'reject',
        notes: reason,
      );

      if (response['success']) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Prescription rejected successfully!'),
            backgroundColor: Colors.red,
          ),
        );

        // Refresh the prescriptions list
        _fetchPrescriptions();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Error: ${response['error'] ?? 'Failed to reject prescription'}',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    }
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

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _fetchPrescriptions,
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
                          'Customer Prescriptions',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          '${_prescriptions.length} prescriptions found',
                          style: const TextStyle(
                            fontSize: 16,
                            color: Colors.white70,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Prescriptions List
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

  Widget _buildPrescriptionCard(Prescription prescription) {
    return Card(
      elevation: 4,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(
                    color: _getStatusColor(
                      prescription.status,
                    ).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(25),
                  ),
                  child: Icon(
                    Icons.file_copy,
                    color: _getStatusColor(prescription.status),
                    size: 24,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Prescription #${prescription.id}',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Customer ID: ${prescription.customerId}',
                        style: TextStyle(fontSize: 14, color: Colors.grey[600]),
                      ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  icon: const Icon(Icons.more_vert),
                  onSelected: (value) {
                    if (value == 'details') {
                      _showPrescriptionDetails(prescription);
                    } else if (value == 'download') {
                      _downloadPrescription(prescription);
                    }
                  },
                  itemBuilder: (context) => [
                    const PopupMenuItem(
                      value: 'details',
                      child: Row(
                        children: [
                          Icon(Icons.visibility, size: 20),
                          SizedBox(width: 8),
                          Text('View Details'),
                        ],
                      ),
                    ),
                    const PopupMenuItem(
                      value: 'download',
                      child: Row(
                        children: [
                          Icon(Icons.download, size: 20),
                          SizedBox(width: 8),
                          Text('Download'),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildInfoItem(
                    'Status',
                    prescription.status.toUpperCase(),
                    Icons.circle,
                    color: _getStatusColor(prescription.status),
                  ),
                ),
                Expanded(
                  child: _buildInfoItem(
                    'Date',
                    '${prescription.createdAt.day}/${prescription.createdAt.month}/${prescription.createdAt.year}',
                    Icons.calendar_today,
                  ),
                ),
                Expanded(
                  child: _buildInfoItem('File', 'PDF', Icons.picture_as_pdf),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.visibility),
                    label: const Text('View Details'),
                    onPressed: () => _showPrescriptionDetails(prescription),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.download),
                    label: const Text('Download'),
                    onPressed: () => _downloadPrescription(prescription),
                  ),
                ),
              ],
            ),

            // Accept/Reject buttons for pending prescriptions
            if (prescription.status.toLowerCase() == 'pending') ...[
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      icon: const Icon(Icons.check, color: Colors.white),
                      label: const Text(
                        'Accept',
                        style: TextStyle(color: Colors.white),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      onPressed: () => _acceptPrescription(prescription),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton.icon(
                      icon: const Icon(Icons.close, color: Colors.white),
                      label: const Text(
                        'Reject',
                        style: TextStyle(color: Colors.white),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.red,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      onPressed: () => _rejectPrescription(prescription),
                    ),
                  ),
                ],
              ),
            ] else ...[
              const SizedBox(height: 16),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(
                  vertical: 12,
                  horizontal: 16,
                ),
                decoration: BoxDecoration(
                  color: prescription.status.toLowerCase() == 'verified'
                      ? Colors.green.withOpacity(0.1)
                      : Colors.red.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(
                    color: prescription.status.toLowerCase() == 'verified'
                        ? Colors.green
                        : Colors.red,
                    width: 1,
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      prescription.status.toLowerCase() == 'verified'
                          ? Icons.check_circle
                          : Icons.cancel,
                      color: prescription.status.toLowerCase() == 'verified'
                          ? Colors.green
                          : Colors.red,
                      size: 20,
                    ),
                    const SizedBox(width: 8),
                    Text(
                      prescription.status.toLowerCase() == 'verified'
                          ? 'Prescription Accepted'
                          : 'Prescription Rejected',
                      style: TextStyle(
                        color: prescription.status.toLowerCase() == 'verified'
                            ? Colors.green
                            : Colors.red,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildInfoItem(
    String label,
    String value,
    IconData icon, {
    Color? color,
  }) {
    return Column(
      children: [
        Icon(icon, color: color ?? const Color(0xFF0b6e6e), size: 20),
        const SizedBox(height: 4),
        Text(label, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
        Text(
          value,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: color ?? const Color(0xFF0b6e6e),
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyState() {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        padding: const EdgeInsets.all(40),
        child: Column(
          children: [
            Icon(Icons.file_copy_outlined, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No prescriptions yet',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Customer prescriptions will appear here when they upload them',
              style: TextStyle(fontSize: 14, color: Colors.grey[500]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
