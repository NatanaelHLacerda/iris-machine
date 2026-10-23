@props(['value'])

@if (filled($value))
    <span class="chip" title="{{ $value }}">{{ $value }}</span>
@else
    <span class="dim">—</span>
@endif
