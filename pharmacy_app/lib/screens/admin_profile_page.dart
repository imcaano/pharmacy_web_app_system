import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/api_service.dart';

class AdminProfilePage extends StatefulWidget {
  final String token;
  final int adminId;
  const AdminProfilePage({
    super.key,
    required this.token,
    required this.adminId,
  });
  @override
  State<AdminProfilePage> createState() => _AdminProfilePageState();
}

class _AdminProfilePageState extends State<AdminProfilePage> {
  late TextEditingController _emailController;
  late TextEditingController _metamaskController;
  bool _loading = false;
  bool _saving = false;
  String? _error;
  String? _success;
  User? _adminUser;

  @override
  void initState() {
    super.initState();
    _emailController = TextEditingController();
    _metamaskController = TextEditingController();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final users = await ApiService.fetchUsers(widget.token);
      final adminUser = users.firstWhere((u) => u.id == widget.adminId);
      setState(() {
        _adminUser = adminUser;
        _emailController.text = adminUser.email;
        _metamaskController.text = adminUser.metamaskAddress;
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

  void _saveProfile() async {
    if (_adminUser == null) return;

    setState(() {
      _saving = true;
      _error = null;
      _success = null;
    });
    try {
      final updated = User(
        id: _adminUser!.id,
        email: _emailController.text,
        userType: _adminUser!.userType,
        metamaskAddress: _metamaskController.text,
      );
      await ApiService.updateUser(widget.token, updated);
      setState(() {
        _success = 'Profile updated successfully!';
        _adminUser = updated;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    }
    setState(() {
      _saving = false;
    });
  }

  @override
  Widget build(BuildContext context) {
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
              children: [
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(30),
                  ),
                  child: const Icon(
                    Icons.admin_panel_settings,
                    color: Color(0xFF0B6E6E),
                    size: 30,
                  ),
                ),
                const SizedBox(width: 16),
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Admin Profile',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      'Manage your account settings',
                      style: TextStyle(fontSize: 16, color: Colors.white70),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Profile Form Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(24),
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
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Account Information',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF0B6E6E),
                  ),
                ),
                const SizedBox(height: 24),

                // Email Field
                TextFormField(
                  controller: _emailController,
                  decoration: const InputDecoration(
                    labelText: 'Email',
                    prefixIcon: Icon(Icons.email, color: Color(0xFF0B6E6E)),
                    border: OutlineInputBorder(),
                    focusedBorder: OutlineInputBorder(
                      borderSide: BorderSide(
                        color: Color(0xFF0B6E6E),
                        width: 2,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // MetaMask Address Field
                TextFormField(
                  controller: _metamaskController,
                  decoration: const InputDecoration(
                    labelText: 'MetaMask Address',
                    prefixIcon: Icon(
                      Icons.account_balance_wallet,
                      color: Color(0xFF0B6E6E),
                    ),
                    border: OutlineInputBorder(),
                    focusedBorder: OutlineInputBorder(
                      borderSide: BorderSide(
                        color: Color(0xFF0B6E6E),
                        width: 2,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Save Button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: _loading ? null : _saveProfile,
                    icon: _loading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: AlwaysStoppedAnimation<Color>(
                                Colors.white,
                              ),
                            ),
                          )
                        : const Icon(Icons.save),
                    label: Text(_loading ? 'Saving...' : 'Save Changes'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0B6E6E),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),

                if (_error != null) ...[
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.red[50],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.red[200]!),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.error_outline, color: Colors.red[600]),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _error!,
                            style: TextStyle(color: Colors.red[700]),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                if (_success != null) ...[
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.green[50],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.green[200]!),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.check_circle_outline,
                          color: Colors.green[600],
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _success!,
                            style: TextStyle(color: Colors.green[700]),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
