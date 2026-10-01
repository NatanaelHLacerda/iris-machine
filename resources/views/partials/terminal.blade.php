<section class="section">
    <div class="container split">
        <div data-reveal>
            <h2 class="section__title">De “pede pro chat” para <span class="accent">um fluxo que você controla</span>.</h2>
            <p class="section__text">Um agente bem configurado lê o repositório, segue as regras do time e mostra o trabalho. Você aprende a montar isso do zero.</p>
        </div>

        <div class="terminal" data-terminal data-reveal>
            <div class="terminal__bar" aria-hidden="true">
                <span></span><span></span><span></span>
                <em>~/seu-projeto</em>
            </div>
            <div class="terminal__body" role="img" aria-label="Exemplo ilustrativo de um agente revisando um pull request com uma skill">
                @foreach ($site['terminal'] as $line)
                    <p class="terminal__line terminal__line--{{ $line['type'] }}" data-line="{{ $line['type'] }}">
                        @if ($line['type'] === 'cmd')
                            <span class="terminal__prompt">{{ $line['prompt'] ?? '$' }}</span>
                        @elseif ($line['type'] === 'ok')
                            <span class="terminal__prompt">✓</span>
                        @else
                            <span class="terminal__prompt">●</span>
                        @endif
                        <span data-text>{{ $line['text'] }}</span>
                    </p>
                @endforeach
                <span class="terminal__cursor" aria-hidden="true"></span>
            </div>
        </div>
    </div>
</section>
