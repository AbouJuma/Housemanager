import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import 'staff_repository.dart';

final staffRepositoryProvider = Provider<StaffRepository>((ref) => StaffRepository(ref.watch(apiClientProvider)));
