<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentPolicy
{
    public function create(User $user, Colocation $colocation): bool
    {
        return DB::table('colocation_user')
            ->where('colocation_id', $colocation->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }
}
