import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/api_service.dart';
import '../widgets/user_list.dart';

class AdminUsersPage extends StatefulWidget {
  final String token;
  const AdminUsersPage({super.key, required this.token});
  @override
  State<AdminUsersPage> createState() => _AdminUsersPageState();
}

class _AdminUsersPageState extends State<AdminUsersPage> {
  List<User> _users = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchUsers();
  }

  Future<void> _fetchUsers() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final users = await ApiService.fetchUsers(widget.token);
      setState(() {
        _users = users;
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

  void _showUserDialog({User? user}) {
    final emailController = TextEditingController(text: user?.email ?? '');
    final metamaskController = TextEditingController(
      text: user?.metamaskAddress ?? '',
    );
    String userType = user?.userType ?? 'customer';
    String password = '';
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(user == null ? 'Add User' : 'Edit User'),
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
            DropdownButton<String>(
              value: userType,
              items: const [
                DropdownMenuItem(value: 'customer', child: Text('Customer')),
                DropdownMenuItem(value: 'pharmacy', child: Text('Pharmacy')),
                DropdownMenuItem(value: 'admin', child: Text('Admin')),
              ],
              onChanged: (v) => setState(() => userType = v ?? 'customer'),
            ),
            if (user == null)
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
              if (user == null) {
                // Add
                await ApiService.createUser(
                  widget.token,
                  User(
                    id: 0,
                    email: emailController.text,
                    userType: userType,
                    metamaskAddress: metamaskController.text,
                  ),
                  password,
                );
              } else {
                // Edit
                await ApiService.updateUser(
                  widget.token,
                  User(
                    id: user.id,
                    email: emailController.text,
                    userType: userType,
                    metamaskAddress: metamaskController.text,
                  ),
                );
              }
              Navigator.pop(ctx);
              _fetchUsers();
            },
            child: Text(user == null ? 'Add' : 'Save'),
          ),
        ],
      ),
    );
  }

  void _deleteUser(User user) async {
    await ApiService.deleteUser(widget.token, user.id);
    _fetchUsers();
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
              'Error loading users',
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
                        'Users Management',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      Text(
                        '${_users.length} total users',
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
                  onPressed: () => _showUserDialog(),
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

          // Users Grid
          Expanded(
            child: _users.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.people_outline,
                          size: 80,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'No users found',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Add your first user to get started',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  )
                : GridView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 16,
                          mainAxisSpacing: 16,
                          childAspectRatio: 0.65,
                        ),
                    itemCount: _users.length,
                    itemBuilder: (context, index) {
                      final user = _users[index];
                      return Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.grey.withOpacity(0.1),
                              spreadRadius: 2,
                              blurRadius: 15,
                              offset: const Offset(0, 8),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            // Header with gradient
                            Container(
                              width: double.infinity,
                              height: 100,
                              decoration: const BoxDecoration(
                                gradient: LinearGradient(
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                  colors: [
                                    Color(0xFF0B6E6E),
                                    Color(0xFF0B6E6E),
                                  ],
                                ),
                                borderRadius: BorderRadius.only(
                                  topLeft: Radius.circular(20),
                                  topRight: Radius.circular(20),
                                ),
                              ),
                              child: Stack(
                                children: [
                                  // Background pattern
                                  Positioned(
                                    top: -20,
                                    right: -20,
                                    child: Container(
                                      width: 60,
                                      height: 60,
                                      decoration: BoxDecoration(
                                        color: Colors.white.withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(30),
                                      ),
                                    ),
                                  ),
                                  // User avatar
                                  Center(
                                    child: Container(
                                      width: 60,
                                      height: 60,
                                      decoration: BoxDecoration(
                                        color: Colors.white,
                                        borderRadius: BorderRadius.circular(30),
                                        boxShadow: [
                                          BoxShadow(
                                            color: Colors.black.withOpacity(
                                              0.1,
                                            ),
                                            spreadRadius: 2,
                                            blurRadius: 8,
                                            offset: const Offset(0, 4),
                                          ),
                                        ],
                                      ),
                                      child: Icon(
                                        _getUserTypeIcon(user.userType),
                                        color: const Color(0xFF0B6E6E),
                                        size: 30,
                                      ),
                                    ),
                                  ),
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
                                    Text(
                                      user.email,
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFF0B6E6E),
                                      ),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 6),
                                    Text(
                                      'MetaMask: ${user.metamaskAddress.substring(0, 6)}...',
                                      style: TextStyle(
                                        fontSize: 8,
                                        color: Colors.grey[600],
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 8,
                                        vertical: 3,
                                      ),
                                      decoration: BoxDecoration(
                                        gradient: LinearGradient(
                                          colors: [
                                            _getUserTypeColor(user.userType).withOpacity(0.1),
                                            _getUserTypeColor(user.userType).withOpacity(0.05),
                                          ],
                                        ),
                                        borderRadius: BorderRadius.circular(15),
                                        border: Border.all(
                                          color: _getUserTypeColor(user.userType).withOpacity(0.3),
                                          width: 1,
                                        ),
                                      ),
                                      child: Text(
                                        user.userType.toUpperCase(),
                                        style: TextStyle(
                                          color: _getUserTypeColor(user.userType),
                                          fontSize: 8,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                    const Spacer(),
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                                      children: [
                                        Container(
                                          decoration: BoxDecoration(
                                            color: const Color(0xFF0B6E6E).withOpacity(0.1),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: IconButton(
                                            icon: const Icon(
                                              Icons.edit,
                                              color: Color(0xFF0B6E6E),
                                              size: 16,
                                            ),
                                            onPressed: () => _showUserDialog(user: user),
                                            padding: const EdgeInsets.all(6),
                                            constraints: const BoxConstraints(
                                              minWidth: 32,
                                              minHeight: 32,
                                            ),
                                          ),
                                        ),
                                        Container(
                                          decoration: BoxDecoration(
                                            color: Colors.red.withOpacity(0.1),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: IconButton(
                                            icon: const Icon(
                                              Icons.delete,
                                              color: Colors.red,
                                              size: 16,
                                            ),
                                            onPressed: () => _deleteUser(user),
                                            padding: const EdgeInsets.all(6),
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

  Color _getUserTypeColor(String userType) {
    switch (userType) {
      case 'admin':
        return Colors.red;
      case 'pharmacy':
        return Colors.orange;
      case 'customer':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  IconData _getUserTypeIcon(String userType) {
    switch (userType) {
      case 'admin':
        return Icons.admin_panel_settings;
      case 'pharmacy':
        return Icons.local_pharmacy;
      case 'customer':
        return Icons.person;
      default:
        return Icons.person;
    }
  }
}
