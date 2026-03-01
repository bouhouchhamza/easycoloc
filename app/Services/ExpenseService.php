<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function create(Colocation $colocation, User $actor, array $data): Expense
    {
        $this->assertActiveMembership($actor, $colocation);

        $payer = isset($data['user_id'])
            ? User::query()->findOrFail($data['user_id'])
            : $actor;

        $this->assertActiveMembership($payer, $colocation);

        $categoryId = null;
        if (isset($data['category_id']) && $data['category_id']) {
            $category = Category::query()
                ->where('id', $data['category_id'])
                ->where('colocation_id', $colocation->id)
                ->first();

            if (! $category) {
                throw ValidationException::withMessages([
                    'category_id' => 'Invalid category selected for this colocation.',
                ]);
            }

            $categoryId = $category->id;
        }

        return Expense::query()->create([
            'title' => $data['title'] ?? null,
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'category_id' => $categoryId,
            'user_id' => $payer->id,
            'colocation_id' => $colocation->id,
        ]);
    }

    public function list(Colocation $colocation, ?string $month = null): Collection
    {
        return Expense::query()
            ->with(['payer:id,name', 'category:id,name'])
            ->where('colocation_id', $colocation->id)
            ->forMonth($month)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();
    }

    public function statsByCategory(Colocation $colocation, ?string $month = null): Collection
    {
        return Expense::query()
            ->selectRaw('categories.name as category_name, SUM(expenses.amount) as total_amount')
            ->join('categories', 'categories.id', '=', 'expenses.category_id')
            ->where('expenses.colocation_id', $colocation->id)
            ->forMonth($month)
            ->groupBy('categories.name')
            ->orderByDesc('total_amount')
            ->get();
    }

    public function statsByMonth(Colocation $colocation): Collection
    {
        return Expense::query()
            ->select(['expense_date', 'amount'])
            ->where('colocation_id', $colocation->id)
            ->orderByDesc('expense_date')
            ->get()
            ->groupBy(fn (Expense $expense) => $expense->expense_date->format('Y-m'))
            ->map(function (Collection $expenses, string $month) {
                return (object) [
                    'month' => $month,
                    'total_amount' => (float) $expenses->sum('amount'),
                ];
            })
            ->values();
    }

    public function delete(User $actor, Expense $expense): void
    {
        $isOwner = (int) $expense->colocation->owner_id === (int) $actor->id;
        $isPayer = (int) $expense->user_id === (int) $actor->id;

        if (! $isPayer && ! $isOwner) {
            throw ValidationException::withMessages([
                'expense' => 'You cannot delete this expense.',
            ]);
        }

        $expense->delete();
    }

    private function assertActiveMembership(User $user, Colocation $colocation): void
    {
        $isMember = DB::table('colocation_user')
            ->where('colocation_id', $colocation->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'colocation' => 'User is not an active member of this colocation.',
            ]);
        }
    }
}
