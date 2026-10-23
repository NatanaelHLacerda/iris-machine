@extends('crm.layouts.app')

@section('title', 'Novo lead')

@section('content')
    <x-crm.page-header eyebrow="Vendas · Leads" title="Novo lead">
        <a class="btn btn--ghost" href="{{ route('crm.leads.index') }}">Voltar</a>
    </x-crm.page-header>

    <div class="section" style="max-width:760px">
        <form class="card form-stack" method="POST" action="{{ route('crm.leads.store') }}">
            @csrf
            <p class="muted" style="font-size:13px">Para quem chegou por fora da landing: indicação, direct do Instagram, evento.</p>
            <div class="field-row">
                <div class="field">
                    <label for="name">Nome</label>
                    <input class="input" id="name" name="name" value="{{ old('name') }}" required maxlength="120">
                    @error('name')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="whatsapp">WhatsApp</label>
                    <input class="input" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="(00) 00000-0000">
                    @error('whatsapp')<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="level">Momento</label>
                    <select class="select" id="level" name="level" required>
                        @foreach (\App\Enums\LeadLevel::cases() as $level)
                            <option value="{{ $level->value }}" @selected(old('level') === $level->value)>{{ $level->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="plan_interest">Formato de interesse</label>
                    <select class="select" id="plan_interest" name="plan_interest">
                        <option value="">Ainda não sabe</option>
                        @foreach (\App\Enums\Plan::cases() as $plan)
                            <option value="{{ $plan->value }}" @selected(old('plan_interest') === $plan->value)>{{ $plan->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="channel">Canal</label>
                    <select class="select" id="channel" name="channel" required>
                        @foreach (\App\Enums\Channel::cases() as $channel)
                            <option value="{{ $channel->value }}" @selected(old('channel', 'indicacao') === $channel->value)>{{ $channel->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="utm_campaign">Campanha <span class="dim">(opcional)</span></label>
                <input class="input" id="utm_campaign" name="utm_campaign" value="{{ old('utm_campaign') }}">
            </div>
            <div class="field">
                <label for="goal">O que quer destravar</label>
                <textarea class="textarea" id="goal" name="goal" rows="3" maxlength="1000">{{ old('goal') }}</textarea>
            </div>
            <div><button class="btn btn--primary" type="submit">Cadastrar lead</button></div>
        </form>
    </div>
@endsection
