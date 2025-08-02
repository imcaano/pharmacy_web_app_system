import 'package:flutter/material.dart';
import '../models/user.dart';

class UserList extends StatelessWidget {
  final List<User> users;
  final void Function(User) onEdit;
  final void Function(User) onDelete;
  const UserList({
    super.key,
    required this.users,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      itemCount: users.length,
      separatorBuilder: (_, __) => const Divider(),
      itemBuilder: (context, i) {
        final user = users[i];
        return ListTile(
          leading: const Icon(Icons.person),
          title: Text(user.email),
          subtitle: Text(
            'Type: ${user.userType}\nMetaMask: ${user.metamaskAddress}',
          ),
          isThreeLine: true,
          trailing: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              IconButton(
                icon: const Icon(Icons.edit),
                onPressed: () => onEdit(user),
              ),
              IconButton(
                icon: const Icon(Icons.delete),
                onPressed: () => onDelete(user),
              ),
            ],
          ),
        );
      },
    );
  }
}
