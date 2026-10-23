@extends('crm.layouts.app')

@section('title', $lead->name)

@section('content')
    <x-crm.page-header :eyebrow="'Lead #'.$lead->id.' · '.$lead->channel->label()" :title="$lead->name">
        <x-crm.status-badge :status="$lead->status" />
        @if ($lead->whatsappLink())
            <a class="btn btn--ghost" href="{{ $lead->whatsappLink() }}" target="_blank" rel="noopener">WhatsApp</a>
        @endif
        <a class="btn btn--ghost" href="mailto:{{ $lead->email }}">E-mail</a>
        @if (Route::has('crm.leads.sales.store'))
            <button class="btn btn--primary" type="button" data-dialog-open="sale-dialog">Registrar venda</button>
        @endif
    </x-crm.page-header>

    <div class="dossier">
        <div class="grid" style="align-content:start">
            <section class="card">
                <div class="card__head"><h2 class="card__title">Etapa</h2>
                    @if ($lead->isOverdue())<span class="badge badge--danger">Follow-up atrasado</span>@elseif ($lead->isCooling())<span class="badge badge--warn">Esfriando</span>@endif
                </div>
                <form class="form-stack" method="POST" action="{{ route('crm.leads.status', $lead) }}" data-status-form>
                    @csrf
                    @method('PATCH')
                    <div class="field-row">
                        <div class="field">
                            <label for="status">Mover para</label>
                            <select class="select" id="status" name="status">
                                @foreach (\App\Enums\LeadStatus::cases() as $status)
                                    @continue($status === \App\Enums\LeadStatus::Fechado)
                                    <option value="{{ $status->value }}" @selected($lead->status === $status)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" data-show-when="perdido">
                            <label for="lost_reason">Motivo da perda</label>
                            <select class="select" id="lost_reason" name="lost_reason">
                                <option value="">Selecione</option>
                                @foreach (\App\Enums\LostReason::cases() as $reason)
                                    <option value="{{ $reason->value }}" @selected($lead->lost_reason === $reason)>{{ $reason->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" data-show-when="conversa_agendada">
                            <label for="meeting_at">Data da conversa</label>
                            <input class="input" id="meeting_at" name="meeting_at" type="datetime-local" value="{{ $lead->meeting_at?->setTimezone(\App\Support\Crm\Period::TIMEZONE)->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                    <div class="field">
                        <label for="note">Observação <span class="dim">(opcional)</span></label>
                        <input class="input" id="note" name="note" maxlength="255">
                    </div>
                    <div><button class="btn btn--ghost" type="submit">Salvar etapa</button></div>
                    <p class="dim" style="font-size:12px">Fechado entra ao registrar a venda.</p>
                </form>
            </section>

            <section class="card">
                <div class="card__head"><h2 class="card__title">Próxima ação</h2></div>
                <form class="form-stack" method="POST" action="{{ route('crm.leads.next-action', $lead) }}">
                    @csrf
                    @method('PATCH')
                    <div class="field-row">
                        <div class="field">
                            <label for="next_action">O que fazer</label>
                            <input class="input" id="next_action" name="next_action" value="{{ $lead->next_action }}" maxlength="160" placeholder="Ex.: mandar proposta da mentoria">
                        </div>
                        <div class="field">
                            <label for="next_action_at">Quando</label>
                            <input class="input" id="next_action_at" name="next_action_at" type="datetime-local" value="{{ $lead->next_action_at?->setTimezone(\App\Support\Crm\Period::TIMEZONE)->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                    <div><button class="btn btn--ghost" type="submit">Salvar próxima ação</button></div>
                </form>
            </section>

            <section class="card">
                <div class="card__head"><h2 class="card__title">Dados</h2></div>
                <dl class="dl">
                    <dt>E-mail</dt><dd>{{ $lead->email }}</dd>
                    <dt>WhatsApp</dt><dd class="num">{{ $lead->formattedWhatsapp() ?? '—' }}</dd>
                    <dt>Momento</dt><dd>{{ $lead->level->label() }}</dd>
                    <dt>Formato</dt><dd>{{ $lead->plan_interest?->label() ?? 'Ainda não sabe' }}</dd>
                    <dt>Quer destravar</dt><dd>{{ $lead->goal ?: '—' }}</dd>
                    <dt>Chegou em</dt><dd><x-crm.datetime :value="$lead->created_at" /></dd>
                    @if ($lead->meeting_at)
                        <dt>Conversa</dt><dd><x-crm.datetime :value="$lead->meeting_at" /></dd>
                    @endif
                    @if ($lead->lost_reason)
                        <dt>Perdido por</dt><dd>{{ $lead->lost_reason->label() }}{{ $lead->lost_note ? ' · '.$lead->lost_note : '' }}</dd>
                    @endif
                </dl>
            </section>

            <section class="card">
                <div class="card__head"><h2 class="card__title">Origem</h2><x-crm.channel :channel="$lead->channel" /></div>
                <dl class="dl">
                    <dt>Fonte</dt><dd><x-crm.utm :value="$lead->utm_source" /></dd>
                    <dt>Mídia</dt><dd><x-crm.utm :value="$lead->utm_medium" /></dd>
                    <dt>Campanha</dt><dd><x-crm.utm :value="$lead->utm_campaign" /></dd>
                    <dt>Conjunto</dt><dd><x-crm.utm :value="$lead->utm_content" /></dd>
                    <dt>Anúncio</dt><dd><x-crm.utm :value="$lead->utm_term" /></dd>
                    <dt>Referrer</dt><dd><x-crm.utm :value="$lead->referrer" /></dd>
                    <dt>Entrou por</dt><dd><x-crm.utm :value="$lead->landing_path" /></dd>
                </dl>
            </section>

            @includeWhen(Route::has('crm.leads.sales.store'), 'crm.leads.partials.sales')

            <form method="POST" action="{{ route('crm.leads.destroy', $lead) }}" data-confirm="Apagar {{ $lead->name }} e todos os dados ligados (envios, histórico, vendas, eventos)? Não dá para desfazer.">
                @csrf
                @method('DELETE')
                <button class="btn btn--danger btn--sm" type="submit">Apagar lead (LGPD)</button>
            </form>
        </div>

        <div class="grid" style="align-content:start">
            <section class="card">
                <div class="card__head"><h2 class="card__title">Anotar</h2></div>
                <form class="form-stack" method="POST" action="{{ route('crm.leads.notes', $lead) }}">
                    @csrf
                    <textarea class="textarea" name="text" rows="3" maxlength="2000" required placeholder="Como foi o contato, objeções, combinados…" aria-label="Anotação"></textarea>
                    <div><button class="btn btn--ghost" type="submit">Salvar anotação</button></div>
                </form>
            </section>

            <section class="card">
                <div class="card__head"><h2 class="card__title">Linha do tempo</h2><span class="dim" style="font-size:12px">{{ $timeline->count() }} registros</span></div>
                <div class="timeline">
                    @forelse ($timeline as $item)
                        <div class="timeline__item timeline__item--{{ $item['kind'] }}">
                            <span class="timeline__dot" aria-hidden="true"></span>
                            <div class="timeline__when"><x-crm.datetime :value="$item['at']" /></div>
                            <div class="timeline__text">{{ $item['text'] }}</div>
                            @if ($item['note'])
                                <div class="timeline__note">{{ $item['note'] }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="empty">Nada registrado ainda.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    @includeWhen(Route::has('crm.leads.sales.store'), 'crm.leads.partials.sale-dialog')
@endsection
