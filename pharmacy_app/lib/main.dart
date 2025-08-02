import 'package:flutter/material.dart';
import 'screens/login_screen.dart';
import 'screens/signup_screen.dart';
import 'screens/admin_dashboard_screen.dart';
import 'screens/pharmacy_dashboard_screen.dart';
import 'screens/customer_dashboard_screen.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Pharmacy App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.teal,
        primaryColor: const Color(0xFF0b6e6e),
        scaffoldBackgroundColor: const Color(0xFFF8F9FA),
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF0b6e6e),
          foregroundColor: Colors.white,
          elevation: 0,
          centerTitle: true,
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF0b6e6e),
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            elevation: 2,
          ),
        ),
        inputDecorationTheme: const InputDecorationTheme(
          border: OutlineInputBorder(),
          contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          focusedBorder: OutlineInputBorder(
            borderSide: BorderSide(color: Color(0xFF0b6e6e), width: 2),
          ),
        ),

        textTheme: const TextTheme(
          headlineLarge: TextStyle(color: Color(0xFF0b6e6e)),
          headlineMedium: TextStyle(color: Color(0xFF0b6e6e)),
          headlineSmall: TextStyle(color: Color(0xFF0b6e6e)),
          titleLarge: TextStyle(color: Color(0xFF0b6e6e)),
          titleMedium: TextStyle(color: Color(0xFF0b6e6e)),
          titleSmall: TextStyle(color: Color(0xFF0b6e6e)),
        ),
      ),
      initialRoute: '/login',
      onGenerateRoute: (settings) {
        switch (settings.name) {
          case '/login':
            return MaterialPageRoute(builder: (context) => const LoginScreen());
          case '/signup':
            return MaterialPageRoute(
              builder: (context) => const SignupScreen(),
            );
          case '/admin':
            final args = settings.arguments as Map<String, dynamic>;
            return MaterialPageRoute(
              builder: (context) => AdminDashboardScreen(
                token: args['token'],
                adminId: args['adminId'],
              ),
            );
          case '/pharmacy':
            final args = settings.arguments as Map<String, dynamic>;
            return MaterialPageRoute(
              builder: (context) => PharmacyDashboardScreen(
                token: args['token'],
                pharmacyId: args['pharmacyId'],
                userEmail: args['userEmail'], // Pass user email
              ),
            );
          case '/customer':
            final args = settings.arguments as Map<String, dynamic>;
            return MaterialPageRoute(
              builder: (context) => CustomerDashboardScreen(
                token: args['token'],
                customerId: args['customerId'],
              ),
            );
          default:
            return MaterialPageRoute(builder: (context) => const LoginScreen());
        }
      },
    );
  }
}
