@props(['data', 'label' => 'Gráfico', 'height' => 160])

@php
    $values = array_values($data);
    $keys = array_keys($data);
    $count = count($values);
    $min = min([...$values, 0]);
    $max = max([...$values, 1]);
    $range = max($max - $min, 1);
    $width = 640;
    $chartHeight = $height - 22;
    $x = fn ($index) => $count > 1 ? $index * $width / ($count - 1) : $width / 2;
    $y = fn ($value) => $chartHeight - ($value - $min) / $range * $chartHeight;
    $points = collect($values)->map(fn ($value, $index) => round($x($index), 1).','.round($y($value), 1))->implode(' ');
    $labelEvery = (int) ceil(max($count, 1) / 8);
@endphp

<svg class="chart" viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $label }}" preserveAspectRatio="none">
    @foreach ([0, 0.5, 1] as $line)
        <line class="grid-line" x1="0" x2="{{ $width }}" y1="{{ $chartHeight * $line }}" y2="{{ $chartHeight * $line }}" />
    @endforeach
    @if ($min < 0)
        <line class="goal-line" x1="0" x2="{{ $width }}" y1="{{ $y(0) }}" y2="{{ $y(0) }}" />
    @endif
    @if ($count > 1)
        <polygon class="area" points="0,{{ $chartHeight }} {{ $points }} {{ $width }},{{ $chartHeight }}" />
        <polyline class="line" points="{{ $points }}" />
    @endif
    @foreach ($keys as $index => $key)
        @if ($index % $labelEvery === 0 || $index === $count - 1)
            <text x="{{ $x($index) }}" y="{{ $height - 4 }}" text-anchor="{{ $index === 0 ? 'start' : ($index === $count - 1 ? 'end' : 'middle') }}">{{ $key }}</text>
        @endif
    @endforeach
    <text x="4" y="12">{{ \App\Support\Crm\Money::format((int) $max) }}</text>
</svg>
