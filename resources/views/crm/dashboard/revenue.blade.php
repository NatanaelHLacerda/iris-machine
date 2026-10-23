@extends('crm.layouts.app')

@section('title', 'Receita')

@php
    use App\Support\Crm\Money;
    use App\Support\Crm\Period;
    $monthlyLabels = collect($monthly)->mapWithKeys(fn ($value, $month) => [mb_substr(Period::monthName(\Carbon\CarbonImmutable::parse($month.'-01')), 0, 3) => $value])->all();
    $percent = fn (?float $value) => $value === null ? '—' : number_format($value * 100, 1, ',', '.').'%';
@endphp

@section('content')
    <x-crm.page-header eyebrow="Growth · Receita" title="Receita e retenção" />
    <x-crm.dashboard-tabs current="receita" />

    <div class="section">
        <section class="card">
            <div class="card__head"><h2 class="card__title">Faturamento dos últimos 12 meses</h2><span class="dim" style="font-size:12px">linha tracejada = meta</span></div>
            <x-crm.column-chart :data="$monthlyLabels" :goal="$goal" :highlight-last="true" label="Faturamento mensal com a meta" :height="200" />
        </section>
    </div>

    <div class="section grid grid--kpis">
        <div class="card"><div class="kpi__label">MRR</div><div class="kpi__value">{{ Money::format($mrr) }}</div><div class="kpi__delta">{{ $active }} {{ $active === 1 ? 'aluno ativo' : 'alunos ativos' }}</div></div>
        <div class="card"><div class="kpi__label">Churn do mês</div><div class="kpi__value">{{ $percent($churn) }}</div><div class="kpi__delta">mês anterior: {{ $percent($previous_churn) }}</div></div>
        <div class="card"><div class="kpi__label">LTV</div><div class="kpi__value">{{ Money::format($ltv) }}</div><div class="kpi__delta">{{ number_format($lifetime, 1, ',', '.') }} meses de permanência {{ $lifetime_measured ? 'medidos' : '(estimativa até haver histórico)' }}</div></div>
        <div class="card"><div class="kpi__label">Upsell sessão → mentoria</div><div class="kpi__value">{{ $upsell }}</div><div class="kpi__delta">nos últimos 90 dias · {{ $sessions_sold }} sessões vendidas</div></div>
    </div>

    <div class="section">
        <div class="card card--flush">
            <div class="card__head" style="padding:16px 18px 0"><h2 class="card__title">Ticket por formato · últimos 90 dias</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Formato</th><th class="right">Vendas</th><th class="right">Ticket médio</th><th class="right">Preço de tabela</th></tr></thead>
                    <tbody>
                        @foreach (\App\Enums\Plan::cases() as $plan)
                            <tr>
                                <td class="strong">{{ $plan->label() }}</td>
                                <td class="right num">{{ $tickets[$plan->value]['count'] }}</td>
                                <td class="right">{{ Money::format($tickets[$plan->value]['average']) }}</td>
                                <td class="right dim">{{ $plan->priceCents() ? Money::format($plan->priceCents()) : 'Sob consulta' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <p class="source-note">LTV = mensalidade média × meses médios de permanência (das assinaturas já encerradas; com menos de 3, usa a estimativa de {{ config('iris.crm.default_lifetime_months') }} meses).</p>
    </div>
@endsection
