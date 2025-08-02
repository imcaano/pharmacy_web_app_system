class Order {
  final int id;
  final int customerId;
  final int pharmacyId;
  final String status;
  final double totalAmount;
  final DateTime createdAt;

  Order({
    required this.id,
    required this.customerId,
    required this.pharmacyId,
    required this.status,
    required this.totalAmount,
    required this.createdAt,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      customerId: json['customer_id'] is int
          ? json['customer_id']
          : int.parse(json['customer_id'].toString()),
      pharmacyId: json['pharmacy_id'] is int
          ? json['pharmacy_id']
          : int.parse(json['pharmacy_id'].toString()),
      status: json['status'] ?? '',
      totalAmount: json['total_amount'] is num
          ? (json['total_amount'] as num).toDouble()
          : double.parse(json['total_amount'].toString()),
      createdAt: DateTime.parse(
        json['created_at'] ?? DateTime.now().toIso8601String(),
      ),
    );
  }
}
