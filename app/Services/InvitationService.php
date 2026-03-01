<?php

namespace App\Services;

use App\Mail\ColocationInvitationMail;
use App\Models\Colocation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class InvitationService
{
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
