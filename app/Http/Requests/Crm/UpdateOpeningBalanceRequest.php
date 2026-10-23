<?php

namespace App\Http\Requests\Crm;

use App\Support\Crm\Money;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class UpdateOpeningBalanceRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
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
