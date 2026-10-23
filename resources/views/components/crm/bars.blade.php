@props(['data', 'money' => true, 'accentFirst' => false])

@php $max = max([...array_values($data), 1]); @endphp

<div class="bars">
    @forelse ($data as $label => $value)
        <div class="bar-row">
            <span class="bar-row__label" title="{{ $label }}">{{ $label }}</span>
            <span class="bar-row__track"><span @class(['bar-row__fill', 'bar-row__fill--pink' => $accentFirst && $loop->first]) style="width: {{ round($value / $max * 100, 1) }}%"></span></span>
            <span class="bar-row__value">{{ $money ? \App\Support\Crm\Money::format((int) $value) : number_format($value, 0, ',', '.') }}</span>
        </div>
    @empty
        <p class="dim" style="font-size:13px">Sem dados no período.</p>
    @endforelse
</div>
