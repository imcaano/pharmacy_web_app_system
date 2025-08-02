class Medicine {
  final int id;
  final String name;
  final String description;
  final double price;
  final int stockQuantity;
  final int pharmacyId;
  final String? pharmacyName;
  final String? category;

  Medicine({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
    required this.stockQuantity,
    required this.pharmacyId,
    this.pharmacyName,
    this.category,
  });

  factory Medicine.fromJson(Map<String, dynamic> json) {
    return Medicine(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      price: json['price'] is num ? (json['price'] as num).toDouble() : double.parse(json['price'].toString()),
      stockQuantity: json['stock_quantity'] is int ? json['stock_quantity'] : int.parse(json['stock_quantity'].toString()),
      pharmacyId: json['pharmacy_id'] is int ? json['pharmacy_id'] : int.parse(json['pharmacy_id'].toString()),
      pharmacyName: json['pharmacy_name'],
      category: json['category'],
    );
  }
}
 