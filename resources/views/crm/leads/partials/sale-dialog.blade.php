@php
    $suggestedPlan = old('plan', $lead->plan_interest?->value ?? \App\Enums\Plan::Mentoria->value);
    $prices = collect(\App\Enums\Plan::cases())->mapWithKeys(fn ($plan) => [$plan->value => $plan->priceCents() ? number_format($plan->priceCents() / 100, 2, ',', '.') : '']);
@endphp

<dialog class="modal" id="sale-dialog" @if ($errors->hasAny(['plan', 'amount_cents', 'sold_at', 'method', 'billing_day'])) data-open-on-load @endif>
    <form method="POST" action="{{ route('crm.leads.sales.store', $lead) }}" data-sale-form data-prices='@json($prices)'>
        @csrf
        <div class="modal__head">Registrar venda · {{ $lead->name }}</div>
        <div class="modal__body form-stack">
            <div class="field-row">
                <div class="field">
                    <label for="sale-plan">Formato</label>
                    <select class="select" id="sale-plan" name="plan" required>
                        @foreach (\App\Enums\Plan::cases() as $plan)
                            <option value="{{ $plan->value }}" @selected($suggestedPlan === $plan->value)>{{ $plan->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="sale-amount">Valor (R$)</label>
                    <input class="input num" id="sale-amount" name="amount" inputmode="decimal" value="{{ old('amount', $prices[$suggestedPlan] ?? '') }}" placeholder="0,00">
                    @error('amount_cents')<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="sale-date">Data da venda</label>
                    <input class="input" id="sale-date" name="sold_at" type="date" value="{{ old('sold_at', now(\App\Support\Crm\Period::TIMEZONE)->toDateString()) }}" required>
                </div>
                <div class="field" data-recurring-only>
                    <label for="sale-day">Dia da cobrança</label>
                    <input class="input num" id="sale-day" name="billing_day" type="number" min="1" max="28" value="{{ old('billing_day') }}" placeholder="Dia da venda">
                </div>
            </div>
            <label class="check" data-squad-only><input type="checkbox" name="is_recurring" value="1" @checked(old('is_recurring'))> Squad com mensalidade recorrente</label>
            <label class="check"><input type="checkbox" name="paid_now" value="1" @checked(old('paid_now', true)) data-paid-toggle> Primeiro pagamento já recebido</label>
            <div class="field" data-paid-only>
                <label for="sale-method">Forma de pagamento</label>
                <select class="select" id="sale-method" name="method">
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="sale-notes">Observação</label>
                <input class="input" id="sale-notes" name="notes" maxlength="255" value="{{ old('notes') }}" placeholder="Ex.: desconto de indicação">
            </div>
        </div>
        <div class="modal__foot">
            <button class="btn btn--ghost" type="button" data-dialog-close>Cancelar</button>
            <button class="btn btn--primary" type="submit">Registrar e fechar lead</button>
        </div>
    </form>
</dialog>
