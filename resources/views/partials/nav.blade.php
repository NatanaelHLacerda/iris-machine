<header class="nav" data-nav>
    <div class="container nav__inner">
        <a class="logo" href="{{ route('home') }}" aria-label="Iris Machine — início">IRIS MACHINE</a>

        <nav class="nav__links" aria-label="Seções">
            @foreach ($site['nav'] as $link)
                <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <a class="btn btn--ghost btn--sm nav__cta" href="#agendar">Agendar conversa</a>
    </div>
</header>
