<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Colocation;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;

class ExpenseController extends Controller
{
    public function store(
        StoreExpenseRequest $request,
        Colocation $colocation,
        ExpenseService $service
    ): RedirectResponse {
        $this->authorize('view', $colocation);

        $service->create($colocation, $request->user(), $request->validated());

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'Expense added successfully.');
    }

    public function destroy(
        Colocation $colocation,
        Expense $expense,
        ExpenseService $service
    ): RedirectResponse {
        $this->authorize('view', $colocation);

        if ((int) $expense->colocation_id !== (int) $colocation->id) {
            abort(404);
        }

        $this->authorize('delete', $expense);

        $service->delete(request()->user(), $expense->loadMissing('colocation'));

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'Expense deleted successfully.');
    }
}
