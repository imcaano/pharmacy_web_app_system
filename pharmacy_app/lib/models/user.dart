class User {
  final int id;
  final String email;
  final String userType;
  final String metamaskAddress;

  User({
    required this.id,
    required this.email,
    required this.userType,
    required this.metamaskAddress,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      email: json['email'],
      userType: json['user_type'],
      metamaskAddress: json['metamask_address'],
    );
  }
}
