<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->paginate($request->query());

        return $this->success(NotificationResource::collection($notifications), 'Notifications retrieved successfully.');
    }

    public function myNotifications(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->getForUser($request->user(), $request->query());

        return $this->success(NotificationResource::collection($notifications), 'Your notifications retrieved successfully.');
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->markAsRead($notification, $request->user());

        return $this->success(null, 'Notification marked as read.');
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->delete($notification, $request->user());

        return $this->success(null, 'Notification deleted successfully.');
    }
}
