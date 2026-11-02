import 'package:flutter/widgets.dart';
import 'package:flutter_animate/flutter_animate.dart';

/// App-wide switch for entrance animations. Follows the phone's "reduce
/// motion" accessibility setting (see main.dart) and is off in tests.
class Motion {
  static bool enabled = true;
}

extension MotionX on Widget {
  /// `child.motion((a) => a.fadeIn().moveY(begin: 12))` - the effects run
  /// when animations are on; otherwise the widget shows as it is.
  Widget motion(Animate Function(Animate a) effects) => Motion.enabled ? effects(animate()) : this;
}
