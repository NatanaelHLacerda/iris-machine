@php
    $groups = [
        'Growth' => [
            ['route' => 'crm.dashboard', 'match' => 'crm.dashboard*', 'label' => 'Dashboard', 'icon' => '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>',
                'children' => [
                    ['url' => fn () => route('crm.dashboard'), 'label' => 'Visão geral', 'active' => request()->routeIs('crm.dashboard')],
                    ['url' => fn () => route('crm.dashboard.tab', 'funil'), 'label' => 'Funil', 'active' => request()->route('tab') === 'funil'],
                    ['url' => fn () => route('crm.dashboard.tab', 'aquisicao'), 'label' => 'Aquisição', 'active' => request()->route('tab') === 'aquisicao'],
                    ['url' => fn () => route('crm.dashboard.tab', 'receita'), 'label' => 'Receita', 'active' => request()->route('tab') === 'receita'],
                ]],
        ],
        'Marketing' => [
            ['route' => 'crm.ads', 'match' => 'crm.ads*', 'label' => 'Meta Ads', 'icon' => '<path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/>'],
        ],
        'Vendas' => [
            ['route' => 'crm.pipeline', 'match' => 'crm.pipeline', 'label' => 'Pipeline', 'icon' => '<rect x="3" y="4" width="5" height="16"/><rect x="10" y="4" width="5" height="10"/><rect x="17" y="4" width="4" height="6"/>'],
            ['route' => 'crm.leads.index', 'match' => 'crm.leads.*', 'label' => 'Leads', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['route' => 'crm.sales.index', 'match' => 'crm.sales.*', 'label' => 'Vendas', 'icon' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
        ],
        'Financeiro' => [
            ['route' => 'crm.cash', 'match' => 'crm.cash*', 'label' => 'Caixa', 'icon' => '<rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/><path d="M16 15h2"/>'],
        ],
    ];
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
@endphp

<aside class="sidebar" aria-label="Menu do CRM">
    <div class="sidebar__brand">
        <a class="logo" href="{{ route('crm.dashboard') }}">Iris Machine</a>
        <div class="sidebar__tag">Gestão CRM · Growth</div>
    </div>

    <nav class="sidebar__nav">
        @foreach ($groups as $title => $items)
            @php $items = array_filter($items, fn ($item) => Route::has($item['route'])); @endphp
            @continue(empty($items))
            <div class="nav-group">
                <div class="nav-group__title">{{ $title }}</div>
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}" @class(['nav-item', 'is-active' => request()->routeIs($item['match'])])>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                    @if (! empty($item['children']) && Route::has('crm.dashboard.tab'))
                        <div class="nav-sub">
                            @foreach ($item['children'] as $child)
                                <a href="{{ $child['url']() }}" @class(['is-active' => $child['active']])>{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="user-card">
        <div class="avatar" aria-hidden="true">{{ mb_strtoupper($initials) }}</div>
        <div style="flex:1;min-width:0">
            <div class="user-card__name" title="{{ $user->email }}">{{ $user->name }}</div>
            <div class="user-card__role">Admin</div>
        </div>
        <form method="POST" action="{{ route('crm.logout') }}">
            @csrf
            <button class="icon-btn" type="submit" aria-label="Sair" title="Sair">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            </button>
        </form>
    </div>
</aside>
