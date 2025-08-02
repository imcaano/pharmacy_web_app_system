class Prescription {
  final int id;
  final int customerId;
  final String fileUrl;
  final String status;
  final DateTime createdAt;

  Prescription({
    required this.id,
    required this.customerId,
    required this.fileUrl,
    required this.status,
    required this.createdAt,
  });

  factory Prescription.fromJson(Map<String, dynamic> json) {
    return Prescription(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      customerId: json['customer_id'] is int
          ? json['customer_id']
          : int.parse(json['customer_id'].toString()),
      fileUrl: json['file_url'] ?? '',
      status: json['status'] ?? '',
      createdAt: DateTime.parse(
        json['created_at'] ?? DateTime.now().toIso8601String(),
      ),
    );
  }
}
