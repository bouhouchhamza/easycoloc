<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ColocationService
{
    public function __construct(
        private readonly SettlementService $settlementService
    ) {
    }

    public function userHasActiveColocation(User $user): bool
    {
        return DB::table('colocation_user')
            ->join('colocations', 'colocations.id', '=', 'colocation_user.colocation_id')
            ->where('colocation_user.user_id', $user->id)
            ->whereNull('colocation_user.left_at')
            ->where('colocations.status', 'active')
            ->exists();
    }

    public function createForOwner(User $owner, string $name): Colocation
    {
        return DB::transaction(function () use ($owner, $name) {
            if ($this->userHasActiveColocation($owner)) {
                throw ValidationException::withMessages([
                    'name' => 'You already belong to an active colocation.',
                ]);
            }

            $colocation = Colocation::create([
                'name' => $name,
                'owner_id' => $owner->id,
                'invite_token' => (string) Str::uuid(),
                'status' => 'active',
            ]);

            $colocation->users()->attach($owner->id, [
                'role' => 'owner',
                'left_at' => null,
            ]);

            foreach (['Rent', 'Utilities', 'Groceries', 'Internet', 'Other'] as $categoryName) {
                Category::query()->create([
                    'colocation_id' => $colocation->id,
                    'name' => $categoryName,
                ]);
            }

            return $colocation->fresh(['owner', 'users']);
        });
    }

    public function getInvitationByToken(string $token): ?Colocation
    {
        return Colocation::query()
            ->with('owner')
            ->where('invite_token', $token)
            ->where('status', 'active')
            ->first();
    }

    public function respondToInvitation(User $user, string $token, bool $accept): ?Colocation
    {
        $colocation = $this->getInvitationByToken($token);

        if (! $colocation) {
            return null;
        }

        if (! $accept) {
            return $colocation;
        }

        return DB::transaction(function () use ($user, $colocation) {
            if ($this->userHasActiveColocation($user)) {
                throw ValidationException::withMessages([
                    'token' => 'You already belong to an active colocation.',
                ]);
            }

            $existing = DB::table('colocation_user')
                ->where('colocation_id', $colocation->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($existing) {
                $colocation->users()->updateExistingPivot($user->id, [
                    'role' => 'member',
                    'left_at' => null,
                ]);
            } else {
                $colocation->users()->attach($user->id, [
                    'role' => 'member',
                    'left_at' => null,
                ]);
            }

            return $colocation->fresh(['owner', 'users']);
        });
    }

    public function leave(User $user, Colocation $colocation): void
    {
        DB::transaction(function () use ($user, $colocation) {
            if ((int) $colocation->owner_id === (int) $user->id) {
                throw ValidationException::withMessages([
                    'colocation' => 'Owner cannot leave an active colocation. Cancel it instead.',
                ]);
            }

            if (! $this->isActiveMember($user, $colocation)) {
                throw ValidationException::withMessages([
                    'colocation' => 'You are not an active member of this colocation.',
                ]);
            }

            $balance = $this->memberBalance($colocation, $user->id);
            $this->applyReputationAfterExit($user, $balance);

            $colocation->users()->updateExistingPivot($user->id, [
                'left_at' => now(),
                'role' => 'member',
            ]);
        });
    }

    public function removeMember(User $actor, Colocation $colocation, User $member): void
    {
        DB::transaction(function () use ($actor, $colocation, $member) {
            $this->guardOwnerAction($actor, $colocation);

            if ((int) $member->id === (int) $colocation->owner_id) {
                throw ValidationException::withMessages([
                    'member' => 'Owner cannot be removed.',
                ]);
            }

            if (! $this->isActiveMember($member, $colocation)) {
                throw ValidationException::withMessages([
                    'member' => 'User is not an active member.',
                ]);
            }

            $settlement = $this->settlementService->calculate($colocation);
            $balance = (float) ($settlement['balances'][$member->id] ?? 0.0);

            if ($balance < 0) {
                $this->transferMemberDebtToOwner($colocation, $member->id, $settlement['transfers'] ?? []);
            }

            $this->applyReputationAfterExit($member, $balance);

            $colocation->users()->updateExistingPivot($member->id, [
                'left_at' => now(),
                'role' => 'member',
            ]);
        });
    }

    private function transferMemberDebtToOwner(Colocation $colocation, int $memberId, array $transfers): void
    {
        foreach ($transfers as $transfer) {
            $fromUserId = (int) ($transfer['from_user_id'] ?? 0);
            $toUserId = (int) ($transfer['to_user_id'] ?? 0);
            $amount = round((float) ($transfer['amount'] ?? 0), 2);

            if ($fromUserId !== $memberId || $amount <= 0) {
                continue;
            }

            if ($toUserId === (int) $colocation->owner_id) {
                continue;
            }

            /*
             * Debt transfer rule when owner removes a debtor:
             * we add an internal adjustment payment "creditor -> owner".
             * In settlement math, that makes the owner absorb the removed member's debt
             * while preserving a balanced sum for active members.
             */
            Payment::query()->create([
                'colocation_id' => $colocation->id,
                'from_user_id' => $toUserId,
                'to_user_id' => $colocation->owner_id,
                'amount' => $amount,
                'paid_at' => now(),
            ]);
        }
    }

    public function cancel(User $actor, Colocation $colocation): void
    {
        DB::transaction(function () use ($actor, $colocation) {
            $this->guardOwnerAction($actor, $colocation);

            $colocation->forceFill(['status' => 'cancelled'])->save();

            DB::table('colocation_user')
                ->where('colocation_id', $colocation->id)
                ->whereNull('left_at')
                ->update([
                    'left_at' => now(),
                    'updated_at' => now(),
                ]);
        });
    }

    private function isActiveMember(User $user, Colocation $colocation): bool
    {
        return DB::table('colocation_user')
            ->where('colocation_id', $colocation->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }

    private function guardOwnerAction(User $actor, Colocation $colocation): void
    {
        if ((int) $colocation->owner_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'colocation' => 'Only the owner can perform this action.',
            ]);
        }
    }

    private function memberBalance(Colocation $colocation, int $userId): float
    {
        $settlement = $this->settlementService->calculate($colocation);

        return (float) ($settlement['balances'][$userId] ?? 0.0);
    }

    private function applyReputationAfterExit(User $user, float $balance): void
    {
        $delta = $balance < 0 ? -1 : 1;

        $user->forceFill([
            'reputation' => (int) $user->reputation + $delta,
        ])->save();
    }
}
