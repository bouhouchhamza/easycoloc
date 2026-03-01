<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'action' => ['required', 'in:accept,refuse'],
        ];
    }
}
