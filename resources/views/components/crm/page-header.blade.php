@props(['eyebrow' => null, 'title', 'total' => null])

<header class="page-header">
    <div>
        @if ($eyebrow)
            <div class="page-header__eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1 class="page-header__title">
            {{ $title }}
            @if ($total !== null)
                <span class="page-header__total">{{ $total }}</span>
            @endif
        </h1>
    </div>
    @if ($slot->isNotEmpty())
        <div class="page-header__actions">{{ $slot }}</div>
    @endif
</header>
