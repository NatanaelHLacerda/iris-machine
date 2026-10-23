<?php

namespace Tests\Feature\Crm;

use App\Enums\PaymentStatus;
use App\Models\AdSpend;
use App\Models\CashEntry;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Support\Crm\CashFlow;
use App\Support\Crm\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create();
    }

    private function openWith(int $cents): void
    {
        Setting::put(CashFlow::OPENING_BALANCE_KEY, ['amount_cents' => $cents, 'date' => Period::today()->startOfMonth()->toDateString()]);
    }

    public function test_balance_is_unknown_without_opening_balance(): void
    {
        Payment::factory()->paid()->create();

        $this->assertNull(app(CashFlow::class)->balance());
        $this->actingAs($this->admin)->get(route('crm.cash'))->assertOk()->assertSee('Definir saldo inicial');
    }

    public function test_balance_adds_payments_and_subtracts_ads_refunds_and_costs(): void
    {
        $this->openWith(100000);
        Payment::factory()->paid()->create(['amount_cents' => 39990]);
        $refunded = Payment::factory()->paid()->create(['amount_cents' => 12990]);
        $refunded->update(['status' => PaymentStatus::Refunded, 'refunded_at' => now()]);
        AdSpend::factory()->create(['spend_cents' => 5000]);
        CashEntry::factory()->create(['amount_cents' => 11000]);

        $this->assertSame(100000 + 39990 + 12990 - 12990 - 5000 - 11000, app(CashFlow::class)->balance());
    }

    public function test_summary_reports_result_margin_and_categories(): void
    {
        $this->openWith(0);
        Payment::factory()->paid()->create(['amount_cents' => 40000]);
        AdSpend::factory()->create(['spend_cents' => 10000]);

        $summary = app(CashFlow::class)->summary(Period::preset('mes'));

        $this->assertSame(30000, $summary['result']);
        $this->assertEqualsWithDelta(0.75, $summary['margin'], 0.001);
        $this->assertSame(['Anúncios' => 10000], $summary['out_by_category']);
    }

    public function test_statement_marks_only_manual_entries_as_editable(): void
    {
        $this->openWith(0);
        Payment::factory()->paid()->create();
        CashEntry::factory()->create();

        $statement = app(CashFlow::class)->statement(Period::preset('mes'));

        $this->assertEquals(['manual' => true, 'payment' => false], $statement->pluck('editable', 'origin')->all());
    }

    public function test_admin_manages_manual_entries(): void
    {
        $this->actingAs($this->admin)->post(route('crm.cash.entries.store'), [
            'date' => now('America/Sao_Paulo')->toDateString(),
            'direction' => 'out',
            'category' => 'infraestrutura',
            'description' => 'Hospedagem',
            'amount' => '60,00',
        ])->assertSessionHas('status');

        $entry = CashEntry::sole();
        $this->assertSame(6000, $entry->amount_cents);

        $this->actingAs($this->admin)->delete(route('crm.cash.entries.destroy', $entry))->assertSessionHas('status');
        $this->assertModelMissing($entry);
    }

    public function test_manual_entries_cannot_pretend_to_be_student_payments(): void
    {
        $this->actingAs($this->admin)->post(route('crm.cash.entries.store'), [
            'date' => now('America/Sao_Paulo')->toDateString(),
            'direction' => 'in',
            'category' => 'alunos',
            'description' => 'Fake',
            'amount' => '10,00',
        ])->assertSessionHasErrors('category');
    }

    public function test_admin_sets_the_opening_balance(): void
    {
        $this->actingAs($this->admin)->put(route('crm.cash.opening'), ['amount' => '1.500,00', 'date' => '2026-10-01'])
            ->assertSessionHas('status');

        $this->assertSame(150000, app(CashFlow::class)->openingBalance()['amount_cents']);
    }

    public function test_cash_page_renders_with_movements(): void
    {
        $this->openWith(50000);
        Payment::factory()->paid()->create();
        CashEntry::factory()->create(['description' => 'Assinatura do Claude']);

        $this->actingAs($this->admin)->get(route('crm.cash'))
            ->assertOk()
            ->assertSee('Assinatura do Claude')
            ->assertSee('Saldo em conta hoje');
    }
}
