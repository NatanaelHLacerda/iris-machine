<!doctype html>
<html lang="pt-BR">
<head>
    @include('crm.layouts.head', ['title' => trim($__env->yieldContent('title'))])
</head>
<body>
    <div class="shell" data-shell>
        @include('crm.partials.sidebar')
        <div class="scrim" data-menu-close hidden></div>

        <main class="main" id="conteudo">
            <div class="topbar">
                <button class="icon-btn" type="button" data-menu-open aria-label="Abrir menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <span class="logo">Iris Machine</span>
            </div>

            @if (session('status'))
                <div class="flash flash--ok" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash flash--error" role="alert">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
