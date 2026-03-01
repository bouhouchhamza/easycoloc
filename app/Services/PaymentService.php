<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly SettlementService $settlementService
    ) {
    }

    public function markAsPaid(
        User $actor,
        Colocation $colocation,
        int $fromUserId,
        int $toUserId,
        float $amount
    ): Payment {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than zero.',
            ]);
        }

        if ((int) $fromUserId === (int) $toUserId) {
            throw ValidationException::withMessages([
                'to_user_id' => 'You cannot pay yourself.',
            ]);
        }

        if ((int) $actor->id !== (int) $fromUserId) {
            throw ValidationException::withMessages([
                'payment' => 'You can only mark your own transaction as paid.',
            ]);
        }

        $from = User::query()->findOrFail($fromUserId);
        $toUser = User::query()->findOrFail($toUserId);

        $this->assertActiveMember($from, $colocation);
        $this->assertActiveMember($toUser, $colocation);

        $settlement = $this->settlementService->calculate($colocation);
        $dueTransfer = collect($settlement['transfers'])->first(
            fn (array $transfer) => (int) $transfer['from_user_id'] === (int) $from->id
                && (int) $transfer['to_user_id'] === (int) $toUserId
        );
        $dueAmount = (float) ($dueTransfer['amount'] ?? 0);

        if ((float) $dueAmount <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'No outstanding debt between these members.',
            ]);
        }

        if ((float) $amount > ((float) $dueAmount + 0.01)) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds the current outstanding debt.',
            ]);
        }

        return Payment::query()->create([
            'colocation_id' => $colocation->id,
            'from_user_id' => $from->id,
            'to_user_id' => $toUserId,
            'amount' => round($amount, 2),
            'paid_at' => now(),
        ]);
    }

    private function assertActiveMember(User $user, Colocation $colocation): void
    {
        $isMember = DB::table('colocation_user')
            ->where('colocation_id', $colocation->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'member' => 'Both users must be active members of the colocation.',
            ]);
        }
    }
}
