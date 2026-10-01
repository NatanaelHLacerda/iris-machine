<section class="section section--band" id="trilha">
    <div class="container">
        <header class="section__head" data-reveal>
            <h2 class="section__title">Quatro módulos para trabalhar com IA <span class="accent">como gente grande</span>.</h2>
            <p class="section__text">Menos prompt mágico, mais engenharia: você aprende os conceitos que não mudam quando a ferramenta muda.</p>
        </header>

        <div class="cards">
            @foreach ($site['modules'] as $module)
                <article class="card" data-reveal-batch data-glow>
                    <span class="card__code">{{ $module['code'] }}</span>
                    <h3 class="card__title">{{ $module['title'] }}</h3>
                    <p class="card__text">{{ $module['text'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
