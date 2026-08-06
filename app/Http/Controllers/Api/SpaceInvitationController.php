<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvitationStatus;
use App\Enums\NotificationType;
use App\Enums\SpaceMemberRole;
use App\Enums\SpaceType;
use App\Events\InvitationCreated;
use App\Events\InvitationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSpaceInvitationRequest;
use App\Models\AppNotification;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\SpaceMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpaceInvitationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invitations = SpaceInvitation::query()
            ->with(['space:space_id,name,type', 'inviter:user_id,name,email'])
            ->where('invited_user_id', $request->user()->getKey())
            ->where('status', InvitationStatus::Pending)
            ->latest()
            ->get();

        return response()->json([
            'data' => $invitations,
        ]);
    }

    public function store(StoreSpaceInvitationRequest $request, Space $space): JsonResponse
    {
        $user = $request->user();

        $this->ensureCanInvite($user, $space);

        $invitee = User::query()->where('email', $request->string('email')->toString())->firstOrFail();

        if ($invitee->getKey() === $user->getKey()) {
            throw ValidationException::withMessages([
                'email' => ['You cannot invite yourself.'],
            ]);
        }

        $alreadyMember = SpaceMember::query()
            ->where('space_id', $space->getKey())
            ->where('user_id', $invitee->getKey())
            ->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => ['This user is already a member of the space.'],
            ]);
        }

        $pendingExists = SpaceInvitation::query()
            ->where('space_id', $space->getKey())
            ->where('invited_user_id', $invitee->getKey())
            ->where('status', InvitationStatus::Pending)
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'email' => ['This user already has a pending invitation.'],
            ]);
        }

        $invitation = SpaceInvitation::query()->create([
            'space_id' => $space->getKey(),
            'invited_by' => $user->getKey(),
            'invited_user_id' => $invitee->getKey(),
            'status' => InvitationStatus::Pending,
        ]);

        $invitation->load(['space:space_id,name,type', 'invitedUser:user_id,name,email', 'inviter:user_id,name,email']);

        InvitationCreated::dispatch($invitation);

        AppNotification::create([
            'user_id' => $invitee->getKey(),
            'title' => 'Space invitation',
            'message' => sprintf('%s invited you to join %s.', $user->name, $space->name),
            'type' => NotificationType::System,
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'data' => $invitation,
        ], 201);
    }

    public function accept(Request $request, SpaceInvitation $invitation): JsonResponse
    {
        $user = $request->user();

        if ($invitation->invited_user_id !== $user->getKey()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($invitation->status !== InvitationStatus::Pending) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation is no longer pending.'],
            ]);
        }

        DB::transaction(function () use ($invitation, $user) {
            $invitation->update(['status' => InvitationStatus::Accepted]);

            SpaceMember::query()->firstOrCreate(
                [
                    'space_id' => $invitation->space_id,
                    'user_id' => $user->getKey(),
                ],
                [
                    'role' => SpaceMemberRole::Member,
                ]
            );

            AppNotification::create([
                'user_id' => $invitation->invited_by,
                'title' => 'Invitation accepted',
                'message' => sprintf('%s joined %s.', $user->name, $invitation->space->name),
                'type' => NotificationType::System,
                'is_read' => false,
            ]);
        });

        $invitation = $invitation->fresh()->load(['space:space_id,name,type', 'inviter:user_id,name,email', 'invitedUser:user_id,name,email']);
        InvitationUpdated::dispatch($invitation);

        return response()->json([
            'message' => 'Invitation accepted.',
            'data' => $invitation,
        ]);
    }

    public function decline(Request $request, SpaceInvitation $invitation): JsonResponse
    {
        $user = $request->user();

        if ($invitation->invited_user_id !== $user->getKey()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($invitation->status !== InvitationStatus::Pending) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation is no longer pending.'],
            ]);
        }

        $invitation->update(['status' => InvitationStatus::Declined]);

        $invitation = $invitation->fresh()->load(['space:space_id,name,type', 'inviter:user_id,name,email', 'invitedUser:user_id,name,email']);
        InvitationUpdated::dispatch($invitation);

        AppNotification::create([
            'user_id' => $invitation->invited_by,
            'title' => 'Invitation declined',
            'message' => sprintf('%s declined the invite to %s.', $user->name, $invitation->space->name),
            'type' => NotificationType::System,
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Invitation declined.',
            'data' => $invitation,
        ]);
    }

    private function ensureCanInvite(User $user, Space $space): void
    {
        if ($space->type !== SpaceType::Shared) {
            throw ValidationException::withMessages([
                'space' => ['Only shared spaces can send invitations.'],
            ]);
        }

        $isOwner = SpaceMember::query()
            ->where('space_id', $space->getKey())
            ->where('user_id', $user->getKey())
            ->where('role', SpaceMemberRole::Owner)
            ->exists();

        if (! $isOwner && $space->user_id !== $user->getKey()) {
            throw ValidationException::withMessages([
                'space' => ['Only the space owner can send invitations.'],
            ]);
        }
    }
}
