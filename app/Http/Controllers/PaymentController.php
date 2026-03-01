<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Colocation;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function store(
        StorePaymentRequest $request,
        Colocation $colocation,
        PaymentService $service
    ): RedirectResponse {
        $this->authorize('create', [Payment::class, $colocation]);

        $data = $request->validated();

        $service->markAsPaid(
            $request->user(),
            $colocation,
            (int) $data['from_user_id'],
            (int) $data['to_user_id'],
            (float) $data['amount']
        );

        return redirect()
            ->route('colocations.settlement.show', $colocation)
            ->with('success', 'Payment recorded successfully.');
    }
}
