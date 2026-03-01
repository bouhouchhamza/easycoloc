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

        return view('colocations.settlement', [
            'colocation' => $colocation->load('activeUsers:id,name'),
            'summary' => $service->calculate($colocation),
        ]);
    }
}
