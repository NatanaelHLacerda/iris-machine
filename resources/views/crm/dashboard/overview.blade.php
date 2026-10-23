@extends('crm.layouts.app')

@section('title', 'Dashboard')

@php use App\Support\Crm\Money; @endphp

@section('content')
    <x-crm.page-header eyebrow="Growth · Visão geral" title="Dashboard">
        <x-crm.period-filter :period="$period" />
    </x-crm.page-header>
    <x-crm.dashboard-tabs :current="null" />

    <div class="section">
        <section class="card">
            <div class="card__head">
                <h2 class="card__title">Meta de {{ \App\Support\Crm\Period::monthName(\App\Support\Crm\Period::today()) }}</h2>
                <div style="display:flex;gap:8px;align-items:center">
                    <span class="badge badge--{{ $goal['status']->tone() }}">{{ $goal['status']->label() }}</span>
                    <button class="btn btn--ghost btn--sm" type="button" data-dialog-open="goal-dialog">Editar meta</button>
                </div>
            </div>
            <div class="goal">
                <div>
                    <div class="goal__value">{{ Money::format($goal['revenue']) }} <span class="goal__of">de {{ Money::format($goal['goal']) }}</span></div>
                    <div class="meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round(min($goal['percent'], 1) * 100) }}" aria-label="Meta do mês">
                        <div class="meter__fill" style="width: {{ round(min($goal['percent'], 1) * 100, 1) }}%"></div>
                        <span class="meter__mark" style="left: {{ round($goal['expected_percent'] * 100, 1) }}%" title="Onde deveria estar hoje"></span>
                    </div>
                    <div class="meter__legend">
                        <span>{{ number_format($goal['percent'] * 100, 0, ',', '.') }}% atingido</span>
                        <span>a marca é onde deveria estar hoje ({{ number_format($goal['expected_percent'] * 100, 0) }}%)</span>
                    </div>
                </div>
                <div class="goal__facts">
                    <div><div class="goal__fact-value">{{ Money::format($goal['remaining']) }}</div><div class="goal__fact-label">faltam</div></div>
                    <div><div class="goal__fact-value">{{ Money::format($goal['daily_needed']) }}</div><div class="goal__fact-label">por dia nos {{ $goal['days_left'] }} dias restantes</div></div>
                    <div><div @class(['goal__fact-value', 'accent' => $goal['projection'] < $goal['goal']])>{{ Money::format($goal['projection']) }}</div><div class="goal__fact-label">projeção de fechamento</div></div>
                    <div><div class="goal__fact-value">{{ Money::format($goal['pending']) }}</div><div class="goal__fact-label">cobranças a receber no mês</div></div>
                </div>
            </div>
            <p class="source-note">Faturado = pagamentos recebidos no mês. Projeção = recebido + cobranças do mês + média diária de vendas avulsas dos últimos 30 dias × dias restantes.</p>
        </section>
    </div>

    <div class="section">
        <h2 class="section__title">Ações de hoje</h2>
        <div class="actions-list">
            @foreach ($actions as $action)
                <a class="action-card" href="{{ $action['url'] }}">
                    <span @class(['action-card__count', 'is-hot' => $action['count'] > 0])>{{ $action['count'] }}</span>
                    <span class="action-card__label">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="section">
        <h2 class="section__title">Indicadores do período</h2>
        <div class="grid grid--kpis">
            @foreach ($kpis as $kpi)
                <x-crm.kpi :kpi="$kpi" />
            @endforeach
        </div>
    </div>

    <dialog class="modal" id="goal-dialog" @error('goal') data-open-on-load @enderror>
        <form method="POST" action="{{ route('crm.settings.goal') }}">
            @csrf
            @method('PUT')
            <div class="modal__head">Meta mensal de faturamento</div>
            <div class="modal__body form-stack">
                <div class="field">
                    <label for="goal">Valor (R$)</label>
                    <input class="input num" id="goal" name="goal" inputmode="decimal" required value="{{ number_format($goal['goal'] / 100, 2, ',', '.') }}">
                    @error('goal')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <p class="dim" style="font-size:12px">Referência: 10 alunos de mentoria ≈ R$ 3.999,00/mês.</p>
            </div>
            <div class="modal__foot">
                <button class="btn btn--ghost" type="button" data-dialog-close>Cancelar</button>
                <button class="btn btn--primary" type="submit">Salvar meta</button>
            </div>
        </form>
    </dialog>
@endsection
