@props(['value', 'format' => 'd/m/Y H:i'])

@if ($value)
    <time datetime="{{ $value->toIso8601String() }}" class="num">{{ $value->copy()->setTimezone(\App\Support\Crm\Period::TIMEZONE)->format($format) }}</time>
@else
    <span class="dim">—</span>
@endif
