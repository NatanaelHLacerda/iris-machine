<?php

namespace App\Http\Requests\Crm;

use App\Support\Crm\Money;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class StoreAdSpendRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'campaign' => ['required', 'string', 'max:200'],
            'adset' => ['nullable', 'string', 'max:200'],
            'ad' => ['nullable', 'string', 'max:200'],
            'spend_cents' => ['required', 'integer', 'min:0'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'link_clicks' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['spend_cents.required' => 'Informe o valor gasto.', 'spend_cents.integer' => 'Valor inválido. Use o formato 49,90.'];
    }

    protected function prepareForValidation(): void
    {
        try {
            $cents = Money::toCents($this->input('spend'));
        } catch (InvalidArgumentException) {
            $cents = 'invalid';
        }

        $this->merge(['spend_cents' => $cents]);
    }
}
