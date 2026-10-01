<section class="section section--glow" id="formatos">
    <div class="container">
        <header class="section__head section__head--center" data-reveal>
            <h2 class="section__title">Escolha como quer <span class="accent">subir de nível</span>.</h2>
            <p class="section__text">Tudo começa com uma conversa sem compromisso. A gente indica o formato que faz sentido para você.</p>
        </header>

        <div class="plans">
            @foreach ($site['plans'] as $plan)
                <article @class(['plan', 'plan--featured' => $plan['featured']]) data-reveal-batch data-glow>
                    @if ($plan['featured'])
                        <span class="plan__badge">recomendado</span>
                    @endif
                    <h3 class="plan__name">{{ $plan['name'] }}</h3>
                    <p class="plan__tagline">{{ $plan['tagline'] }}</p>
                    <p @class(['plan__price', 'plan__price--ask' => ! $plan['price']])>{{ $plan['price'] ?? 'Sob consulta' }}</p>
                    <ul class="plan__features">
                        @foreach ($plan['features'] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="#agendar" data-plan="{{ $plan['slug'] }}" @class(['btn', 'btn--primary' => $plan['featured'], 'btn--ghost' => ! $plan['featured']])>
                        Quero esse
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>
