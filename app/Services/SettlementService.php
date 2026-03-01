<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Support\Collection;

class SettlementService
{
    public function calculate(Colocation $colocation): array
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

        $memberIds = $members->pluck('id');

        $totalsPaid = Expense::query()
            ->where('colocation_id', $colocation->id)
            ->whereIn('user_id', $memberIds)
            ->selectRaw('user_id, SUM(amount) as total_amount')
            ->groupBy('user_id')
            ->pluck('total_amount', 'user_id');

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

        $totalExpenses = (float) $totalsPaid->sum();
        $share = $totalExpenses / $members->count();

        $balances = [];
        $normalizedTotalsPaid = [];

        foreach ($memberIds as $memberId) {
            $expensePaid = (float) ($totalsPaid[$memberId] ?? 0.0);
            $paidOut = (float) ($outgoingPayments[$memberId] ?? 0.0);
            $received = (float) ($incomingPayments[$memberId] ?? 0.0);

            $normalizedTotalsPaid[$memberId] = round($expensePaid, 2);
            // Payments adjust balances:
            // - payer (from_user) gets +amount because they settled part of their debt
            // - receiver (to_user) gets -amount because they recovered part of their credit
            $balances[$memberId] = round(($expensePaid + $paidOut - $received) - $share, 2);
        }

        $transfers = $this->simplifyBalances($balances, $members->pluck('name', 'id'));

        return [
            'members' => $members,
            'total_expenses' => round($totalExpenses, 2),
            'share_per_member' => round($share, 2),
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
