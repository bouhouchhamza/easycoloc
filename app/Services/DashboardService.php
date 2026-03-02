<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;

class DashboardService
{
    public function forUser(User $user): array
    {
        $activeColocation = $user->activeColocations()
            ->select('colocations.id', 'colocations.name')
            ->latest('colocations.id')
            ->first();

        $myPaidInActiveColocation = 0.0;
        $activeColocationExpensesTotal = 0.0;

        if ($activeColocation) {
            $myPaidInActiveColocation = (float) Expense::query()
                ->where('colocation_id', $activeColocation->id)
                ->where('user_id', $user->id)
                ->sum('amount');

            $activeColocationExpensesTotal = (float) Expense::query()
                ->where('colocation_id', $activeColocation->id)
                ->sum('amount');
        }

        $myExpensesTotal = $activeColocation
            ? $activeColocationExpensesTotal
            : (float) Expense::query()->where('user_id', $user->id)->sum('amount');

        return [
            'activeColocation' => $activeColocation,
            'myExpensesTotal' => $myExpensesTotal,
            'myPaidInActiveColocation' => $myPaidInActiveColocation,
            'activeColocationExpensesTotal' => $activeColocationExpensesTotal,
            'myReputation' => (int) $user->reputation,
        ];
    }
}
