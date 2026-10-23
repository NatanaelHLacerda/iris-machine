@extends('crm.layouts.app')

@section('title', 'Leads')

@section('content')
    <x-crm.page-header eyebrow="Vendas · Leads" title="Leads" :total="number_format($summary->total ?? 0, 0, ',', '.').' '.(($summary->total ?? 0) === 1 ? 'lead' : 'leads')">
        <a class="btn btn--ghost" href="{{ route('crm.leads.export', $filters) }}">Exportar CSV</a>
        <a class="btn btn--primary" href="{{ route('crm.leads.create') }}">Novo lead</a>
    </x-crm.page-header>

    <form class="section" method="GET" action="{{ route('crm.leads.index') }}">
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <label class="sr-only" for="q">Buscar</label>
            <input class="input input--search" id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar nome, e-mail, WhatsApp…">
            <select class="select" name="status" aria-label="Etapa" data-autosubmit>
                <option value="">Todas as etapas</option>
                @foreach (\App\Enums\LeadStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select class="select" name="channel" aria-label="Canal" data-autosubmit>
                <option value="">Todos os canais</option>
                @foreach (\App\Enums\Channel::cases() as $channel)
                    <option value="{{ $channel->value }}" @selected(($filters['channel'] ?? '') === $channel->value)>{{ $channel->label() }}</option>
                @endforeach
            </select>
            <select class="select" name="campaign" aria-label="Campanha" data-autosubmit>
                <option value="">Todas as campanhas</option>
                @foreach ($campaigns as $campaign)
                    <option value="{{ $campaign }}" @selected(($filters['campaign'] ?? '') === $campaign)>{{ $campaign }}</option>
                @endforeach
            </select>
            <select class="select" name="level" aria-label="Momento" data-autosubmit>
                <option value="">Todo momento</option>
                @foreach (\App\Enums\LeadLevel::cases() as $level)
                    <option value="{{ $level->value }}" @selected(($filters['level'] ?? '') === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
            <select class="select" name="plan" aria-label="Formato" data-autosubmit>
                <option value="">Todo formato</option>
                @foreach (\App\Enums\Plan::cases() as $plan)
                    <option value="{{ $plan->value }}" @selected(($filters['plan'] ?? '') === $plan->value)>{{ $plan->label() }}</option>
                @endforeach
            </select>
            <input class="input" type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="De">
            <input class="input" type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="Até">
            @if (isset($filters['flag']))
                <input type="hidden" name="flag" value="{{ $filters['flag'] }}">
                <span class="badge badge--pink">{{ $filters['flag'] === 'atrasados' ? 'Follow-up atrasado' : 'Esfriando' }}</span>
            @endif
            <button class="btn btn--ghost" type="submit">Filtrar</button>
            @if ($filters)
                <a class="btn btn--sm" href="{{ route('crm.leads.index') }}">Limpar</a>
            @endif
        </div>
    </form>

    <div class="section">
        <div class="stats">
            <div><div class="stat__value">{{ number_format($summary->total ?? 0, 0, ',', '.') }}</div><div class="stat__label">Total</div></div>
            <div><div class="stat__value">{{ number_format($summary->open ?? 0, 0, ',', '.') }}</div><div class="stat__label">Em aberto</div></div>
            <div><div class="stat__value">{{ number_format($summary->closed ?? 0, 0, ',', '.') }}</div><div class="stat__label">Fechados</div></div>
            <div><div class="stat__value">{{ number_format($summary->lost ?? 0, 0, ',', '.') }}</div><div class="stat__label">Perdidos</div></div>
            <div><div class="stat__value">{{ $summary->total ? round($summary->with_utm / $summary->total * 100).'%' : '—' }}</div><div class="stat__label">Com UTM</div></div>
        </div>
    </div>

    <div class="section">
        <div class="card card--flush">
            <div class="table-wrap">
                <table class="table" style="min-width:1000px">
                    <thead>
                        <tr>
                            <th>Quando</th><th>Lead</th><th>Contato</th><th>Momento</th><th>Formato</th><th>Canal</th><th>Campanha</th><th>Etapa</th><th>Próxima ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr @class(['is-alert' => $lead->isOverdue() || $lead->isCooling()])>
                                <td style="white-space:nowrap"><x-crm.datetime :value="$lead->created_at" format="d/m H:i" /></td>
                                <td><a class="strong" href="{{ route('crm.leads.show', $lead) }}">{{ $lead->name }}</a></td>
                                <td>
                                    <div>{{ $lead->email }}</div>
                                    @if ($lead->whatsapp)
                                        <a href="{{ $lead->whatsappLink() }}" target="_blank" rel="noopener" class="dim num">{{ $lead->formattedWhatsapp() }}</a>
                                    @endif
                                </td>
                                <td>{{ $lead->level->label() }}</td>
                                <td>{{ $lead->plan_interest?->label() ?? '—' }}</td>
                                <td><x-crm.channel :channel="$lead->channel" /></td>
                                <td><x-crm.utm :value="$lead->utm_campaign" /></td>
                                <td><x-crm.status-badge :status="$lead->status" /></td>
                                <td>
                                    @if ($lead->isOverdue())
                                        <span class="badge badge--danger">Atrasada</span>
                                    @elseif ($lead->isCooling())
                                        <span class="badge badge--warn">Esfriando</span>
                                    @endif
                                    @if ($lead->next_action)
                                        <div style="margin-top:4px">{{ $lead->next_action }} · <x-crm.datetime :value="$lead->next_action_at" format="d/m" /></div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty">Nenhum lead com esses filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($leads->hasPages())
                <div class="pagination">
                    <span>{{ $leads->firstItem() }}–{{ $leads->lastItem() }} de {{ $leads->total() }}</span>
                    <nav>
                        @if ($leads->previousPageUrl())<a class="btn btn--ghost btn--sm" href="{{ $leads->previousPageUrl() }}">Anterior</a>@endif
                        @if ($leads->nextPageUrl())<a class="btn btn--ghost btn--sm" href="{{ $leads->nextPageUrl() }}">Próxima</a>@endif
                    </nav>
                </div>
            @endif
        </div>
    </div>
@endsection
