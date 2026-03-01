<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinByTokenRequest;
use App\Http\Requests\RespondInvitationRequest;
use App\Http\Requests\SendInvitationEmailRequest;
use App\Http\Requests\StoreColocationRequest;
use App\Models\Colocation;
use App\Models\User;
use App\Services\ColocationService;
use App\Services\ExpenseService;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ColocationController extends Controller
{
    public function index(Request $request): View
    {
        $colocations = $request->user()
            ->colocations()
            ->with('owner:id,name')
            ->wherePivotNull('left_at')
            ->where('colocations.status', 'active')
            ->orderByDesc('colocations.id')
            ->get();

        return view('colocations.index', [
            'colocations' => $colocations,
        ]);
    }

    public function create(): View
    {
        return view('colocations.create');
    }

    public function join(): View
    {
        return view('colocations.join');
    }

    public function store(StoreColocationRequest $request, ColocationService $service): RedirectResponse
    {
        $this->authorize('join', Colocation::class);

        $colocation = $service->createForOwner($request->user(), $request->validated('name'));

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'Colocation created successfully.');
    }

    public function sendInvitationEmail(
        SendInvitationEmailRequest $request,
        Colocation $colocation,
        InvitationService $service
    ): RedirectResponse {
        $this->authorize('update', $colocation);

        $message = $service->sendInvitationEmail($request->user(), $colocation, $request->validated('email'));

        return back()->with('success', $message);
    }

    public function show(Request $request, Colocation $colocation, ExpenseService $expenseService): View
    {
        $this->authorize('view', $colocation);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = $validated['month'] ?? null;

        $colocation->load([
            'owner:id,name',
            'activeUsers' => fn ($query) => $query->select('users.id', 'users.name', 'users.reputation'),
        ]);

        return view('colocations.show', [
            'colocation' => $colocation,
            'members' => $colocation->activeUsers,
            'month' => $month,
            'expenses' => $expenseService->list($colocation, $month),
            'categories' => $colocation->categories()->orderBy('name')->get(),
        ]);
    }

    public function submitToken(JoinByTokenRequest $request): RedirectResponse
    {
        return redirect()->route('colocations.invite', [
            'token' => $request->validated('token'),
        ]);
    }

    public function invite(string $token, Request $request, ColocationService $service): View
    {
        $colocation = $service->getInvitationByToken($token);

        return view('colocations.invite', [
            'token' => $token,
            'colocation' => $colocation,
            'hasActiveColocation' => $service->userHasActiveColocation($request->user()),
        ]);
    }

    public function respondInvitation(
        RespondInvitationRequest $request,
        ColocationService $service
    ): RedirectResponse {
        $data = $request->validated();
        $accept = $data['action'] === 'accept';

        if ($accept) {
            $this->authorize('join', Colocation::class);
        }

        $colocation = $service->respondToInvitation($request->user(), $data['token'], $accept);

        if (! $colocation) {
            return back()->withErrors([
                'token' => 'Invitation token is invalid or expired.',
            ]);
        }

        if (! $accept) {
            return redirect()
                ->route('colocations.index')
                ->with('success', 'Invitation refused.');
        }

        return redirect()
            ->route('colocations.show', $colocation)
            ->with('success', 'You joined the colocation.');
    }

    public function leave(Colocation $colocation, Request $request, ColocationService $service): RedirectResponse
    {
        $this->authorize('leave', $colocation);

        $service->leave($request->user(), $colocation);

        return redirect()
            ->route('colocations.index')
            ->with('success', 'You left the colocation.');
    }

    public function removeMember(
        Colocation $colocation,
        User $user,
        Request $request,
        ColocationService $service
    ): RedirectResponse {
        $this->authorize('update', $colocation);

        $service->removeMember($request->user(), $colocation, $user);

        return back()->with('success', 'Member removed successfully.');
    }

    public function cancel(Colocation $colocation, Request $request, ColocationService $service): RedirectResponse
    {
        $this->authorize('delete', $colocation);

        $service->cancel($request->user(), $colocation);

        return redirect()
            ->route('colocations.index')
            ->with('success', 'Colocation cancelled.');
    }
}
