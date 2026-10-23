<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNextActionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'next_action' => ['nullable', 'required_with:next_action_at', 'string', 'max:160'],
            'next_action_at' => ['nullable', 'required_with:next_action', 'date'],
        ];
    }
}
