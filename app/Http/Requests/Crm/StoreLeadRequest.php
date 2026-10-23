<?php

namespace App\Http\Requests\Crm;

use App\Enums\Channel;
use App\Enums\LeadLevel;
use App\Enums\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:leads,email'],
            'whatsapp' => ['nullable', 'string', 'min:10', 'max:13'],
            'level' => ['required', Rule::enum(LeadLevel::class)],
            'plan_interest' => ['nullable', Rule::enum(Plan::class)],
            'channel' => ['required', Rule::enum(Channel::class)],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'goal' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['email.unique' => 'Já existe um lead com esse e-mail.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'whatsapp' => preg_replace('/\D/', '', (string) $this->input('whatsapp')) ?: null,
        ]);
    }
}
