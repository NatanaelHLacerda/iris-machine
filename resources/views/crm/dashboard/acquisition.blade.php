@extends('crm.layouts.app')

@section('title', 'Aquisição')

@php
    use App\Support\Crm\Money;
    $percent = fn (?float $value) => $value === null ? '—' : number_format($value * 100, 1, ',', '.').'%';
    $roas = fn (?float $value) => $value === null ? '—' : number_format($value, 1, ',', '.').'×';
@endphp

@section('content')
    <x-crm.page-header eyebrow="Growth · Aquisição" title="De onde vem o crescimento">
        <x-crm.period-filter :period="$period" />
    </x-crm.page-header>
    <x-crm.dashboard-tabs current="aquisicao" />

    <div class="section">
        <div class="card card--flush">
            <div class="card__head" style="padding:16px 18px 0"><h2 class="card__title">Por canal</h2></div>
            <div class="table-wrap">
                <table class="table" style="min-width:560px">
                    <thead><tr><th>Canal</th><th class="right">Leads</th><th class="right">Clientes</th><th class="right">Conversão</th><th class="right">Receita</th></tr></thead>
                    <tbody>
                        @forelse ($channels as $row)
                            <tr><td class="strong">{{ $row['label'] }}</td><td class="right num">{{ $row['leads'] }}</td><td class="right num">{{ $row['customers'] }}</td><td class="right">{{ $percent($row['conversion']) }}</td><td class="right">{{ Money::format($row['revenue']) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="empty">Nenhum lead no período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="source-note">Clientes = leads do período já fechados. Receita = pagamentos recebidos no período, pelo canal da venda.</p>
    </div>

    <div class="section">
        <div class="card card--flush">
            <div class="card__head" style="padding:16px 18px 0"><h2 class="card__title">Campanhas do Meta</h2><a class="btn btn--ghost btn--sm" href="{{ route('crm.ads', request()->only('periodo', 'de', 'ate')) }}">Detalhar</a></div>
            <div class="table-wrap">
                <table class="table" style="min-width:720px">
                    <thead><tr><th>Campanha</th><th class="right">Investido</th><th class="right">Leads</th><th class="right">CPL</th><th class="right">Clientes</th><th class="right">CAC</th><th class="right">ROAS</th></tr></thead>
                    <tbody>
                        @forelse ($campaigns as $campaign)
                            <tr @class(['is-alert' => $campaign['is_unprofitable']])>
                                <td class="strong">{{ $campaign['name'] }} @if ($campaign['is_unprofitable'])<span class="badge badge--danger">No vermelho</span>@endif</td>
                                <td class="right">{{ Money::format($campaign['spend']) }}</td>
                                <td class="right num">{{ $campaign['leads'] }}</td>
                                <td class="right">{{ Money::format($campaign['cpl']) }}</td>
                                <td class="right num">{{ $campaign['customers'] }}</td>
                                <td class="right">{{ Money::format($campaign['cac']) }}</td>
                                <td class="right strong">{{ $roas($campaign['roas']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">Sem campanhas no período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section grid grid--3">
        <section class="card">
            <div class="card__head"><h2 class="card__title">Por momento</h2></div>
            <x-crm.bars :data="$levels->mapWithKeys(fn ($row) => [$row['label'].' · '.$percent($row['conversion']) => $row['leads']])->all()" :money="false" />
            <p class="source-note">Leads no período · conversão em cliente.</p>
        </section>
        <section class="card">
            <div class="card__head"><h2 class="card__title">Formato de interesse</h2></div>
            <x-crm.bars :data="$plans->mapWithKeys(fn ($row) => [$row['label'].' · '.$percent($row['conversion']) => $row['leads']])->all()" :money="false" />
            <p class="source-note">O que o lead marcou no formulário · conversão.</p>
        </section>
        <section class="card">
            <div class="card__head"><h2 class="card__title">O que querem destravar</h2></div>
            <x-crm.bars :data="$topics" :money="false" :accent-first="true" />
            <p class="source-note">Palavras mais citadas no formulário. Use nos criativos e no conteúdo.</p>
        </section>
    </div>
@endsection
