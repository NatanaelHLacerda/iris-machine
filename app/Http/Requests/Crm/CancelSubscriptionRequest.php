<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class CancelSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'canceled_at' => ['required', 'date', 'before_or_equal:today'],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
