@props(['channel'])

<span @class(['badge', 'badge--pink' => $channel === \App\Enums\Channel::Meta, 'badge--info' => $channel !== \App\Enums\Channel::Meta])>{{ $channel->label() }}</span>
