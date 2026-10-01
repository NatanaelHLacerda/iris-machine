<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'level' => ['required', Rule::in(array_keys(config('iris.levels')))],
            'plan' => ['nullable', Rule::in(array_column(config('iris.plans'), 'slug'))],
            'goal' => ['nullable', 'string', 'max:1000'],
            // Honeypot: humanos não enxergam esse campo.
            'site' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Conta pra gente como você se chama.',
            'name.min' => 'O nome precisa ter pelo menos 2 caracteres.',
            'email.required' => 'Precisamos de um e-mail para te responder.',
            'email.email' => 'Esse e-mail não parece válido.',
            'level.required' => 'Escolha o momento que mais combina com você.',
            'level.in' => 'Escolha uma das opções da lista.',
            'plan.in' => 'Escolha um dos formatos da lista.',
            'goal.max' => 'Resuma em até 1000 caracteres.',
            'site.prohibited' => 'Não foi possível enviar o formulário.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('home').'#agendar';
    }
}
