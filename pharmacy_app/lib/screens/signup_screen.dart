import 'package:flutter/material.dart';
import 'dart:math';
import '../services/api_service.dart';
import '../models/user.dart';

class SignupScreen extends StatefulWidget {
  const SignupScreen({super.key});
  @override
  State<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  String? _userType;
  bool _loading = false;
  String? _error;

  String _generateMetaMaskAddress() {
    final rand = Random();
    const hex = '0123456789abcdef';
    String addr = '0x';
    for (int i = 0; i < 40; i++) {
      addr += hex[rand.nextInt(16)];
    }
    return addr;
  }

  void _signup() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final metamaskAddress = _generateMetaMaskAddress();
      final result = await ApiService.signup(
        _emailController.text,
        _passwordController.text,
        _userType!,
        metamaskAddress,
      );
      final user = result['user'] as User;
      final token = result['token'] as String;

      // Navigate based on user type
      switch (user.userType) {
        case 'admin':
          Navigator.of(context).pushReplacementNamed(
            '/admin',
            arguments: {'token': token, 'adminId': user.id},
          );
          break;
        case 'pharmacy':
          Navigator.of(context).pushReplacementNamed(
            '/pharmacy',
            arguments: {
              'token': token,
              'pharmacyId': user.id,
              'userEmail': user.email, // Pass user email
            },
          );
          break;
        case 'customer':
          Navigator.of(context).pushReplacementNamed(
            '/customer',
            arguments: {'token': token, 'customerId': user.id},
          );
          break;
        default:
          setState(() {
            _error = 'Unknown user type';
          });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFFE6F0F0), Color(0xFFB8DADA)],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Container(
                constraints: const BoxConstraints(maxWidth: 400),
                child: Card(
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                  ),
                  elevation: 12,
                  shadowColor: const Color(0xFF0B6E6E).withOpacity(0.1),
                  child: Padding(
                    padding: const EdgeInsets.all(32),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          // Icon at the top
                          Container(
                            width: 80,
                            height: 80,
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                colors: [Color(0xFF0B6E6E), Color(0xFF0B6E6E)],
                              ),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: const Icon(
                              Icons.person_add,
                              color: Colors.white,
                              size: 40,
                            ),
                          ),
                          const SizedBox(height: 24),

                          // Header
                          const Text(
                            'Create Account',
                            style: TextStyle(
                              fontSize: 28,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF22223B),
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Join our pharmacy network',
                            style: TextStyle(
                              fontSize: 16,
                              color: Color(0xFF6C6A7C),
                            ),
                          ),
                          const SizedBox(height: 32),

                          // Error Message
                          if (_error != null)
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(12),
                              margin: const EdgeInsets.only(bottom: 16),
                              decoration: BoxDecoration(
                                color: Colors.red.shade50,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: Colors.red.shade200),
                              ),
                              child: Text(
                                _error!,
                                style: TextStyle(
                                  color: Colors.red.shade700,
                                  fontSize: 14,
                                ),
                              ),
                            ),

                          // Email Field
                          TextFormField(
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            decoration: InputDecoration(
                              labelText: 'Email Address',
                              hintText: 'Enter your email',
                              prefixIcon: const Icon(
                                Icons.email,
                                color: Color(0xFF0B6E6E),
                              ),
                              filled: true,
                              fillColor: Colors.grey.shade50,
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide(
                                  color: Colors.grey.shade300,
                                ),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: const BorderSide(
                                  color: Color(0xFF0B6E6E),
                                  width: 2,
                                ),
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 16,
                              ),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Please enter your email';
                              }
                              if (!RegExp(
                                r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$',
                              ).hasMatch(value)) {
                                return 'Please enter a valid email';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),

                          // Password Field
                          TextFormField(
                            controller: _passwordController,
                            obscureText: true,
                            decoration: InputDecoration(
                              labelText: 'Password',
                              hintText: 'Enter your password',
                              prefixIcon: const Icon(
                                Icons.lock,
                                color: Color(0xFF0B6E6E),
                              ),
                              filled: true,
                              fillColor: Colors.grey.shade50,
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide(
                                  color: Colors.grey.shade300,
                                ),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: const BorderSide(
                                  color: Color(0xFF0B6E6E),
                                  width: 2,
                                ),
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 16,
                              ),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Please enter your password';
                              }
                              if (value.length < 6) {
                                return 'Password must be at least 6 characters';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),

                          // User Type Field
                          DropdownButtonFormField<String>(
                            value: _userType,
                            decoration: InputDecoration(
                              labelText: 'User Type',
                              hintText: 'Select user type',
                              prefixIcon: const Icon(
                                Icons.person,
                                color: Color(0xFF0B6E6E),
                              ),
                              filled: true,
                              fillColor: Colors.grey.shade50,
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide(
                                  color: Colors.grey.shade300,
                                ),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: const BorderSide(
                                  color: Color(0xFF0B6E6E),
                                  width: 2,
                                ),
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 16,
                              ),
                            ),
                            items: const [
                              DropdownMenuItem(
                                value: 'customer',
                                child: Text('Customer'),
                              ),
                              DropdownMenuItem(
                                value: 'pharmacy',
                                child: Text('Pharmacy'),
                              ),
                            ],
                            onChanged: (value) {
                              setState(() {
                                _userType = value;
                              });
                            },
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Please select user type';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 24),

                          // Signup Button
                          SizedBox(
                            width: double.infinity,
                            height: 50,
                            child: ElevatedButton(
                              onPressed: _loading ? null : _signup,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF0B6E6E),
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                elevation: 4,
                                shadowColor: const Color(
                                  0xFF0B6E6E,
                                ).withOpacity(0.3),
                              ),
                              child: _loading
                                  ? const SizedBox(
                                      height: 20,
                                      width: 20,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Text(
                                      'Sign Up',
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                            ),
                          ),
                          const SizedBox(height: 24),

                          // Login Link
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Text(
                                'Already have an account? ',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Color(0xFF6C6A7C),
                                ),
                              ),
                              GestureDetector(
                                onTap: () => Navigator.pushReplacementNamed(
                                  context,
                                  '/login',
                                ),
                                child: const Text(
                                  'Login',
                                  style: TextStyle(
                                    color: Color(0xFF0B6E6E),
                                    fontWeight: FontWeight.w600,
                                    fontSize: 16,
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
              ),
            ),
          ),
        ),
      ),
    );
  }
}
