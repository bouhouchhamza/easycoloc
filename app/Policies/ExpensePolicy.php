<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpensePolicy
{
    public function delete(User $user, Expense $expense): bool
    {
        if ($user->hasRole('global_admin')) {
            return true;
        }

        if ((int) $expense->user_id === (int) $user->id) {
            return true;
        }

        return DB::table('colocation_user')
            ->where('colocation_id', $expense->colocation_id)
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->whereNull('left_at')
            ->exists();
    }
}
