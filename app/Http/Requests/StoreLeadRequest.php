<?php

namespace App\Http\Requests;

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
        $attribution = ['nullable', 'string', 'max:255'];

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'whatsapp' => ['nullable', 'string', 'min:10', 'max:13'],
            'level' => ['required', Rule::enum(LeadLevel::class)],
            'plan' => ['nullable', Rule::enum(Plan::class)],
            'goal' => ['nullable', 'string', 'max:1000'],
            'site' => ['nullable'],
            'utm_source' => $attribution,
            'utm_medium' => $attribution,
            'utm_campaign' => $attribution,
            'utm_content' => $attribution,
            'utm_term' => $attribution,
            'fbclid' => $attribution,
            'referrer' => $attribution,
            'landing_path' => $attribution,
            'visitor_id' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Conta pra gente como podemos te chamar.',
            'email.required' => 'Precisamos do seu e-mail para responder.',
            'email.email' => 'Esse e-mail não parece válido.',
            'whatsapp.min' => 'Confira o número com DDD.',
            'whatsapp.max' => 'Confira o número com DDD.',
            'level.required' => 'Selecione o seu momento.',
            'level.enum' => 'Selecione o seu momento.',
            'plan.enum' => 'Selecione um formato da lista.',
            'goal.max' => 'Resuma em até 1000 caracteres.',
        ];
    }

    public function isSpam(): bool
    {
        return filled($this->input('site'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'whatsapp' => preg_replace('/\D/', '', (string) $this->input('whatsapp')) ?: null,
            'name' => trim((string) $this->input('name')),
        ]);
    }
}
