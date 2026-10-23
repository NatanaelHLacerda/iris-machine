<?php

namespace Tests\Feature\Crm;

use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Crm\ChargeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create();
    }

    public function test_selling_a_mentorship_creates_subscription_charge_and_closes_the_lead(): void
    {
        $lead = Lead::factory()->fromMeta('vibe-coding-junior')->status(LeadStatus::Proposta)->create();

        $this->actingAs($this->admin)->post(route('crm.leads.sales.store', $lead), [
            'plan' => 'mentoria',
            'amount' => '399,90',
            'sold_at' => now('America/Sao_Paulo')->toDateString(),
            'paid_now' => '1',
            'method' => 'pix',
        ])->assertRedirect(route('crm.leads.show', $lead));

        $lead->refresh();
        $sale = $lead->sales()->sole();
        $this->assertSame(LeadStatus::Fechado, $lead->status);
        $this->assertSame(39990, $sale->amount_cents);
        $this->assertTrue($sale->is_recurring);
        $this->assertSame('vibe-coding-junior', $sale->utm_campaign);
        $this->assertSame(SubscriptionStatus::Active, $sale->subscription->status);
        $this->assertSame(PaymentStatus::Paid, $sale->payments()->sole()->status);
    }

    public function test_a_single_session_uses_the_table_price_and_is_not_recurring(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin)->post(route('crm.leads.sales.store', $lead), [
            'plan' => 'sessao',
            'sold_at' => now('America/Sao_Paulo')->toDateString(),
        ])->assertRedirect();

        $sale = $lead->sales()->sole();
        $this->assertSame(12990, $sale->amount_cents);
        $this->assertNull($sale->subscription);
        $this->assertSame(PaymentStatus::Pending, $sale->payments()->sole()->status);
    }

    public function test_squad_requires_an_amount(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin)->post(route('crm.leads.sales.store', $lead), [
            'plan' => 'squad',
            'sold_at' => now('America/Sao_Paulo')->toDateString(),
        ])->assertSessionHasErrors('amount_cents');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(LeadStatus::Novo, $lead->fresh()->status);
    }

    public function test_paying_and_refunding_a_charge(): void
    {
        $payment = Payment::factory()->create();

        $this->actingAs($this->admin)->patch(route('crm.payments.pay', $payment), [
            'paid_at' => now('America/Sao_Paulo')->toDateString(),
            'method' => 'cartao',
        ])->assertRedirect();
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);

        $this->actingAs($this->admin)->patch(route('crm.payments.refund', $payment))->assertRedirect();
        $this->assertSame(PaymentStatus::Refunded, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->refunded_at);
    }

    public function test_canceling_a_subscription_drops_future_open_charges(): void
    {
        $subscription = Subscription::factory()->create(['started_at' => now()->subMonths(2)]);
        $future = Payment::factory()->create([
            'sale_id' => $subscription->sale_id,
            'subscription_id' => $subscription->id,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($this->admin)->patch(route('crm.subscriptions.cancel', $subscription), [
            'canceled_at' => now('America/Sao_Paulo')->toDateString(),
            'cancel_reason' => 'Conseguiu a vaga',
        ])->assertRedirect();

        $this->assertSame(SubscriptionStatus::Canceled, $subscription->fresh()->status);
        $this->assertModelMissing($future);
    }

    public function test_charge_generator_is_idempotent_and_skips_canceled(): void
    {
        $active = Subscription::factory()->create(['billing_day' => 5, 'started_at' => '2026-08-05 12:00:00']);
        Subscription::factory()->canceled()->create(['billing_day' => 5, 'started_at' => '2026-08-05 12:00:00']);
        $generator = new ChargeGenerator;
        $today = CarbonImmutable::parse('2026-10-06 09:00', 'America/Sao_Paulo');

        $this->assertSame(1, $generator->run($today));
        $this->assertSame(0, $generator->run($today));
        $this->assertSame('2026-10-05', $active->payments()->sole()->due_date->toDateString());
    }

    public function test_charge_generator_waits_for_the_billing_day(): void
    {
        Subscription::factory()->create(['billing_day' => 20, 'started_at' => '2026-08-20 12:00:00']);

        $this->assertSame(0, (new ChargeGenerator)->run(CarbonImmutable::parse('2026-10-06', 'America/Sao_Paulo')));
    }

    public function test_sales_page_renders_every_tab(): void
    {
        $subscription = Subscription::factory()->create();
        Payment::factory()->create(['sale_id' => $subscription->sale_id, 'due_date' => now()->subDays(3)->toDateString()]);

        foreach (['vendas', 'assinaturas', 'cobrancas'] as $tab) {
            $this->actingAs($this->admin)->get(route('crm.sales.index', ['aba' => $tab]))->assertOk();
        }

        $this->actingAs($this->admin)->get(route('crm.sales.index', ['aba' => 'cobrancas']))->assertSee('Atrasada');
    }
}
