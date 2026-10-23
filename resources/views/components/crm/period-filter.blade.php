@props(['period', 'action' => null, 'keep' => []])

@php $current = request('periodo', 'mes'); @endphp

<form method="GET" action="{{ $action ?? url()->current() }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center" data-period-form>
    @foreach ($keep as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <select class="select" name="periodo" aria-label="Período" data-period-select>
        @foreach (\App\Support\Crm\Period::PRESETS as $value => $label)
            <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
        @endforeach
        <option value="custom" @selected($current === 'custom')>Personalizado</option>
    </select>
    <span data-period-custom @if ($current !== 'custom') hidden @endif style="display:inline-flex;gap:8px">
        <input class="input" type="date" name="de" value="{{ request('de') }}" aria-label="De">
        <input class="input" type="date" name="ate" value="{{ request('ate') }}" aria-label="Até">
        <button class="btn btn--ghost" type="submit">Aplicar</button>
    </span>
    <span class="dim num" style="font-size:12px">{{ \App\Support\Crm\Period::label($period) }}</span>
</form>
