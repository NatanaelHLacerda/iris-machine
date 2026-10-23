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

        <div class="terminal hero__terminal" data-hero-item>
            <div class="terminal__bar" aria-hidden="true">
                <span></span><span></span><span></span>
                <em>git log --graph</em>
            </div>
            <div class="terminal__body hero__graph">
                <svg class="git-graph" viewBox="0 0 60 36" role="img" aria-label="Ilustração em pixel art de um histórico do Git: branches de feature e de correção saem da main e voltam com merge" data-git-graph>
                    <text class="git-graph__label" x="3" y="15">main</text>
                    <text class="git-graph__label git-graph__label--pink" x="21" y="5">feat/api-vagas</text>
                    <text class="git-graph__label git-graph__label--violet" x="27" y="34">fix/seguranca</text>

                    <g transform="translate(0.5 0.5)">
                        <rect class="git-graph__node" x="2.5" y="16.5" width="3" height="3" data-node />
                        <path class="git-graph__line" d="M4 18 H12" pathLength="1" data-draw />
                        <rect class="git-graph__node" x="10.5" y="16.5" width="3" height="3" data-node />
                        <path class="git-graph__line git-graph__line--pink" d="M12 18 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 H24" pathLength="1" data-draw />
                        <rect class="git-graph__node git-graph__node--pink" x="22.5" y="6.5" width="3" height="3" data-node />
                        <path class="git-graph__line" d="M12 18 H26" pathLength="1" data-draw />
                        <rect class="git-graph__node" x="24.5" y="16.5" width="3" height="3" data-node />
                        <path class="git-graph__line git-graph__line--pink" d="M24 8 H30" pathLength="1" data-draw />
                        <rect class="git-graph__node git-graph__node--pink" x="28.5" y="6.5" width="3" height="3" data-node />
                        <path class="git-graph__line git-graph__line--violet" d="M26 18 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 H38" pathLength="1" data-draw />
                        <rect class="git-graph__node git-graph__node--violet" x="36.5" y="26.5" width="3" height="3" data-node />
                        <path class="git-graph__line git-graph__line--pink" d="M30 8 H36" pathLength="1" data-draw />
                        <rect class="git-graph__node git-graph__node--pink" x="34.5" y="6.5" width="3" height="3" data-node />
                        <path class="git-graph__line git-graph__line--pink" d="M36 8 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 h1 v1 H46" pathLength="1" data-draw />
                        <path class="git-graph__line" d="M26 18 H46" pathLength="1" data-draw />
                        <rect class="git-graph__node git-graph__node--pink" x="44.5" y="16.5" width="3" height="3" data-node />
                        <path class="git-graph__line git-graph__line--violet" d="M38 28 H40 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 h1 v-1 H50" pathLength="1" data-draw />
                        <path class="git-graph__line" d="M46 18 H50" pathLength="1" data-draw />
                        <rect class="git-graph__node git-graph__node--violet" x="48.5" y="16.5" width="3" height="3" data-node />
                        <path class="git-graph__line" d="M50 18 H56" pathLength="1" data-draw />
                        <rect class="git-graph__node git-graph__node--head" x="54.5" y="16.5" width="3" height="3" data-node />
                    </g>

                    <text class="git-graph__label git-graph__label--ok" x="58" y="14" text-anchor="end" data-node>✓ deploy</text>
                </svg>
            </div>
        </div>
    </div>
</section>
