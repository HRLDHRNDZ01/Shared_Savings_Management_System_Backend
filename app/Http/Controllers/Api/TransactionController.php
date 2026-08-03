<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDepositRequest;
use App\Http\Requests\Api\StoreWithdrawalRequest;
use App\Models\AppNotification;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $recent = $user->transactions()
            ->with('space:space_id,name')
            ->latest('occurred_at')
            ->limit(10)
            ->get();

        $totalDeposits = $user->transactions()
            ->where('type', TransactionType::Deposit)
            ->sum('amount');

        $totalWithdrawals = $user->transactions()
            ->where('type', TransactionType::Withdrawal)
            ->sum('amount');

        return response()->json([
            'data' => [
                'recent_transactions' => $recent,
                'total_deposits' => (float) $totalDeposits,
                'total_withdrawals' => (float) $totalWithdrawals,
            ],
        ]);
    }

    public function storeDeposit(StoreDepositRequest $request): JsonResponse
    {
        $user = $request->user();
        $amount = (float) $request->input('amount');
        $userId = $user->getKey();

        $result = DB::transaction(function () use ($request, $user, $amount, $userId) {
            $space = Space::query()
                ->whereKey($request->integer('space_id'))
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
                })
                ->lockForUpdate()
                ->firstOrFail();

            $transaction = $user->transactions()->create([
                'space_id' => $space->getKey(),
                'type' => TransactionType::Deposit,
                'amount' => $amount,
                'note' => $request->input('note'),
                'occurred_at' => now(),
            ]);

            $space->balance = (float) $space->balance + $amount;
            $space->save();

            $this->notifySpaceMembers(
                space: $space,
                actor: $user,
                action: 'deposit',
                type: NotificationType::Deposit,
                amount: $amount,
                titleForActor: 'Deposit received',
                messageForActor: sprintf('You deposited %.2f into %s.', $amount, $space->name),
                titleForMembers: sprintf('%s made a deposit', $user->name),
                messageForMembers: sprintf('%s deposited %.2f into %s.', $user->name, $amount, $space->name),
            );

            return [
                'transaction' => $transaction->load('space:space_id,name'),
                'space' => $space->fresh(),
            ];
        });

        return response()->json([
            'message' => 'Deposit added successfully.',
            'data' => $result,
        ], 201);
    }

    public function storeWithdrawal(StoreWithdrawalRequest $request): JsonResponse
    {
        $user = $request->user();
        $amount = (float) $request->input('amount');
        $userId = $user->getKey();

        $result = DB::transaction(function () use ($request, $user, $amount, $userId) {
            $space = Space::query()
                ->whereKey($request->integer('space_id'))
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
                })
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $space->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient balance for this withdrawal.'],
                ]);
            }

            $transaction = $user->transactions()->create([
                'space_id' => $space->getKey(),
                'type' => TransactionType::Withdrawal,
                'amount' => $amount,
                'note' => $request->input('note'),
                'occurred_at' => now(),
            ]);

            $space->balance = (float) $space->balance - $amount;
            $space->save();

            $this->notifySpaceMembers(
                space: $space,
                actor: $user,
                action: 'withdrawal',
                type: NotificationType::Withdrawal,
                amount: $amount,
                titleForActor: 'Withdrawal completed',
                messageForActor: sprintf('You withdrew %.2f from %s.', $amount, $space->name),
                titleForMembers: sprintf('%s made a withdrawal', $user->name),
                messageForMembers: sprintf('%s withdrew %.2f from %s.', $user->name, $amount, $space->name),
            );

            return [
                'transaction' => $transaction->load('space:space_id,name'),
                'space' => $space->fresh(),
            ];
        });

        return response()->json([
            'message' => 'Withdrawal completed successfully.',
            'data' => $result,
        ], 201);
    }

    private function notifySpaceMembers(
        Space $space,
        User $actor,
        string $action,
        NotificationType $type,
        float $amount,
        string $titleForActor,
        string $messageForActor,
        string $titleForMembers,
        string $messageForMembers,
    ): void {
        $memberIds = $space->members()->pluck('user_id');

        if ($memberIds->isEmpty()) {
            $memberIds = collect([$space->user_id]);
        }

        $memberIds = $memberIds
            ->push($space->user_id)
            ->unique()
            ->values();

        foreach ($memberIds as $memberId) {
            $isActor = (int) $memberId === (int) $actor->getKey();

            AppNotification::create([
                'user_id' => $memberId,
                'space_id' => $space->getKey(),
                'actor_user_id' => $actor->getKey(),
                'title' => $isActor ? $titleForActor : $titleForMembers,
                'message' => $isActor ? $messageForActor : $messageForMembers,
                'type' => $type,
                'action' => $action,
                'amount' => $amount,
                'is_read' => false,
            ]);
        }
    }
}
