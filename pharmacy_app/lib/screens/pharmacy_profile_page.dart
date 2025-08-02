import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/api_service.dart';

class PharmacyProfilePage extends StatefulWidget {
  final String token;
  final int pharmacyId;
  final String userEmail; // Add user email parameter
  const PharmacyProfilePage({
    super.key,
    required this.token,
    required this.pharmacyId,
    required this.userEmail, // Add user email parameter
  });
  @override
  State<PharmacyProfilePage> createState() => _PharmacyProfilePageState();
}

class _PharmacyProfilePageState extends State<PharmacyProfilePage> {
  late TextEditingController _pharmacyNameController;
  late TextEditingController _addressController;
  late TextEditingController _phoneController;
  late TextEditingController _emailController;
  late TextEditingController _metamaskController;
  bool _loading = false;
  bool _saving = false;
  String? _error;
  String? _success;
  Map<String, dynamic>? _pharmacyData;

  @override
  void initState() {
    super.initState();
    _pharmacyNameController = TextEditingController();
    _addressController = TextEditingController();
    _phoneController = TextEditingController();
    _emailController = TextEditingController();
    _metamaskController = TextEditingController();
    _loadProfile();
  }

  @override
  void dispose() {
    _pharmacyNameController.dispose();
    _addressController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _metamaskController.dispose();
    super.dispose();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      // Find pharmacy record for this user
      final pharmacyData = await ApiService.fetchPharmacyProfile(
        widget.pharmacyId,
      );
      setState(() {
        _pharmacyData = pharmacyData;
        _pharmacyNameController.text = pharmacyData['pharmacy_name'] ?? '';
        _addressController.text = pharmacyData['address'] ?? '';
        _phoneController.text = pharmacyData['phone'] ?? '';
        // Use the email from the user record, not pharmacy record
        _emailController.text = widget.userEmail; // Use the login email
        _metamaskController.text = pharmacyData['metamask_address'] ?? '';
      });
    } catch (e) {
      setState(() {
        _error = 'Error loading profile: ${e.toString()}';
      });

      // Show error message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error loading profile: ${e.toString()}'),
          backgroundColor: Colors.red,
          duration: const Duration(seconds: 5),
        ),
      );
    }

    setState(() {
      _loading = false;
    });
  }

  void _saveProfile() async {
    setState(() {
      _saving = true;
      _error = null;
      _success = null;
    });

    try {
      // Use the user's pharmacy_id
      final pharmacyId = widget.pharmacyId;

      await ApiService.updatePharmacyProfile(
        pharmacyId,
        _pharmacyNameController.text,
        _addressController.text,
        _phoneController.text,
        _emailController.text,
        _metamaskController.text,
      );

      setState(() {
        _success = 'Profile updated successfully!';
      });

      // Show success message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Profile updated successfully!'),
          backgroundColor: const Color(0xFF0b6e6e),
          duration: const Duration(seconds: 3),
        ),
      );
    } catch (e) {
      setState(() {
        _error = 'Error updating profile: ${e.toString()}';
      });

      // Show error message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error updating profile: ${e.toString()}'),
          backgroundColor: Colors.red,
          duration: const Duration(seconds: 3),
        ),
      );
    }

    setState(() {
      _saving = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Center(
        child: CircularProgressIndicator(
          valueColor: AlwaysStoppedAnimation<Color>(Color(0xFF0B6E6E)),
        ),
      );
    }

    if (_error != null && _pharmacyData == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
            SizedBox(height: 16),
            Text(
              'Error loading profile',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 8),
            Text(_error!, style: TextStyle(color: Colors.grey[600])),
            SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loadProfile,
              style: ElevatedButton.styleFrom(
                backgroundColor: Color(0xFF0B6E6E),
                foregroundColor: Colors.white,
              ),
              child: Text('Retry'),
            ),
          ],
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: EdgeInsets.all(24),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E).withOpacity(0.8)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 60,
                      height: 60,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(30),
                      ),
                      child: Icon(
                        Icons.local_pharmacy,
                        color: Color(0xFF0B6E6E),
                        size: 30,
                      ),
                    ),
                    SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Pharmacy Profile',
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                          Text(
                            'Manage your pharmacy information',
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
              ],
            ),
          ),

          SizedBox(height: 24),

          // Profile Form
          Container(
            padding: EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  blurRadius: 10,
                  offset: Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Pharmacy Information',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey[800],
                  ),
                ),
                SizedBox(height: 24),

                // Pharmacy ID Display
                Text(
                  'Pharmacy ID',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF0B6E6E),
                  ),
                ),
                SizedBox(height: 8),
                Container(
                  width: double.infinity,
                  padding: EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey.shade300),
                  ),
                  child: Text(
                    '${widget.pharmacyId > 0 ? widget.pharmacyId : 11}',
                    style: TextStyle(fontSize: 16, color: Colors.grey[700]),
                  ),
                ),

                SizedBox(height: 20),

                // Pharmacy Name Field
                Text(
                  'Pharmacy Name',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF0B6E6E),
                  ),
                ),
                SizedBox(height: 8),
                TextFormField(
                  controller: _pharmacyNameController,
                  decoration: InputDecoration(
                    hintText: 'Enter pharmacy name',
                    filled: true,
                    fillColor: Colors.grey.shade50,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: Color(0xFF0B6E6E),
                        width: 2,
                      ),
                    ),
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 16,
                    ),
                  ),
                ),

                SizedBox(height: 20),

                // Address Field
                Text(
                  'Address',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF0B6E6E),
                  ),
                ),
                SizedBox(height: 8),
                TextFormField(
                  controller: _addressController,
                  decoration: InputDecoration(
                    hintText: 'Enter pharmacy address',
                    filled: true,
                    fillColor: Colors.grey.shade50,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: Color(0xFF0B6E6E),
                        width: 2,
                      ),
                    ),
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 16,
                    ),
                  ),
                ),

                SizedBox(height: 20),

                // Phone Field
                Text(
                  'Phone Number',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF0B6E6E),
                  ),
                ),
                SizedBox(height: 8),
                TextFormField(
                  controller: _phoneController,
                  decoration: InputDecoration(
                    hintText: 'Enter phone number',
                    filled: true,
                    fillColor: Colors.grey.shade50,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: Color(0xFF0B6E6E),
                        width: 2,
                      ),
                    ),
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 16,
                    ),
                  ),
                ),

                SizedBox(height: 20),

                // Email Field
                Text(
                  'Email Address',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF0B6E6E),
                  ),
                ),
                SizedBox(height: 8),
                TextFormField(
                  controller: _emailController,
                  decoration: InputDecoration(
                    hintText: 'Enter your email',
                    filled: true,
                    fillColor: Colors.grey.shade50,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: Color(0xFF0B6E6E),
                        width: 2,
                      ),
                    ),
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 16,
                    ),
                  ),
                ),

                SizedBox(height: 20),

                // MetaMask Field
                Text(
                  'MetaMask Address',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF0B6E6E),
                  ),
                ),
                SizedBox(height: 8),
                TextFormField(
                  controller: _metamaskController,
                  decoration: InputDecoration(
                    hintText: 'Enter MetaMask address',
                    filled: true,
                    fillColor: Colors.grey.shade50,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: Color(0xFF0B6E6E),
                        width: 2,
                      ),
                    ),
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 16,
                    ),
                  ),
                ),

                SizedBox(height: 24),

                // Error/Success Messages
                if (_error != null)
                  Container(
                    width: double.infinity,
                    padding: EdgeInsets.all(12),
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
                    padding: EdgeInsets.all(12),
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

                SizedBox(height: 24),

                // Save Button
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton(
                    onPressed: _saving ? null : _saveProfile,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Color(0xFF0B6E6E),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 4,
                    ),
                    child: _saving
                        ? SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            'Save Changes',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
