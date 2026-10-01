<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Iris Machine · Consultoria em IA para devs júnior')</title>
    <meta name="description" content="Consultoria em IA aplicada para desenvolvedores júnior: aprenda a usar agentes, skills e Claude Code no seu dia a dia.">
    <meta name="theme-color" content="#0B0910">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Só esconde os elementos animados se o JS realmente for rodar. --}}
    <script>
        (function (d) {
            if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            d.classList.add('motion');
            setTimeout(function () { if (!window.__irisReady) d.classList.remove('motion'); }, 4000);
        })(document.documentElement);
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
    <div class="curtain" aria-hidden="true"><span>IRIS MACHINE</span></div>

    @yield('content')
</body>
</html>
