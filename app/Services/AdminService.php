<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminService
{
    public function dashboardData(): array
    {
        return [
            'stats' => [
                'users' => User::query()->count(),
                'colocations' => Colocation::query()->count(),
                'expenses' => Expense::query()->count(),
            ],
            'users' => User::query()->orderBy('name')->get(),
        ];
    }

    public function updateBanStatus(User $user, bool $isBanned): void
    {
        if ($user->isGlobalAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Global admin cannot be banned.',
            ]);
        }

        $user->forceFill([
            'is_banned' => $isBanned,
        ])->save();
    }
}
