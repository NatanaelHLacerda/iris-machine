<section class="hero">
    <div class="hero__bg" aria-hidden="true">
        <span class="orb orb--pink"></span>
        <span class="orb orb--violet"></span>
        <img class="hero__art" src="{{ asset('images/hero-iris.jpg') }}" alt="" width="1024" height="1024" fetchpriority="low">
    </div>

    <div class="container hero__inner">
        <div class="hero__copy">
            <h1 class="hero__title" data-split>
                {{ $site['hero']['title'] }} <span class="accent">{{ $site['hero']['accent'] }}</span> {{ $site['hero']['suffix'] }}.
            </h1>

            <p class="hero__text" data-hero-item>{{ $site['hero']['text'] }}</p>

            <ul class="hero__benefits" data-hero-item>
                @foreach ($site['hero']['benefits'] as $benefit)
                    <li>{{ $benefit }}</li>
                @endforeach
            </ul>

            <div class="hero__actions" data-hero-item>
                <a href="#agendar" class="btn btn--primary" data-magnetic>
                    Agendar conversa <span class="btn__arrow" aria-hidden="true">→</span>
                </a>
                <a href="#trilha" class="btn btn--link">Ver a trilha</a>
            </div>

            <p class="hero__note" data-hero-item>{{ $site['hero']['note'] }}</p>
        </div>
    </div>
</section>
