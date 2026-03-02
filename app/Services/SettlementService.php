<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettlementService
{
    public function calculate(Colocation $colocation): array
    {
        return $this->calculateSettlement($colocation);
    }

    public function calculateSettlement(Colocation $colocation): array
    {
        $members = $colocation->activeUsers()
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        if ($members->isEmpty()) {
            return [
                'members' => collect(),
                'total_expenses' => 0.0,
                'share_per_member' => 0.0,
                'totals_paid' => [],
                'balances' => [],
                'transfers' => [],
            ];
        }

        $memberIds = $members->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values();

        $joinedAtColumn = Schema::hasColumn('colocation_user', 'joined_at') ? 'joined_at' : 'created_at';

        $membershipPeriods = [];
        foreach ($memberIds as $memberId) {
            $membershipPeriods[$memberId] = ['joined_at' => null, 'left_at' => null];
        }

        $memberships = DB::table('colocation_user')
            ->where('colocation_id', $colocation->id)
            ->whereIn('user_id', $memberIds)
            ->selectRaw("user_id, {$joinedAtColumn} as joined_at, left_at")
            ->get();

        foreach ($memberships as $membership) {
            $membershipPeriods[(int) $membership->user_id] = [
                'joined_at' => $membership->joined_at ? Carbon::parse($membership->joined_at) : null,
                'left_at' => $membership->left_at ? Carbon::parse($membership->left_at) : null,
            ];
        }

        $totalsPaidCents = array_fill_keys($memberIds->all(), 0);
        $totalsOwedCents = array_fill_keys($memberIds->all(), 0);

        $expenses = Expense::query()
            ->where('colocation_id', $colocation->id)
            ->whereIn('user_id', $memberIds)
            ->select(['id', 'colocation_id', 'user_id', 'amount', 'expense_date', 'created_at', 'category_id'])
            ->orderByRaw('COALESCE(expense_date, created_at)')
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            $hasExplicitExpenseDate = ! empty($expense->expense_date);
            $expenseDate = $hasExplicitExpenseDate
                ? Carbon::parse($expense->expense_date)->startOfDay()
                : Carbon::parse($expense->created_at);

            $activeMemberIds = $memberIds
                ->filter(function (int $memberId) use ($membershipPeriods, $expenseDate, $hasExplicitExpenseDate): bool {
                    $period = $membershipPeriods[$memberId] ?? ['joined_at' => null, 'left_at' => null];
                    $joinedAt = $period['joined_at'];
                    $leftAt = $period['left_at'];

                    if ($hasExplicitExpenseDate) {
                        $joinedAt = $joinedAt ? $joinedAt->copy()->startOfDay() : null;
                        $leftAt = $leftAt ? $leftAt->copy()->endOfDay() : null;
                    }

                    if ($joinedAt !== null && $expenseDate->lt($joinedAt)) {
                        return false;
                    }

                    if ($leftAt !== null && $expenseDate->gt($leftAt)) {
                        return false;
                    }

                    return true;
                })
                ->sort()
                ->values();

            if ($activeMemberIds->isEmpty()) {
                continue;
            }

            $amountCents = (int) round((float) $expense->amount * 100);
            if ($amountCents <= 0) {
                continue;
            }

            $payerId = (int) $expense->user_id;
            $totalsPaidCents[$payerId] = ($totalsPaidCents[$payerId] ?? 0) + $amountCents;

            $membersCount = $activeMemberIds->count();
            $baseShareCents = intdiv($amountCents, $membersCount);
            $remainderCents = $amountCents % $membersCount;

            foreach ($activeMemberIds as $index => $memberId) {
                $totalsOwedCents[$memberId] += $baseShareCents;

                if ($index < $remainderCents) {
                    $totalsOwedCents[$memberId] += 1;
                }
            }
        }

        $outgoingPayments = Payment::query()
            ->where('colocation_id', $colocation->id)
            ->whereIn('from_user_id', $memberIds)
            ->selectRaw('from_user_id, SUM(amount) as total_amount')
            ->groupBy('from_user_id')
            ->pluck('total_amount', 'from_user_id');

        $incomingPayments = Payment::query()
            ->where('colocation_id', $colocation->id)
            ->whereIn('to_user_id', $memberIds)
            ->selectRaw('to_user_id, SUM(amount) as total_amount')
            ->groupBy('to_user_id')
            ->pluck('total_amount', 'to_user_id');

        $balances = [];
        $normalizedTotalsPaid = [];

        foreach ($memberIds as $memberId) {
            $expensePaidCents = $totalsPaidCents[$memberId] ?? 0;
            $owedCents = $totalsOwedCents[$memberId] ?? 0;
            $paidOutCents = (int) round((float) ($outgoingPayments[$memberId] ?? 0.0) * 100);
            $receivedCents = (int) round((float) ($incomingPayments[$memberId] ?? 0.0) * 100);

            $normalizedTotalsPaid[$memberId] = round($expensePaidCents / 100, 2);
            $balances[$memberId] = round(($expensePaidCents + $paidOutCents - $receivedCents - $owedCents) / 100, 2);
        }

        $transfers = $this->simplifyBalances($balances, $members->pluck('name', 'id'));

        return [
            'members' => $members,
            'total_expenses' => round(array_sum($totalsPaidCents) / 100, 2),
            'share_per_member' => $members->count() > 0
                ? round((array_sum($totalsOwedCents) / 100) / $members->count(), 2)
                : 0.0,
            'totals_paid' => $normalizedTotalsPaid,
            'balances' => $balances,
            'transfers' => $transfers,
        ];
    }

    private function simplifyBalances(array $balances, Collection $names): array
    {
        $debtors = [];
        $creditors = [];

        foreach ($balances as $userId => $balance) {
            if ($balance < 0) {
                $debtors[] = ['user_id' => $userId, 'amount' => round(abs($balance), 2)];
            } elseif ($balance > 0) {
                $creditors[] = ['user_id' => $userId, 'amount' => round($balance, 2)];
            }
        }

        usort($debtors, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        $transfers = [];
        $i = 0;
        $j = 0;

        while ($i < count($debtors) && $j < count($creditors)) {
            $amount = min($debtors[$i]['amount'], $creditors[$j]['amount']);
            $amount = round($amount, 2);

            if ($amount <= 0) {
                break;
            }

            $fromUserId = $debtors[$i]['user_id'];
            $toUserId = $creditors[$j]['user_id'];

            $transfers[] = [
                'from_user_id' => $fromUserId,
                'from_name' => $names[$fromUserId] ?? ('User #'.$fromUserId),
                'to_user_id' => $toUserId,
                'to_name' => $names[$toUserId] ?? ('User #'.$toUserId),
                'amount' => $amount,
            ];

            $debtors[$i]['amount'] = round($debtors[$i]['amount'] - $amount, 2);
            $creditors[$j]['amount'] = round($creditors[$j]['amount'] - $amount, 2);

            if ($debtors[$i]['amount'] <= 0.009) {
                $i++;
            }

            if ($creditors[$j]['amount'] <= 0.009) {
                $j++;
            }
        }

        return $transfers;
    }
}
