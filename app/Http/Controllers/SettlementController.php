<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Services\SettlementService;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function show(Colocation $colocation, SettlementService $service): View
    {
        $this->authorize('view', $colocation);

        $colocation->load([
            'activeUsers:id,name',
            'expenses' => fn ($query) => $query
                ->select(['id', 'colocation_id', 'user_id', 'category_id', 'amount', 'expense_date', 'created_at'])
                ->orderByRaw('COALESCE(expense_date, created_at)')
                ->orderBy('id'),
        ]);

        return view('colocations.settlement', [
            'colocation' => $colocation,
            'summary' => $service->calculate($colocation),
        ]);
    }
}
