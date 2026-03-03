<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ColocationPolicy
{
    public function view(User $user, Colocation $colocation): bool
    {
        if ($user->hasRole('global_admin')) {
            return true;
        }

        return $this->isActiveMember($user, $colocation);
    }

    public function update(User $user, Colocation $colocation): bool
    {
        if ($user->hasRole('global_admin')) {
            return true;
        }

        return $this->isOwner($user, $colocation);
    }

    public function delete(User $user, Colocation $colocation): bool
    {
        if ($user->hasRole('global_admin')) {
            return true;
        }

        return $this->isOwner($user, $colocation);
    }

    public function join(User $user): bool
    {
        if ($user->hasRole('global_admin')) {
            return true;
        }

        return ! DB::table('colocation_user')
            ->join('colocations', 'colocations.id', '=', 'colocation_user.colocation_id')
            ->where('colocation_user.user_id', $user->id)
            ->whereNull('colocation_user.left_at')
            ->where('colocations.status', 'active')
            ->exists();
    }

    public function leave(User $user, Colocation $colocation): bool
    {
        if ($user->hasRole('global_admin')) {
            return true;
        }

        return $this->isActiveMember($user, $colocation)
            && ! $this->isOwner($user, $colocation);
    }

    private function isActiveMember(User $user, Colocation $colocation): bool
    {
        return DB::table('colocation_user')
            ->where('colocation_id', $colocation->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }

    private function isOwner(User $user, Colocation $colocation): bool
    {
        return DB::table('colocation_user')
            ->where('colocation_id', $colocation->id)
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->whereNull('left_at')
            ->exists();
    }
}
