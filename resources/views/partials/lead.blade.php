<section class="section lead" id="agendar">
    <div class="lead__bg" aria-hidden="true"><span class="orb orb--pink"></span></div>

    <div class="container split split--lead">
        <div data-reveal>
            <h2 class="section__title">Bora marcar <span class="accent">uma conversa</span>?</h2>
            <p class="section__text">Deixe seu contato e conte rapidinho onde você está. A gente responde para combinar um horário — sem compromisso e sem spam.</p>

            <ul class="checklist">
                <li>Conversa inicial de diagnóstico</li>
                <li>Indicação do formato ideal para o seu momento</li>
                <li>Seus dados ficam só com a gente</li>
            </ul>
        </div>

        <div class="form-card" data-reveal>
            <div class="form-success" data-lead-success role="status" @unless (session('lead_ok')) hidden @endunless>
                <span class="form-success__icon" aria-hidden="true">✓</span>
                <h3>Contato recebido!</h3>
                <p data-lead-success-text>{{ session('lead_ok') }}</p>
            </div>

            <form class="form" method="POST" action="{{ route('leads.store') }}" data-lead-form novalidate @if (session('lead_ok')) hidden @endif>
                @csrf

                <div class="field">
                    <label for="lead-name">Nome</label>
                    <input id="lead-name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required maxlength="120" placeholder="Como podemos te chamar?" @error('name') aria-invalid="true" @enderror>
                    <p class="field__error" data-error="name">@error('name'){{ $message }}@enderror</p>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="lead-email">E-mail</label>
                        <input id="lead-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required maxlength="160" placeholder="voce@email.com" @error('email') aria-invalid="true" @enderror>
                        <p class="field__error" data-error="email">@error('email'){{ $message }}@enderror</p>
                    </div>
                    <div class="field">
                        <label for="lead-whatsapp">WhatsApp <small>(opcional)</small></label>
                        <input id="lead-whatsapp" name="whatsapp" type="tel" value="{{ old('whatsapp') }}" autocomplete="tel" maxlength="30" placeholder="(00) 00000-0000">
                        <p class="field__error" data-error="whatsapp">@error('whatsapp'){{ $message }}@enderror</p>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="lead-level">Seu momento</label>
                        <select id="lead-level" name="level" required @error('level') aria-invalid="true" @enderror>
                            <option value="" disabled @selected(! old('level'))>Selecione</option>
                            @foreach ($site['levels'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('level') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="field__error" data-error="level">@error('level'){{ $message }}@enderror</p>
                    </div>
                    <div class="field">
                        <label for="lead-plan">Formato de interesse</label>
                        <select id="lead-plan" name="plan">
                            <option value="">Ainda não sei</option>
                            @foreach ($site['plans'] as $plan)
                                <option value="{{ $plan['slug'] }}" @selected(old('plan') === $plan['slug'])>{{ $plan['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="field__error" data-error="plan">@error('plan'){{ $message }}@enderror</p>
                    </div>
                </div>

                <div class="field">
                    <label for="lead-goal">O que você quer destravar? <small>(opcional)</small></label>
                    <textarea id="lead-goal" name="goal" rows="3" maxlength="1000" placeholder="Ex.: quero usar agentes no meu TCC sem virar refém da IA">{{ old('goal') }}</textarea>
                    <p class="field__error" data-error="goal">@error('goal'){{ $message }}@enderror</p>
                </div>

                <div class="field field--hp" aria-hidden="true">
                    <label for="lead-site">Não preencha este campo</label>
                    <input id="lead-site" name="site" type="text" tabindex="-1" autocomplete="off">
                </div>

                <p class="field__error field__error--form" data-error="form">@error('site'){{ $message }}@enderror</p>

                <button class="btn btn--primary btn--block" type="submit">
                    <span data-submit-label>Quero agendar minha conversa</span> <span class="btn__arrow" aria-hidden="true">→</span>
                </button>
            </form>
        </div>
    </div>
</section>
