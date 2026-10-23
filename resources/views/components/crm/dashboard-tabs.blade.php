@props(['current'])

<nav class="tabs" aria-label="Abas do dashboard">
    <a href="{{ route('crm.dashboard', request()->only('periodo', 'de', 'ate')) }}" @class(['is-active' => $current === null])>Visão geral</a>
    @foreach (['funil' => 'Funil', 'aquisicao' => 'Aquisição', 'receita' => 'Receita'] as $tab => $label)
        <a href="{{ route('crm.dashboard.tab', [$tab, ...request()->only('periodo', 'de', 'ate')]) }}" @class(['is-active' => $current === $tab])>{{ $label }}</a>
    @endforeach
</nav>
