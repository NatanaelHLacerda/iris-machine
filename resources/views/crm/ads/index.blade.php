@extends('crm.layouts.app')

@section('title', 'Meta Ads')

@php
    use App\Support\Crm\Money;
    $roas = fn (?float $value) => $value === null ? '—' : number_format($value, 1, ',', '.').'×';
    $dailyLabels = collect($daily)->mapWithKeys(fn ($value, $day) => [\Carbon\CarbonImmutable::parse($day)->format('d/m') => $value])->all();
@endphp

@section('content')
    <x-crm.page-header eyebrow="Marketing · Tráfego pago" title="Meta Ads">
        <x-crm.period-filter :period="$period" />
        <button class="btn btn--ghost" type="button" data-dialog-open="spend-dialog">Lançar gasto</button>
        <button class="btn btn--primary" type="button" data-dialog-open="import-dialog">Importar CSV</button>
    </x-crm.page-header>

    <div class="section">
        <div class="grid grid--kpis">
            <div class="card">
                <div class="kpi__label">Investido</div>
                <div class="kpi__value">{{ Money::format($summary['spend']) }}</div>
                <div class="kpi__delta">{{ number_format($summary['impressions'], 0, ',', '.') }} impressões · {{ number_format($summary['clicks'], 0, ',', '.') }} cliques</div>
            </div>
            <div class="card">
                <div class="kpi__label">Leads do Meta</div>
                <div class="kpi__value">{{ $summary['leads'] }}</div>
                <div class="kpi__delta">CPL {{ Money::format($summary['cpl']) }}</div>
            </div>
            <div class="card">
                <div class="kpi__label">Clientes novos</div>
                <div class="kpi__value">{{ $summary['customers'] }}</div>
                <div class="kpi__delta">CAC {{ Money::format($summary['cac']) }}</div>
            </div>
            <div class="card">
                <div class="kpi__label">Receita atribuída</div>
                <div class="kpi__value">{{ Money::format($summary['revenue']) }}</div>
                <div class="kpi__delta">ROAS {{ $roas($summary['roas']) }}</div>
            </div>
            <div class="card">
                <div class="kpi__label">ROAS projetado (LTV)</div>
                <div @class(['kpi__value', 'accent' => $summary['is_unprofitable']])>{{ $roas($summary['ltv_roas']) }}</div>
                <div class="kpi__delta">LTV {{ Money::format($ltv) }} por aluno</div>
            </div>
        </div>
        @if ($summary['spend'] === null)
            <p class="source-note">Nenhum gasto registrado no período: importe o CSV do Gerenciador ou lance o gasto à mão. Sem gasto, CPL, CAC e ROAS ficam em "—".</p>
        @endif
    </div>

    <div class="section">
        <div class="card card--flush">
            <div class="card__head" style="padding:16px 18px 0"><h2 class="card__title">Por campanha</h2><span class="dim" style="font-size:12px">{{ $outsideMeta }} leads de fora do Meta no período</span></div>
            <div class="table-wrap">
                <table class="table" style="min-width:960px">
                    <thead>
                        <tr><th>Campanha</th><th class="right">Investido</th><th class="right">Cliques</th><th class="right">Leads</th><th class="right">CPL</th><th class="right">Clientes</th><th class="right">CAC</th><th class="right">Receita</th><th class="right">ROAS</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($campaigns as $campaign)
                            <tr @class(['is-alert' => $campaign['is_unprofitable']])>
                                <td>
                                    <details>
                                        <summary class="strong" style="cursor:pointer">{{ $campaign['name'] }}</summary>
                                        <div style="margin-top:8px;display:flex;flex-direction:column;gap:4px">
                                            @foreach ($adsets[$campaign['key']] as $adset)
                                                <div class="dim" style="font-size:12px">
                                                    <span class="chip">{{ $adset['key'] === '' ? 'sem conjunto' : $adset['name'] }}</span>
                                                    {{ Money::format($adset['spend']) }} · {{ $adset['leads'] }} leads · CPL {{ Money::format($adset['cpl']) }} · {{ $adset['customers'] }} clientes
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>
                                <td class="right">{{ Money::format($campaign['spend']) }}</td>
                                <td class="right num">{{ $campaign['spend'] === null ? '—' : number_format($campaign['clicks'], 0, ',', '.') }}</td>
                                <td class="right num">{{ $campaign['leads'] }}</td>
                                <td class="right">{{ Money::format($campaign['cpl']) }}</td>
                                <td class="right num">{{ $campaign['customers'] }}</td>
                                <td class="right">{{ Money::format($campaign['cac']) }}</td>
                                <td class="right">{{ Money::format($campaign['revenue']) }}</td>
                                <td class="right strong">{{ $roas($campaign['roas']) }}</td>
                                <td>
                                    @if ($campaign['spend'] === null)
                                        <span class="badge">Sem custo registrado</span>
                                    @elseif ($campaign['is_unprofitable'])
                                        <span class="badge badge--danger">No vermelho</span>
                                    @elseif (($campaign['ltv_roas'] ?? 0) >= 1)
                                        <span class="badge badge--ok">Se paga</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="empty">Nenhuma campanha com gasto ou lead no período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="source-note">"No vermelho": o custo para conquistar um aluno (CAC) passou do LTV, ou a campanha já gastou mais que um LTV sem trazer cliente. Atribuição first-touch pela UTM.</p>
    </div>

    <div class="section grid grid--2">
        <section class="card">
            <div class="card__head"><h2 class="card__title">Investimento por dia</h2></div>
            <x-crm.column-chart :data="$dailyLabels" label="Investimento diário no Meta" />
        </section>
        <section class="card">
            <div class="card__head"><h2 class="card__title">Padrão de UTM dos anúncios</h2><button class="btn btn--ghost btn--sm" type="button" data-copy="#utm-template">Copiar</button></div>
            <p class="muted" style="font-size:13px">Cole em <strong>Parâmetros de URL</strong> de cada anúncio. É o que liga lead e venda à campanha, ao conjunto e ao anúncio.</p>
            <pre id="utm-template" class="mono" style="margin:12px 0 0;padding:12px;border-radius:10px;background:rgba(255,255,255,.03);border:1px solid var(--line-soft);white-space:pre-wrap;word-break:break-all;font-size:12px;color:var(--violet-light)">{{ $utmTemplate }}</pre>
            <p class="source-note">Renomear uma campanha no Meta separa o histórico: mantenha o nome.</p>
        </section>
    </div>

    <dialog class="modal" id="import-dialog" @error('file') data-open-on-load @enderror>
        <form method="POST" action="{{ route('crm.ads.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal__head">Importar CSV do Gerenciador</div>
            <div class="modal__body form-stack">
                <p class="muted" style="font-size:13px">No Gerenciador de Anúncios: Relatórios → detalhamento <strong>por dia</strong> → colunas Nome da campanha, Nome do conjunto, Nome do anúncio, Valor usado, Impressões e Cliques no link → Exportar CSV. Reimportar o mesmo período atualiza, não duplica.</p>
                <input class="input" type="file" name="file" accept=".csv,text/csv" required style="padding-top:6px">
                @error('file')<p class="field__error">{{ $message }}</p>@enderror
            </div>
            <div class="modal__foot">
                <button class="btn btn--ghost" type="button" data-dialog-close>Cancelar</button>
                <button class="btn btn--primary" type="submit">Importar</button>
            </div>
        </form>
    </dialog>

    <dialog class="modal" id="spend-dialog" @if ($errors->hasAny(['date', 'campaign', 'spend_cents'])) data-open-on-load @endif>
        <form method="POST" action="{{ route('crm.ads.store') }}">
            @csrf
            <div class="modal__head">Lançar gasto</div>
            <div class="modal__body form-stack">
                <div class="field-row">
                    <div class="field"><label for="spend-date">Dia</label><input class="input" id="spend-date" type="date" name="date" value="{{ old('date', now(\App\Support\Crm\Period::TIMEZONE)->toDateString()) }}" required></div>
                    <div class="field"><label for="spend-value">Valor gasto (R$)</label><input class="input num" id="spend-value" name="spend" inputmode="decimal" value="{{ old('spend') }}" required placeholder="0,00"></div>
                </div>
                <div class="field"><label for="spend-campaign">Campanha</label><input class="input" id="spend-campaign" name="campaign" value="{{ old('campaign') }}" required placeholder="Igual ao utm_campaign"></div>
                <div class="field-row">
                    <div class="field"><label for="spend-adset">Conjunto</label><input class="input" id="spend-adset" name="adset" value="{{ old('adset') }}"></div>
                    <div class="field"><label for="spend-ad">Anúncio</label><input class="input" id="spend-ad" name="ad" value="{{ old('ad') }}"></div>
                </div>
                <div class="field-row">
                    <div class="field"><label for="spend-impressions">Impressões</label><input class="input num" id="spend-impressions" type="number" min="0" name="impressions" value="{{ old('impressions') }}"></div>
                    <div class="field"><label for="spend-clicks">Cliques no link</label><input class="input num" id="spend-clicks" type="number" min="0" name="link_clicks" value="{{ old('link_clicks') }}"></div>
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn btn--ghost" type="button" data-dialog-close>Cancelar</button>
                <button class="btn btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </dialog>
@endsection
