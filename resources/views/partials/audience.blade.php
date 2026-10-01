<section class="section" aria-labelledby="audience-title">
    <div class="container">
        <header class="section__head" data-reveal>
            <h2 class="section__title" id="audience-title">Feita para quem está <span class="accent">começando</span>.</h2>
        </header>

        <ul class="audience">
            @foreach ($site['audience'] as $i => $item)
                <li class="audience__item" data-reveal-batch>
                    <span class="audience__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    {{ $item }}
                </li>
            @endforeach
        </ul>
    </div>
</section>
