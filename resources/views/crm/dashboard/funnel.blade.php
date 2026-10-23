@extends('crm.layouts.app')

@section('title', 'Funil')

@section('content')
    <x-crm.page-header eyebrow="Growth · Funil" title="Funil de conversão">
        <x-crm.period-filter :period="$period" :keep="$filters" />
    </x-crm.page-header>
    <x-crm.dashboard-tabs current="funil" />

    <form class="section" method="GET" action="{{ route('crm.dashboard.tab', 'funil') }}" style="display:flex;gap:8px;flex-wrap:wrap">
        @foreach (request()->only('periodo', 'de', 'ate') as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <select class="select" name="channel" aria-label="Canal" data-autosubmit>
            <option value="">Todos os canais</option>
            @foreach ($channels as $channel)
                <option value="{{ $channel->value }}" @selected(($filters['channel'] ?? '') === $channel->value)>{{ $channel->label() }}</option>
            @endforeach
        </select>
        <select class="select" name="campaign" aria-label="Campanha" data-autosubmit>
            <option value="">Todas as campanhas</option>
            @foreach ($campaigns as $campaign)
                <option value="{{ $campaign }}" @selected(($filters['campaign'] ?? '') === $campaign)>{{ $campaign }}</option>
            @endforeach
        </select>
    </form>

    <div class="section">
        <section class="card">
            <div class="card__head">
                <h2 class="card__title">Da visita ao cliente</h2>
                @php $bottleneck = $steps->firstWhere('isBottleneck', true); @endphp
                @if ($bottleneck)
                    <span class="badge badge--pink">Maior perda: até "{{ $bottleneck->label }}"</span>
                @endif
            </div>
            <x-crm.funnel-chart :steps="$steps" />
            <p class="source-note">Até "Começou a preencher": sessões distintas na landing (visitantes com Do Not Track não entram). Daí em diante: leads criados no período e a etapa mais avançada que alcançaram. A porcentagem é a conversão a partir do passo anterior.</p>
        </section>
    </div>

    @php
        $first = $steps->first()?->count ?? 0;
        $last = $steps->last()?->count ?? 0;
    @endphp
    <div class="section grid grid--3">
        <div class="card"><div class="kpi__label">Visita → lead</div><div class="kpi__value">{{ $first > 0 ? number_format(($steps->firstWhere('key', 'leads')->count / $first) * 100, 1, ',', '.').'%' : '—' }}</div><div class="kpi__delta">taxa de conversão da landing</div></div>
        <div class="card"><div class="kpi__label">Lead → cliente</div><div class="kpi__value">{{ $steps->firstWhere('key', 'leads')->count > 0 ? number_format($last / $steps->firstWhere('key', 'leads')->count * 100, 1, ',', '.').'%' : '—' }}</div><div class="kpi__delta">taxa de fechamento comercial</div></div>
        <div class="card"><div class="kpi__label">Visita → cliente</div><div class="kpi__value">{{ $first > 0 ? number_format($last / $first * 100, 2, ',', '.').'%' : '—' }}</div><div class="kpi__delta">de ponta a ponta</div></div>
    </div>
@endsection
