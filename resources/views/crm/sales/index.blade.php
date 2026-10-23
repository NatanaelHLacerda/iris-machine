@extends('crm.layouts.app')

@section('title', 'Vendas')

@section('content')
    <x-crm.page-header eyebrow="Vendas · Receita" title="Vendas">
        <x-crm.period-filter :period="$period" :keep="['aba' => $tab]" />
    </x-crm.page-header>

    <div class="section">
        <div class="stats">
            <div><div class="stat__value"><x-crm.money :cents="$summary['revenue']" /></div><div class="stat__label">Recebido no mês</div></div>
            <div><div class="stat__value"><x-crm.money :cents="$summary['mrr']" /></div><div class="stat__label">MRR</div></div>
            <div><div class="stat__value">{{ $summary['active'] }}</div><div class="stat__label">Assinaturas ativas</div></div>
            <div>
                <div @class(['stat__value', 'accent' => $summary['overdue_count'] > 0])>{{ $summary['overdue_count'] }}</div>
                <div class="stat__label">Cobranças atrasadas · <x-crm.money :cents="$summary['overdue_amount']" /></div>
            </div>
        </div>
    </div>

    <nav class="tabs" style="margin-top:20px" aria-label="Seções de vendas">
        @foreach (['vendas' => 'Vendas', 'assinaturas' => 'Assinaturas', 'cobrancas' => 'Cobranças'] as $key => $label)
            <a href="{{ route('crm.sales.index', [...request()->only('periodo', 'de', 'ate'), 'aba' => $key]) }}" @class(['is-active' => $tab === $key])>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="section">
        <div class="card card--flush">
            <div class="table-wrap">
                @if ($tab === 'vendas')
                    <table class="table" style="min-width:820px">
                        <thead><tr><th>Data</th><th>Lead</th><th>Formato</th><th class="right">Valor</th><th>Tipo</th><th>Canal</th><th>Campanha</th></tr></thead>
                        <tbody>
                            @forelse ($sales as $sale)
                                <tr>
                                    <td><x-crm.datetime :value="$sale->sold_at" format="d/m/Y" /></td>
                                    <td><a class="strong" href="{{ route('crm.leads.show', $sale->lead) }}">{{ $sale->lead->name }}</a></td>
                                    <td>{{ $sale->plan->label() }}</td>
                                    <td class="right strong"><x-crm.money :cents="$sale->amount_cents" /></td>
                                    <td>{{ $sale->is_recurring ? 'Mensal' : 'Avulsa' }}</td>
                                    <td><x-crm.channel :channel="$sale->channel" /></td>
                                    <td><x-crm.utm :value="$sale->utm_campaign" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty">Nenhuma venda no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @elseif ($tab === 'assinaturas')
                    <table class="table" style="min-width:820px">
                        <thead><tr><th>Aluno</th><th>Formato</th><th class="right">Mensalidade</th><th>Vence dia</th><th>Desde</th><th>Pagas</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($subscriptions as $subscription)
                                <tr>
                                    <td><a class="strong" href="{{ route('crm.leads.show', $subscription->lead) }}">{{ $subscription->lead->name }}</a></td>
                                    <td>{{ $subscription->plan->label() }}</td>
                                    <td class="right"><x-crm.money :cents="$subscription->monthly_amount_cents" /></td>
                                    <td class="num">{{ $subscription->billing_day }}</td>
                                    <td><x-crm.datetime :value="$subscription->started_at" format="d/m/Y" /></td>
                                    <td class="num">{{ $subscription->paid_count }}</td>
                                    <td>
                                        <span @class(['badge', 'badge--ok' => $subscription->status === \App\Enums\SubscriptionStatus::Active])>{{ $subscription->status->label() }}</span>
                                        @if ($subscription->canceled_at)
                                            <div class="dim" style="font-size:11.5px;margin-top:4px">em <x-crm.datetime :value="$subscription->canceled_at" format="d/m/Y" />{{ $subscription->cancel_reason ? ' · '.$subscription->cancel_reason : '' }}</div>
                                        @endif
                                    </td>
                                    <td class="right">
                                        @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                                            <button class="btn btn--danger btn--sm" type="button" data-dialog-open="cancel-dialog" data-action="{{ route('crm.subscriptions.cancel', $subscription) }}">Cancelar</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty">Nenhuma assinatura ainda.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="table" style="min-width:820px">
                        <thead><tr><th>Vencimento</th><th>Aluno</th><th>Formato</th><th class="right">Valor</th><th>Status</th><th>Pago em</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                <tr @class(['is-alert' => $payment->isOverdue()])>
                                    <td class="num">{{ $payment->due_date->format('d/m/Y') }}</td>
                                    <td><a class="strong" href="{{ route('crm.leads.show', $payment->lead) }}">{{ $payment->lead->name }}</a></td>
                                    <td>{{ $payment->sale->plan->label() }}</td>
                                    <td class="right strong"><x-crm.money :cents="$payment->amount_cents" /></td>
                                    <td>
                                        @if ($payment->isOverdue())
                                            <span class="badge badge--danger">Atrasada</span>
                                        @else
                                            <span @class(['badge', 'badge--ok' => $payment->status === \App\Enums\PaymentStatus::Paid, 'badge--warn' => $payment->status === \App\Enums\PaymentStatus::Pending])>{{ $payment->status->label() }}</span>
                                        @endif
                                    </td>
                                    <td>@if ($payment->paid_at)<x-crm.datetime :value="$payment->paid_at" format="d/m/Y" /> · {{ $payment->method?->label() }}@else<span class="dim">—</span>@endif</td>
                                    <td class="right">
                                        @if ($payment->status === \App\Enums\PaymentStatus::Pending)
                                            <button class="btn btn--primary btn--sm" type="button" data-dialog-open="pay-dialog" data-action="{{ route('crm.payments.pay', $payment) }}">Marcar pago</button>
                                        @elseif ($payment->status === \App\Enums\PaymentStatus::Paid)
                                            <form method="POST" action="{{ route('crm.payments.refund', $payment) }}" data-confirm="Estornar este pagamento? Ele sai do faturamento e do caixa.">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn--ghost btn--sm" type="submit">Estornar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty">Nenhuma cobrança em aberto ou paga no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <dialog class="modal" id="pay-dialog">
        <form method="POST" data-dialog-form>
            @csrf
            @method('PATCH')
            <div class="modal__head">Registrar pagamento</div>
            <div class="modal__body form-stack">
                <div class="field-row">
                    <div class="field">
                        <label for="paid_at">Pago em</label>
                        <input class="input" id="paid_at" name="paid_at" type="date" value="{{ now(\App\Support\Crm\Period::TIMEZONE)->toDateString() }}" required>
                    </div>
                    <div class="field">
                        <label for="method">Forma</label>
                        <select class="select" id="method" name="method" required>
                            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                <option value="{{ $method->value }}">{{ $method->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn btn--ghost" type="button" data-dialog-close>Cancelar</button>
                <button class="btn btn--primary" type="submit">Confirmar pagamento</button>
            </div>
        </form>
    </dialog>

    <dialog class="modal" id="cancel-dialog">
        <form method="POST" data-dialog-form>
            @csrf
            @method('PATCH')
            <div class="modal__head">Cancelar assinatura</div>
            <div class="modal__body form-stack">
                <div class="field">
                    <label for="canceled_at">Cancelada em</label>
                    <input class="input" id="canceled_at" name="canceled_at" type="date" value="{{ now(\App\Support\Crm\Period::TIMEZONE)->toDateString() }}" required>
                </div>
                <div class="field">
                    <label for="cancel_reason">Motivo</label>
                    <input class="input" id="cancel_reason" name="cancel_reason" maxlength="255" placeholder="Ex.: conseguiu a vaga, financeiro…">
                </div>
                <p class="dim" style="font-size:12px">Cobranças em aberto com vencimento depois dessa data são descartadas.</p>
            </div>
            <div class="modal__foot">
                <button class="btn btn--ghost" type="button" data-dialog-close>Voltar</button>
                <button class="btn btn--danger" type="submit">Cancelar assinatura</button>
            </div>
        </form>
    </dialog>
@endsection
