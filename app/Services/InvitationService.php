<?php

namespace App\Services;

use App\Mail\ColocationInvitationMail;
use App\Models\Colocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class InvitationService
{
    public function __construct(
        private readonly ColocationService $colocationService
    ) {
    }

    public function resolveByToken(string $token): ?Colocation
    {
        return $this->colocationService->getInvitationByToken($token);
    }

    public function acceptInvitation(User $user, string $token): array
    {
        $colocation = $this->resolveByToken($token);

        if (! $colocation) {
            throw ValidationException::withMessages([
                'token' => 'Invitation token is invalid or expired.',
            ]);
        }

        $isAlreadyMember = DB::table('colocation_user')
            ->where('colocation_id', $colocation->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();

        if ($isAlreadyMember) {
            return [
                'colocation' => $colocation,
                'already_member' => true,
            ];
        }

        $joinedColocation = $this->colocationService->respondToInvitation($user, $token, true);

        if (! $joinedColocation) {
            throw ValidationException::withMessages([
                'token' => 'Invitation token is invalid or expired.',
            ]);
        }

        return [
            'colocation' => $joinedColocation,
            'already_member' => false,
        ];
    }

    public function sendInvitationEmail(User $actor, Colocation $colocation, string $email): string
    {
        if ((int) $colocation->owner_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'colocation' => 'Only the owner can send invitations.',
            ]);
        }

        if ($colocation->status !== 'active') {
            throw ValidationException::withMessages([
                'colocation' => 'Invitations are only available for active colocations.',
            ]);
        }

        try {
            Mail::to($email)->send(new ColocationInvitationMail($colocation));

            return 'Invitation email sent.';
        } catch (TransportExceptionInterface $exception) {
            Log::warning('SMTP failed when sending colocation invitation email.', [
                'colocation_id' => $colocation->id,
                'recipient' => $email,
                'error' => $exception->getMessage(),
            ]);

            Mail::mailer('log')->to($email)->send(new ColocationInvitationMail($colocation));

            return 'SMTP unavailable. Invitation email logged.';
        }
    }
}
