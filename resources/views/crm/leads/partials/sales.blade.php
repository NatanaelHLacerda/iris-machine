<section class="card" id="vendas">
    <div class="card__head"><h2 class="card__title">Vendas</h2>
        <span class="dim" style="font-size:12px">Recebido: <x-crm.money :cents="(int) $lead->payments->where('status', \App\Enums\PaymentStatus::Paid)->sum('amount_cents')" /></span>
    </div>
    @forelse ($lead->sales as $sale)
        <div style="padding:10px 0;border-top:1px solid var(--line-soft)">
            <div style="display:flex;justify-content:space-between;gap:10px">
                <strong>{{ $sale->plan->label() }}</strong>
                <span class="num"><x-crm.money :cents="$sale->amount_cents" />{{ $sale->is_recurring ? '/mês' : '' }}</span>
            </div>
            <div class="dim" style="font-size:12px;margin-top:2px">
                <x-crm.datetime :value="$sale->sold_at" format="d/m/Y" />
                @if ($sale->subscription)
                    · assinatura {{ mb_strtolower($sale->subscription->status->label()) }}
                @endif
                · {{ $sale->payments->where('status', \App\Enums\PaymentStatus::Paid)->count() }}/{{ $sale->payments->count() }} cobranças pagas
            </div>
        </div>
    @empty
        <p class="dim" style="font-size:13px">Nenhuma venda ainda.</p>
    @endforelse
</section>
