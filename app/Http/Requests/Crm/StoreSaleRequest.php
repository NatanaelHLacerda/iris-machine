<?php

namespace App\Http\Requests\Crm;

use App\Enums\PaymentMethod;
use App\Enums\Plan;
use App\Support\Crm\Money;
use App\Support\Crm\Period;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class StoreSaleRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::enum(Plan::class)],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'is_recurring' => ['boolean'],
            'billing_day' => ['nullable', 'integer', 'between:1,28'],
            'sold_at' => ['required', 'date', 'before_or_equal:today'],
            'paid_now' => ['boolean'],
            'method' => ['nullable', 'required_if:paid_now,true', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_cents.required' => 'Informe o valor da venda.',
            'amount_cents.min' => 'Informe o valor da venda.',
            'amount_cents.integer' => 'Valor inválido. Use o formato 399,90.',
            'method.required_if' => 'Informe a forma de pagamento.',
        ];
    }

    /**
     * @return array{plan: Plan, amount_cents: int, is_recurring: bool, billing_day: ?int, sold_at: CarbonImmutable, paid_now: bool, method: ?PaymentMethod, notes: ?string}
     */
    public function saleData(): array
    {
        return [
            'plan' => Plan::from($this->validated('plan')),
            'amount_cents' => (int) $this->validated('amount_cents'),
            'is_recurring' => $this->boolean('is_recurring'),
            'billing_day' => $this->validated('billing_day'),
            'sold_at' => CarbonImmutable::parse($this->validated('sold_at'), Period::TIMEZONE)->setTimeFrom(Period::today())->utc(),
            'paid_now' => $this->boolean('paid_now'),
            'method' => PaymentMethod::tryFrom((string) $this->validated('method')),
            'notes' => $this->validated('notes'),
        ];
    }

    /**
     * Sessão é sempre avulsa, mentoria sempre mensal; squad escolhe. O valor vem em reais.
     */
    protected function prepareForValidation(): void
    {
        $plan = Plan::tryFrom((string) $this->input('plan'));

        try {
            $cents = Money::toCents($this->input('amount')) ?? $plan?->priceCents();
        } catch (InvalidArgumentException) {
            $cents = 'invalid';
        }

        $this->merge([
            'amount_cents' => $cents,
            'is_recurring' => match ($plan) {
                Plan::Sessao => false,
                Plan::Mentoria => true,
                default => $this->boolean('is_recurring'),
            },
            'paid_now' => $this->boolean('paid_now'),
        ]);
    }
}
