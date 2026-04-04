import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/gamification_model.dart';
import '../../data/repositories/gamification_repository.dart';

// ---------------------------------------------------------------------------
// Gamification profile
// ---------------------------------------------------------------------------

final gamificationProfileProvider =
    FutureProvider<GamificationProfile>((ref) async {
  return ref.read(gamificationRepositoryProvider).getProfile();
});

// ---------------------------------------------------------------------------
// Badges
// ---------------------------------------------------------------------------

final badgesProvider = FutureProvider<List<BadgeModel>>((ref) async {
  return ref.read(gamificationRepositoryProvider).getBadges();
});

// ---------------------------------------------------------------------------
// Leaderboard
// ---------------------------------------------------------------------------

final leaderboardProvider =
    FutureProvider<List<LeaderboardEntry>>((ref) async {
  return ref.read(gamificationRepositoryProvider).getLeaderboard();
});
