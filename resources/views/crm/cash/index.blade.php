@extends('crm.layouts.app')

@section('title', 'Caixa')

@php
    use App\Support\Crm\Money;
    $seriesLabels = $series ? collect($series)->mapWithKeys(fn ($value, $day) => [\Carbon\CarbonImmutable::parse($day)->format('d/m') => $value])->all() : [];
@endphp

@section('content')
    <x-crm.page-header eyebrow="Financeiro" title="Caixa">
        <x-crm.period-filter :period="$period" />
        <button class="btn btn--ghost" type="button" data-dialog-open="opening-dialog">Saldo inicial</button>
        <button class="btn btn--primary" type="button" data-dialog-open="entry-dialog" data-action="{{ route('crm.cash.entries.store') }}" data-method="POST">Novo lançamento</button>
    </x-crm.page-header>

    <div class="section">
        <div class="grid grid--kpis">
            <div class="card">
                <div class="kpi__label">Saldo em conta hoje</div>
                <div @class(['kpi__value', 'accent' => ($balance ?? 0) < 0])>{{ Money::format($balance) }}</div>
                <div class="kpi__delta">
                    @if ($opening)
                        desde {{ $opening['date']->format('d/m/Y') }} ({{ Money::format($opening['amount_cents']) }})
                    @else
                        <button class="btn btn--sm btn--ghost" type="button" data-dialog-open="opening-dialog">Definir saldo inicial</button>
                    @endif
                </div>
            </div>
            <div class="card"><div class="kpi__label">Entradas</div><div class="kpi__value">{{ Money::format($summary['in']) }}</div><div class="kpi__delta">no período</div></div>
            <div class="card"><div class="kpi__label">Saídas</div><div class="kpi__value">{{ Money::format($summary['out']) }}</div><div class="kpi__delta">anúncios e custos</div></div>
            <div class="card">
                <div class="kpi__label">Resultado</div>
                <div @class(['kpi__value', 'delta--down' => $summary['result'] < 0, 'delta--up' => $summary['result'] > 0])>{{ Money::format($summary['result']) }}</div>
                <div class="kpi__delta">{{ $summary['result'] < 0 ? 'prejuízo' : 'lucro' }} · margem {{ $summary['margin'] === null ? '—' : number_format($summary['margin'] * 100, 0, ',', '.').'%' }}</div>
            </div>
        </div>
    </div>

    <div class="section grid grid--2">
        <section class="card">
            <div class="card__head"><h2 class="card__title">Evolução do saldo</h2></div>
            @if ($series)
                <x-crm.line-chart :data="$seriesLabels" label="Saldo em conta ao fim de cada dia" />
            @else
                <p class="empty">Defina o saldo inicial para acompanhar a evolução.</p>
            @endif
        </section>
        <section class="card">
            <div class="card__head"><h2 class="card__title">Saídas por categoria</h2></div>
            <x-crm.bars :data="$summary['out_by_category']" :accent-first="true" />
        </section>
    </div>

    <div class="section">
        <div class="card card--flush">
            <div class="card__head" style="padding:16px 18px 0">
                <h2 class="card__title">Extrato</h2>
                <form method="GET" action="{{ route('crm.cash') }}">
                    @foreach (request()->only('periodo', 'de', 'ate') as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <select class="select" name="origem" aria-label="Origem" data-autosubmit>
                        <option value="">Todas as origens</option>
                        @foreach ($origins as $value => $label)
                            <option value="{{ $value }}" @selected($origin === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="table-wrap">
                <table class="table" style="min-width:720px">
                    <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Origem</th><th class="right">Valor</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($statement as $line)
                            <tr>
                                <td class="num">{{ \Carbon\CarbonImmutable::parse($line['date'])->format('d/m/Y') }}</td>
                                <td>
                                    @if ($line['link'])<a class="strong" href="{{ $line['link'] }}">{{ $line['description'] }}</a>@else<span class="strong">{{ $line['description'] }}</span>@endif
                                </td>
                                <td>{{ $line['category']->label() }}</td>
                                <td><span class="badge">{{ $line['editable'] ? 'Manual' : 'Automático' }}</span></td>
                                <td @class(['right', 'strong', 'delta--up' => $line['amount_cents'] > 0])>{{ $line['amount_cents'] > 0 ? '+' : '' }}{{ Money::format($line['amount_cents']) }}</td>
                                <td class="right" style="white-space:nowrap">
                                    @if ($line['editable'])
                                        <button class="btn btn--ghost btn--sm" type="button" data-dialog-open="entry-dialog"
                                                data-action="{{ route('crm.cash.entries.update', $line['entry']) }}" data-method="PUT"
                                                data-fill="{{ json_encode(['date' => $line['entry']->date, 'direction' => $line['entry']->direction->value, 'category' => $line['entry']->category->value, 'description' => $line['entry']->description, 'amount' => number_format($line['entry']->amount_cents / 100, 2, ',', '')]) }}">Editar</button>
                                        <form method="POST" action="{{ route('crm.cash.entries.destroy', $line['entry']) }}" data-confirm="Apagar este lançamento?" style="display:inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn--danger btn--sm" type="submit">Apagar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">Nenhuma movimentação no período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="source-note">Pagamentos entram na data em que foram recebidos; o gasto no Meta sai no dia do gasto. Esses lançamentos se editam em Vendas e Meta Ads.</p>
    </div>

    <dialog class="modal" id="entry-dialog" @if ($errors->hasAny(['description', 'amount_cents', 'category'])) data-open-on-load @endif>
        <form method="POST" action="{{ route('crm.cash.entries.store') }}" data-dialog-form>
            @csrf
            <input type="hidden" name="_method" value="POST" data-method-field>
            <div class="modal__head">Lançamento</div>
            <div class="modal__body form-stack">
                <div class="field-row">
                    <div class="field">
                        <label for="entry-direction">Tipo</label>
                        <select class="select" id="entry-direction" name="direction" data-direction>
                            @foreach (\App\Enums\CashDirection::cases() as $direction)
                                <option value="{{ $direction->value }}" @selected(old('direction', 'out') === $direction->value)>{{ $direction->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="entry-category">Categoria</label>
                        <select class="select" id="entry-category" name="category">
                            @foreach (\App\Enums\CashDirection::cases() as $direction)
                                @foreach (\App\Enums\CashCategory::manual($direction) as $category)
                                    <option value="{{ $category->value }}" data-for="{{ $direction->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="entry-description">Descrição</label>
                    <input class="input" id="entry-description" name="description" maxlength="160" required value="{{ old('description') }}" placeholder="Ex.: Assinatura do Claude">
                    @error('description')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field-row">
                    <div class="field"><label for="entry-date">Data</label><input class="input" id="entry-date" type="date" name="date" value="{{ old('date', now(\App\Support\Crm\Period::TIMEZONE)->toDateString()) }}" required></div>
                    <div class="field">
                        <label for="entry-amount">Valor (R$)</label>
                        <input class="input num" id="entry-amount" name="amount" inputmode="decimal" required value="{{ old('amount') }}" placeholder="0,00">
                        @error('amount_cents')<p class="field__error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn btn--ghost" type="button" data-dialog-close>Cancelar</button>
                <button class="btn btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </dialog>

    <dialog class="modal" id="opening-dialog">
        <form method="POST" action="{{ route('crm.cash.opening') }}">
            @csrf
            @method('PUT')
            <div class="modal__head">Saldo inicial</div>
            <div class="modal__body form-stack">
                <p class="muted" style="font-size:13px">Quanto havia na conta da empresa numa data. O saldo de hoje é esse valor mais tudo que entrou e saiu depois.</p>
                <div class="field-row">
                    <div class="field"><label for="opening-amount">Valor (R$)</label><input class="input num" id="opening-amount" name="amount" inputmode="decimal" required value="{{ $opening ? number_format($opening['amount_cents'] / 100, 2, ',', '') : '' }}"></div>
                    <div class="field"><label for="opening-date">Em</label><input class="input" id="opening-date" type="date" name="date" required value="{{ $opening ? $opening['date']->toDateString() : now(\App\Support\Crm\Period::TIMEZONE)->startOfMonth()->toDateString() }}"></div>
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn btn--ghost" type="button" data-dialog-close>Cancelar</button>
                <button class="btn btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </dialog>
@endsection
