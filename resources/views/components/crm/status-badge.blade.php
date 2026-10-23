@props(['status'])

<span {{ $attributes->class(['badge', 'badge--'.$status->tone()]) }}>{{ $status->label() }}</span>
