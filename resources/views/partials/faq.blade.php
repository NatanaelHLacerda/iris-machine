<section class="section" id="faq">
    <div class="container container--narrow">
        <header class="section__head" data-reveal>
            <h2 class="section__title">Perguntas que todo mundo faz.</h2>
        </header>

        <div class="faq">
            @foreach ($site['faq'] as $item)
                <details class="faq__item" data-reveal-batch>
                    <summary>{{ $item['q'] }}<span class="faq__icon" aria-hidden="true"></span></summary>
                    <div class="faq__answer"><p>{{ $item['a'] }}</p></div>
                </details>
            @endforeach
        </div>
    </div>
</section>
