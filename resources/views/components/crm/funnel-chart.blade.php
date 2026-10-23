@props(['steps'])

@php $max = max($steps->max('count'), 1); @endphp

<div class="funnel" role="list" aria-label="Funil de conversão">
    @foreach ($steps as $step)
        <div @class(['funnel__step', 'is-bottleneck' => $step->isBottleneck]) role="listitem">
            <span class="funnel__label">{{ $step->label }}</span>
            <span class="funnel__bar"><span class="funnel__fill" style="width: {{ max(round($step->count / $max * 100, 1), 0.5) }}%">{{ number_format($step->count, 0, ',', '.') }}</span></span>
            <span class="funnel__rate">
                @if ($step->rateFromPrevious === null)
                    @if (! $loop->first) — @endif
                @else
                    {{ number_format($step->rateFromPrevious * 100, 1, ',', '.') }}%
                @endif
                @if ($step->isBottleneck)<br><span style="font-size:11px">maior perda</span>@endif
            </span>
        </div>
    @endforeach
</div>
