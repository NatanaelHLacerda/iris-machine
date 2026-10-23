<section class="hero">
    <div class="hero__bg" aria-hidden="true">
        <span class="orb orb--pink"></span>
        <span class="orb orb--violet"></span>
        <span class="grid-lines"></span>
    </div>

    <div class="container hero__inner">
        <div class="hero__copy">
            <h1 class="hero__title" data-split>
                {{ $site['hero']['title'] }} <span class="accent">{{ $site['hero']['accent'] }}</span> {{ $site['hero']['suffix'] }}.
            </h1>

            <p class="hero__text" data-hero-item>{{ $site['hero']['text'] }}</p>

            <div class="hero__actions" data-hero-item>
                <a href="#agendar" class="btn btn--primary" data-magnetic>
                    Agendar conversa <span class="btn__arrow" aria-hidden="true">→</span>
                </a>
                <a href="#trilha" class="btn btn--link">Ver a trilha</a>
            </div>
        </div>
    </div>
</section>
