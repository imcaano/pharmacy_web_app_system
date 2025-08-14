import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/user.dart';
import '../models/medicine.dart';
import '../models/order.dart';
import '../models/prescription.dart';

class ApiService {
  static const String baseUrl =
      'http://192.168.137.1/pharmacy_web_app_system/api';

  static Future<Map<String, dynamic>> login(
    String email,
    String password,
  ) async {
    final url = Uri.parse('$baseUrl/login.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['success'] == true) {
      return {'user': User.fromJson(data['user']), 'token': data['token']};
    } else {
      throw Exception(data['error'] ?? 'Login failed');
    }
  }

  static Future<Map<String, dynamic>> signup(
    String email,
    String password,
    String userType,
    String metamaskAddress,
  ) async {
    final url = Uri.parse('$baseUrl/signup.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
        'user_type': userType,
        'metamask_address': metamaskAddress,
      }),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['success'] == true) {
      return {'user': User.fromJson(data['user']), 'token': data['token']};
    } else {
      throw Exception(data['error'] ?? 'Signup failed');
    }
  }

  // USERS CRUD
  static Future<List<User>> fetchUsers(String token) async {
    final url = Uri.parse('$baseUrl/users.php');
    final response = await http.get(url, headers: _headers(token));
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return (data['users'] as List).map((u) => User.fromJson(u)).toList();
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch users');
    }
  }

  static Future<bool> createUser(
    String token,
    User user,
    String password,
  ) async {
    final url = Uri.parse('$baseUrl/users.php');
    final response = await http.post(
      url,
      headers: _headers(token),
      body: jsonEncode({
        'email': user.email,
        'password': password,
        'user_type': user.userType,
        'metamask_address': user.metamaskAddress,
      }),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> updateUser(String token, User user) async {
    final url = Uri.parse('$baseUrl/users.php');
    final response = await http.put(
      url,
      headers: _headers(token),
      body: jsonEncode({
        'id': user.id,
        'email': user.email,
        'user_type': user.userType,
        'metamask_address': user.metamaskAddress,
      }),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> deleteUser(String token, int id) async {
    final url = Uri.parse('$baseUrl/users.php');
    final response = await http.delete(
      url,
      headers: _headers(token),
      body: jsonEncode({'id': id}),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  // MEDICINES CRUD
  static Future<List<Medicine>> fetchMedicines(String token) async {
    final url = Uri.parse('$baseUrl/medicines.php');
    final response = await http.get(url, headers: _headers(token));
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return (data['medicines'] as List)
          .map((m) => Medicine.fromJson(m))
          .toList();
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch medicines');
    }
  }

  // Customer method to fetch all medicines from all pharmacies
  static Future<List<Medicine>> fetchAllMedicines() async {
    final url = Uri.parse('$baseUrl/medicines.php?all=true');
    final response = await http.get(
      url,
      headers: {'Content-Type': 'application/json'},
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return (data['medicines'] as List)
          .map((m) => Medicine.fromJson(m))
          .toList();
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch medicines');
    }
  }

  // CART METHODS
  static Future<List<Map<String, dynamic>>> fetchCartItems(
    int customerId,
  ) async {
    final url = Uri.parse('$baseUrl/cart.php?customer_id=$customerId');
    final response = await http.get(
      url,
      headers: {'Content-Type': 'application/json'},
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return (data['cart_items'] as List).cast<Map<String, dynamic>>();
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch cart items');
    }
  }

  static Future<bool> addToCart(
    int customerId,
    int medicineId,
    int quantity,
  ) async {
    final url = Uri.parse('$baseUrl/cart.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'customer_id': customerId,
        'medicine_id': medicineId,
        'quantity': quantity,
      }),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> updateCartItem(
    int customerId,
    int cartId,
    int quantity,
  ) async {
    final url = Uri.parse('$baseUrl/cart.php');
    final response = await http.put(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'customer_id': customerId,
        'cart_id': cartId,
        'quantity': quantity,
      }),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> removeFromCart(int customerId, int cartId) async {
    final url = Uri.parse('$baseUrl/cart.php');
    final response = await http.delete(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'customer_id': customerId, 'cart_id': cartId}),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> createMedicine(Medicine medicine) async {
    final url = Uri.parse('$baseUrl/medicines.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'name': medicine.name,
        'description': medicine.description,
        'price': medicine.price,
        'stock_quantity': medicine.stockQuantity,
        'pharmacy_id': medicine.pharmacyId,
      }),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> updateMedicine(Medicine medicine) async {
    final url = Uri.parse('$baseUrl/medicines.php');
    final response = await http.put(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'id': medicine.id,
        'name': medicine.name,
        'description': medicine.description,
        'price': medicine.price,
        'stock_quantity': medicine.stockQuantity,
        'pharmacy_id': medicine.pharmacyId,
      }),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> deleteMedicine(int id) async {
    final url = Uri.parse('$baseUrl/medicines.php');
    final response = await http.delete(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'id': id}),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  // ORDERS CRUD
  static Future<List<Order>> fetchOrders(String token) async {
    final url = Uri.parse('$baseUrl/orders.php');
    final response = await http.get(url, headers: _headers(token));
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return (data['orders'] as List).map((o) => Order.fromJson(o)).toList();
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch orders');
    }
  }

  static Future<bool> createOrder(String token, Order order) async {
    final url = Uri.parse('$baseUrl/orders.php');
    final response = await http.post(
      url,
      headers: _headers(token),
      body: jsonEncode({
        'customer_id': order.customerId,
        'pharmacy_id': order.pharmacyId,
        'status': order.status,
        'total_amount': order.totalAmount,
      }),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> updateOrder(int id, String status) async {
    final url = Uri.parse('$baseUrl/orders.php');
    final response = await http.put(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'id': id, 'status': status}),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> deleteOrder(String token, int id) async {
    final url = Uri.parse('$baseUrl/orders.php');
    final response = await http.delete(
      url,
      headers: _headers(token),
      body: jsonEncode({'id': id}),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  // PRESCRIPTIONS CRUD
  static Future<List<Prescription>> fetchPrescriptions() async {
    final url = Uri.parse('$baseUrl/prescriptions.php');
    final response = await http.get(
      url,
      headers: {'Content-Type': 'application/json'},
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return (data['prescriptions'] as List)
          .map((p) => Prescription.fromJson(p))
          .toList();
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch prescriptions');
    }
  }

  static Future<bool> createPrescription(
    String token,
    Prescription presc,
  ) async {
    final url = Uri.parse('$baseUrl/prescriptions.php');
    final response = await http.post(
      url,
      headers: _headers(token),
      body: jsonEncode({
        'customer_id': presc.customerId,
        'file_url': presc.fileUrl,
        'status': presc.status,
      }),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> updatePrescription(
    String token,
    int id,
    String status,
  ) async {
    final url = Uri.parse('$baseUrl/prescriptions.php');
    final response = await http.put(
      url,
      headers: _headers(token),
      body: jsonEncode({'id': id, 'status': status}),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  static Future<bool> deletePrescription(String token, int id) async {
    final url = Uri.parse('$baseUrl/prescriptions.php');
    final response = await http.delete(
      url,
      headers: _headers(token),
      body: jsonEncode({'id': id}),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }

  // Accept or reject prescription
  static Future<Map<String, dynamic>> acceptPrescription(
    int prescriptionId,
    String action, {
    String? notes,
  }) async {
    final url = Uri.parse('$baseUrl/prescriptions.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'prescription_id': prescriptionId,
        'action': action,
        if (notes != null) 'notes': notes,
      }),
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return data;
    } else {
      throw Exception(data['error'] ?? 'Failed to $action prescription');
    }
  }

  // Pharmacy-specific methods
  static Future<List<Medicine>> fetchMedicinesByPharmacy(int pharmacyId) async {
    final url = Uri.parse('$baseUrl/medicines.php?pharmacy_id=$pharmacyId');
    final response = await http.get(
      url,
      headers: {'Content-Type': 'application/json'},
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return (data['medicines'] as List)
          .map((m) => Medicine.fromJson(m))
          .toList();
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch medicines');
    }
  }

  static Future<List<Order>> fetchOrdersByPharmacy(int pharmacyId) async {
    final url = Uri.parse('$baseUrl/orders.php');
    final response = await http.get(
      url,
      headers: {'Content-Type': 'application/json'},
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return (data['orders'] as List)
          .map((o) => Order.fromJson(o))
          .where((o) => o.pharmacyId == pharmacyId)
          .toList();
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch orders');
    }
  }

  // FILE UPLOAD
  static Future<String> uploadPrescriptionFile(
    String token,
    String filePath,
  ) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl/upload_prescription.php'),
    );
    request.headers['Authorization'] = 'Bearer $token';
    request.files.add(await http.MultipartFile.fromPath('file', filePath));

    final response = await request.send();
    final responseData = await response.stream.bytesToString();

    if (response.statusCode == 200) {
      final data = json.decode(responseData);
      return data['fileUrl'];
    } else {
      throw Exception('Failed to upload file');
    }
  }

  // PHARMACY SPECIFIC METHODS
  static Future<Map<String, dynamic>> fetchPharmacyStats(int pharmacyId) async {
    final url = Uri.parse('$baseUrl/pharmacy_stats.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'pharmacy_id': pharmacyId}),
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return data['stats'];
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch pharmacy stats');
    }
  }

  static Future<List<Map<String, dynamic>>> fetchRecentOrders(
    int pharmacyId,
  ) async {
    final url = Uri.parse('$baseUrl/pharmacy_recent_orders.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'pharmacy_id': pharmacyId}),
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return List<Map<String, dynamic>>.from(data['orders']);
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch recent orders');
    }
  }

  static Future<List<Map<String, dynamic>>> fetchLowStockMedicines(
    int pharmacyId,
  ) async {
    final url = Uri.parse('$baseUrl/pharmacy_low_stock.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'pharmacy_id': pharmacyId}),
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return List<Map<String, dynamic>>.from(data['medicines']);
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch low stock medicines');
    }
  }

  // PHARMACY PROFILE METHODS
  static Future<Map<String, dynamic>> fetchPharmacyProfile(
    int pharmacyId,
  ) async {
    final url = Uri.parse('$baseUrl/pharmacy_profile.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'pharmacy_id': pharmacyId}),
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return data['pharmacy'];
    } else {
      throw Exception(data['error'] ?? 'Failed to fetch pharmacy profile');
    }
  }

  static Future<bool> updatePharmacyProfile(
    int pharmacyId,
    String pharmacyName,
    String address,
    String phone,
    String email,
    String metamaskAddress,
  ) async {
    final url = Uri.parse('$baseUrl/pharmacy_profile.php');
    final response = await http.put(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'pharmacy_id': pharmacyId,
        'pharmacy_name': pharmacyName,
        'address': address,
        'phone': phone,
        'email': email,
        'metamask_address': metamaskAddress,
      }),
    );
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return true;
    } else {
      throw Exception(data['error'] ?? 'Failed to update pharmacy profile');
    }
  }

  static Map<String, String> _headers(String token) {
    return {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }
}
