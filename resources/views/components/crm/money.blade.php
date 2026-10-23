@props(['cents', 'short' => false])

<span {{ $attributes->class(['num']) }}>{{ $short ? \App\Support\Crm\Money::short($cents) : \App\Support\Crm\Money::format($cents) }}</span>
