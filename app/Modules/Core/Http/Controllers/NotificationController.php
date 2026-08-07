<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Notification;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The in-app half of notification delivery: what somebody has already been told
 * about and has not yet dealt with.
 *
 * Everything here is confined to the organization the person is working in,
 * plus the notifications that belong to no organization because they are about
 * the person themselves.
 */
class NotificationController extends Controller
{
    /**
     * The person's notifications in the organization they are working in.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $user->notificationsIn(OrganizationContext::current())
            ->latest()
            ->limit(50)
            ->get(['id', 'type', 'data', 'read_at', 'created_at']);

        return response()->json([
            'notifications' => $notifications,
            'unread' => $user->notificationsIn(OrganizationContext::current())->unread()->count(),
        ]);
    }

    /**
     * Mark one notification as seen.
     *
     * Scoped through the person's own notifications rather than looked up by id
     * alone, so an id belonging to somebody else — or to another organization —
     * is not found rather than quietly acted on.
     */
    public function update(Request $request, string $notification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $found = $user->notificationsIn(OrganizationContext::current())
            ->whereKey($notification)
            ->firstOrFail();

        $found->markAsRead();

        return back();
    }

    /**
     * Mark everything currently visible as seen.
     */
    public function readAll(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->notificationsIn(OrganizationContext::current())
            ->unread()
            ->update(['read_at' => now()]);

        return back();
    }

    /**
     * Dismiss a notification outright.
     *
     * Removing it here removes only the record that the person was told; the
     * node's activity timeline is what keeps the account of what happened, and
     * nothing on it can be deleted.
     */
    public function destroy(Request $request, string $notification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $found = $user->notificationsIn(OrganizationContext::current())
            ->whereKey($notification)
            ->firstOrFail();

        /** @var Notification $found */
        $found->delete();

        return back();
    }
}
