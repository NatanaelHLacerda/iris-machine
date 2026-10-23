@props(['kpi'])

@php
    $format = function ($value) use ($kpi) {
        if ($value === null) {
            return '—';
        }

        return match ($kpi->format) {
            'money' => \App\Support\Crm\Money::format((int) $value),
            'percent' => number_format($value * 100, 1, ',', '.').'%',
            'multiplier' => number_format($value, 1, ',', '.').'×',
            default => number_format($value, 0, ',', '.'),
        };
    };
    $delta = $kpi->deltaPercent();
    $improved = $kpi->isImprovement();
@endphp

<div class="card">
    <div class="kpi__label" @if ($kpi->hint) title="{{ $kpi->hint }}" @endif>{{ $kpi->label }}</div>
    <div class="kpi__value">{{ $format($kpi->value) }}</div>
    <div class="kpi__delta">
        @if ($delta !== null)
            <span @class(['delta--up' => $improved === true, 'delta--down' => $improved === false])>
                {{ $delta >= 0 ? '▲' : '▼' }} {{ number_format(abs($delta) * 100, 0, ',', '.') }}%
            </span>
            vs período anterior ({{ $format($kpi->previous) }})
        @elseif ($kpi->previous === null && $kpi->hint)
            {{ $kpi->hint }}
        @else
            sem base de comparação
        @endif
    </div>
</div>
