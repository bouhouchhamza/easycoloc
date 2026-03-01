<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $colocationId = $this->route('colocation')?->id;

        return [
            'title' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('colocation_id', $colocationId),
            ],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $colocation = $this->route('colocation');
            $payerId = (int) ($this->input('user_id') ?: $this->user()?->id);

            if (! $colocation || $payerId <= 0) {
                return;
            }

            $isActiveMember = DB::table('colocation_user')
                ->where('colocation_id', $colocation->id)
                ->where('user_id', $payerId)
                ->whereNull('left_at')
                ->exists();

            if (! $isActiveMember) {
                $validator->errors()->add('user_id', 'The payer must be an active member of this colocation.');
            }
        });
    }
}
