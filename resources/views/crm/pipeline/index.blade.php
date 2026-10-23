@extends('crm.layouts.app')

@section('title', 'Pipeline')

@section('content')
    <x-crm.page-header eyebrow="Vendas · Pipeline" title="Pipeline" :total="$total.' leads no board'">
        <form method="GET" action="{{ route('crm.pipeline') }}">
            <label class="sr-only" for="q">Buscar no board</label>
            <input class="input input--search" id="q" name="q" type="search" value="{{ $search }}" placeholder="Buscar no board…">
        </form>
        <a class="btn btn--primary" href="{{ route('crm.leads.create') }}">Novo lead</a>
    </x-crm.page-header>

    <div class="board" data-board data-close-url="{{ Route::has('crm.leads.sales.store') ? 'sale' : '' }}">
        @foreach ($columns as $column)
            <section class="column" data-column="{{ $column['status']->value }}" aria-label="{{ $column['status']->label() }}">
                <header class="column__head">
                    <span>{{ $column['status']->label() }}</span>
                    <span class="column__count" data-count>{{ $column['leads']->count() }}</span>
                </header>
                <div class="column__body">
                    @foreach ($column['leads'] as $lead)
                        <a href="{{ route('crm.leads.show', $lead) }}" draggable="true"
                           @class(['kcard', 'is-alert' => $lead->isOverdue() || $lead->isCooling()])
                           data-card data-url="{{ route('crm.leads.status', $lead) }}" data-show-url="{{ route('crm.leads.show', $lead) }}">
                            <div class="kcard__name">{{ $lead->name }}</div>
                            <div class="kcard__meta">
                                <span>{{ $lead->plan_interest?->label() ?? 'Formato a definir' }}</span>
                                <span>·</span>
                                <span>{{ $lead->channel->label() }}</span>
                            </div>
                            <div class="kcard__meta">
                                @if ($lead->isOverdue())
                                    <span class="badge badge--danger">Atrasado</span>
                                @elseif ($lead->isCooling())
                                    <span class="badge badge--warn">Esfriando</span>
                                @endif
                                <span class="num">{{ $lead->daysInStatus() }}d na etapa</span>
                                @if ($lead->next_action)
                                    <span title="{{ $lead->next_action }}">→ <x-crm.datetime :value="$lead->next_action_at" format="d/m" /></span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <dialog class="modal" id="lost-dialog">
        <form method="dialog" data-lost-form>
            <div class="modal__head">Por que perdeu?</div>
            <div class="modal__body form-stack">
                <div class="field">
                    <label for="lost-reason">Motivo</label>
                    <select class="select" id="lost-reason" name="lost_reason" required>
                        <option value="">Selecione</option>
                        @foreach ($lostReasons as $reason)
                            <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="lost-note">Observação</label>
                    <input class="input" id="lost-note" name="note" maxlength="255">
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn btn--ghost" value="cancel" formnovalidate>Cancelar</button>
                <button class="btn btn--primary" value="confirm">Marcar como perdido</button>
            </div>
        </form>
    </dialog>
@endsection
