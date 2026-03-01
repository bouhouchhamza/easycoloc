<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;

class DashboardService
{
    public function forUser(User $user): array
    {
        return [
            'activeColocation' => $user->activeColocations()->latest('colocations.id')->first(),
            'myExpensesTotal' => Expense::query()->where('user_id', $user->id)->sum('amount'),
            'myReputation' => (int) $user->reputation,
        ];
    }
}
