import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/api_service.dart';

class AdminPharmaciesPage extends StatefulWidget {
  final String token;
  const AdminPharmaciesPage({super.key, required this.token});
  @override
  State<AdminPharmaciesPage> createState() => _AdminPharmaciesPageState();
}

class _AdminPharmaciesPageState extends State<AdminPharmaciesPage> {
  List<User> _pharmacies = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchPharmacies();
  }

  Future<void> _fetchPharmacies() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final users = await ApiService.fetchUsers(widget.token);
      setState(() {
        _pharmacies = users.where((u) => u.userType == 'pharmacy').toList();
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

  void _showPharmacyDialog({User? pharmacy}) {
    final emailController = TextEditingController(text: pharmacy?.email ?? '');
    final metamaskController = TextEditingController(
      text: pharmacy?.metamaskAddress ?? '',
    );
    String password = '';
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(pharmacy == null ? 'Add Pharmacy' : 'Edit Pharmacy'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: emailController,
              decoration: const InputDecoration(labelText: 'Email'),
            ),
            TextField(
              controller: metamaskController,
              decoration: const InputDecoration(labelText: 'MetaMask Address'),
            ),
            if (pharmacy == null)
              TextField(
                decoration: const InputDecoration(labelText: 'Password'),
                obscureText: true,
                onChanged: (v) => password = v,
              ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              if (pharmacy == null) {
                // Add
                await ApiService.createUser(
                  widget.token,
                  User(
                    id: 0,
                    email: emailController.text,
                    userType: 'pharmacy',
                    metamaskAddress: metamaskController.text,
                  ),
                  password,
                );
              } else {
                // Edit
                await ApiService.updateUser(
                  widget.token,
                  User(
                    id: pharmacy.id,
                    email: emailController.text,
                    userType: 'pharmacy',
                    metamaskAddress: metamaskController.text,
                  ),
                );
              }
              Navigator.pop(ctx);
              _fetchPharmacies();
            },
            child: Text(pharmacy == null ? 'Add' : 'Save'),
          ),
        ],
      ),
    );
  }

  void _deletePharmacy(User pharmacy) async {
    await ApiService.deleteUser(widget.token, pharmacy.id);
    _fetchPharmacies();
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
              'Error loading pharmacies',
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

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Pharmacies Management',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      Text(
                        '${_pharmacies.length} total pharmacies',
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
                  label: const Text('Add', style: TextStyle(fontSize: 12)),
                  onPressed: () => _showPharmacyDialog(),
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

          // Pharmacies Grid
          Expanded(
            child: _pharmacies.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.local_pharmacy_outlined,
                          size: 80,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'No pharmacies found',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Add your first pharmacy to get started',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  )
                : GridView.builder(
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                          childAspectRatio: 0.7,
                        ),
                    itemCount: _pharmacies.length,
                    itemBuilder: (context, index) {
                      final pharmacy = _pharmacies[index];
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
                            // Header
                            Container(
                              width: double.infinity,
                              height: 80,
                              decoration: const BoxDecoration(
                                color: Color(0xFF0B6E6E),
                                borderRadius: BorderRadius.only(
                                  topLeft: Radius.circular(16),
                                  topRight: Radius.circular(16),
                                ),
                              ),
                              child: const Icon(
                                Icons.local_pharmacy,
                                color: Colors.white,
                                size: 40,
                              ),
                            ),

                            // Content
                            Expanded(
                              child: Padding(
                                padding: const EdgeInsets.all(10),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      pharmacy.email,
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                      ),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      'MetaMask: ${pharmacy.metamaskAddress.substring(0, 6)}...',
                                      style: TextStyle(
                                        fontSize: 8,
                                        color: Colors.grey[600],
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const Spacer(),
                                    Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceEvenly,
                                      children: [
                                        IconButton(
                                          icon: const Icon(
                                            Icons.edit,
                                            color: Color(0xFF0B6E6E),
                                            size: 16,
                                          ),
                                          onPressed: () => _showPharmacyDialog(
                                            pharmacy: pharmacy,
                                          ),
                                          padding: EdgeInsets.zero,
                                          constraints: const BoxConstraints(
                                            minWidth: 28,
                                            minHeight: 28,
                                          ),
                                        ),
                                        IconButton(
                                          icon: const Icon(
                                            Icons.delete,
                                            color: Colors.red,
                                            size: 16,
                                          ),
                                          onPressed: () =>
                                              _deletePharmacy(pharmacy),
                                          padding: EdgeInsets.zero,
                                          constraints: const BoxConstraints(
                                            minWidth: 28,
                                            minHeight: 28,
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
