<?php

namespace App\Http\Requests\Crm;

use App\Enums\CashCategory;
use App\Enums\CashDirection;
use App\Support\Crm\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class StoreCashEntryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'direction' => ['required', Rule::enum(CashDirection::class)],
            'category' => ['required', Rule::enum(CashCategory::class)->except([CashCategory::Alunos])],
            'description' => ['required', 'string', 'max:160'],
            'amount_cents' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['amount_cents.required' => 'Informe o valor.', 'amount_cents.min' => 'Informe o valor.', 'amount_cents.integer' => 'Valor inválido. Use o formato 110,00.'];
    }

    protected function prepareForValidation(): void
    {
        try {
            $cents = Money::toCents($this->input('amount'));
        } catch (InvalidArgumentException) {
            $cents = 'invalid';
        }

        $this->merge(['amount_cents' => $cents]);
    }
}
