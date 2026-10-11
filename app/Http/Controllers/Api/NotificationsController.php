<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The mobile app's notification inbox, for whoever holds the token -
 * staff, tenant, landlord or buyer (all Notifiable). Same notifications
 * the web bell shows: welcome messages, payment confirmations, "your
 * access was updated" (App\Modules\Users\Notifications\AccessChanged)...
 */
class NotificationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $account = $request->user();
        $page = $account->notifications()->latest()->paginate(20);

        return response()->json([
            'unread_count' => $account->unreadNotifications()->count(),
            'data' => collect($page->items())->map(fn (DatabaseNotification $n) => self::present($n))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $found = $request->user()->notifications()->findOrFail($notification);
        $found->markAsRead();

        return response()->json(['data' => self::present($found->fresh())]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['unread_count' => 0]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function present(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'kind' => $notification->data['kind'] ?? 'general',
            'title' => $notification->data['title'] ?? __('Notification'),
            'message' => $notification->data['message'] ?? '',
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
            'created_ago' => $notification->created_at?->diffForHumans(),
        ];
    }
}
