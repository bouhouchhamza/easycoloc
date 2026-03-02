<?php

namespace App\Http\Controllers;

use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function landing(string $token, Request $request, InvitationService $service): View|RedirectResponse
    {
        $colocation = $service->resolveByToken($token);

        if (! $colocation) {
            $this->clearPendingInvitation($request);

            abort(404);
        }

        $request->session()->put('pending_invitation_token', $token);
        $request->session()->put('url.intended', route('invitations.accept', ['token' => $token], false));

        if ($request->user()) {
            return redirect()->route('invitations.accept', ['token' => $token]);
        }

        return view('invitations.landing', [
            'token' => $token,
            'colocation' => $colocation,
        ]);
    }

    public function accept(string $token, Request $request, InvitationService $service): RedirectResponse
    {
        try {
            $result = $service->acceptInvitation($request->user(), $token);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('invitations.landing', ['token' => $token])
                ->withErrors($exception->errors());
        }

        $this->clearPendingInvitation($request);

        $message = $result['already_member']
            ? 'You are already a member of this colocation.'
            : 'Invitation accepted. You joined the colocation.';

        return redirect()
            ->route('colocations.show', $result['colocation'])
            ->with('success', $message);
    }

    private function clearPendingInvitation(Request $request): void
    {
        $request->session()->forget([
            'pending_invitation_token',
            'url.intended',
        ]);
    }
}

