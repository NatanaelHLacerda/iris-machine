@props(['data', 'label' => 'Gráfico', 'money' => true, 'goal' => null, 'highlightLast' => false, 'height' => 160])

@php
    $values = array_values($data);
    $keys = array_keys($data);
    $count = max(count($values), 1);
    $max = max([...$values, $goal ?? 0, 1]);
    $width = 640;
    $chartHeight = $height - 22;
    $slot = $width / $count;
    $bar = max(min($slot * 0.62, 36), 2);
    $labelEvery = (int) ceil($count / 12);
    $format = fn ($value) => $money ? \App\Support\Crm\Money::format((int) $value) : number_format($value, 0, ',', '.');
@endphp

<svg class="chart" viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $label }}" preserveAspectRatio="none">
    @foreach ([0.25, 0.5, 0.75, 1] as $line)
        <line class="grid-line" x1="0" x2="{{ $width }}" y1="{{ $chartHeight - $chartHeight * $line }}" y2="{{ $chartHeight - $chartHeight * $line }}" />
    @endforeach
    @foreach ($values as $index => $value)
        @php $barHeight = $value > 0 ? max($chartHeight * $value / $max, 2) : 0; @endphp
        <rect @class(['col', 'col--accent' => $highlightLast && $index === $count - 1])
              x="{{ $index * $slot + ($slot - $bar) / 2 }}" y="{{ $chartHeight - $barHeight }}" width="{{ $bar }}" height="{{ $barHeight }}" rx="3">
            <title>{{ $keys[$index] }}: {{ $format($value) }}</title>
        </rect>
        @if ($index % $labelEvery === 0)
            <text x="{{ $index * $slot + $slot / 2 }}" y="{{ $height - 4 }}" text-anchor="middle">{{ $keys[$index] }}</text>
        @endif
    @endforeach
    @if ($goal)
        <line class="goal-line" x1="0" x2="{{ $width }}" y1="{{ $chartHeight - $chartHeight * $goal / $max }}" y2="{{ $chartHeight - $chartHeight * $goal / $max }}" />
        <text x="{{ $width - 4 }}" y="{{ $chartHeight - $chartHeight * $goal / $max - 5 }}" text-anchor="end">meta {{ $format($goal) }}</text>
    @endif
</svg>
