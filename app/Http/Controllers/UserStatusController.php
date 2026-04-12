<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class UserStatusController extends Controller
{
    public function __construct(
        private UserStatusService $statusService
    ) {
    }

    /**
     * Get the current user's status.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $statusWithMeta = $this->statusService->getStatusWithMetadata($user->id);

        if ($statusWithMeta === null) {
            // User is offline/inactive
            return response()->json([
                'status' => 'offline',
                'updated_at' => now()->toIso8601String(),
                'heartbeat' => now()->toIso8601String(),
            ]);
        }

        return response()->json($statusWithMeta);
    }

    /**
     * Set the current user's status.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'status' => ['required', 'in:online,away,busy,offline'],
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        $statusEnum = \App\Enums\UserStatusEnum::from($request->input('status'));

        $previousStatus = $this->statusService->getStatus($user);

        // Set the new status
        $this->statusService->setStatus($user, $statusEnum);

        // Log the activity
        \App\Models\UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'from_status' => $previousStatus->value,
            'to_status' => $statusEnum->value,
            'metadata' => $request->input('message') ? ['message' => $request->input('message')] : null,
        ]);

        // Broadcast the status change
        broadcast(new \App\Events\UserStatusUpdatedEvent(
            $user,
            $statusEnum,
            $request->input('message', ''),
        ));

        return response()->json([
            'status' => $statusEnum->value,
            'updated_at' => now()->toIso8601String(),
            'heartbeat' => now()->toIso8601String(),
        ]);
    }
}
