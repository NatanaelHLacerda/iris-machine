<?php

namespace Tests\Feature\Crm;

use App\Models\AdSpend;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Support\Crm\AdMetrics;
use App\Support\Crm\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create();
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('meta.csv', $content);
    }

    public function test_it_imports_the_portuguese_ads_manager_export(): void
    {
        $file = $this->csv("\xEF\xBB\xBF".implode("\n", [
            'Dia,Nome da campanha,Nome do conjunto de anúncios,Nome do anúncio,Valor usado (BRL),Impressões,Cliques no link',
            '2026-10-20,Vibe Coding Junior,Estudantes,Video 1,"45,50",3200,61',
            '2026-10-21,Vibe Coding Junior,Estudantes,Video 1,"50,00",3500,70',
            ',,,,"95,50",6700,131',
        ]));

        $this->actingAs($this->admin)->post(route('crm.ads.import'), ['file' => $file])
            ->assertSessionHas('status', 'Importação concluída: 2 novas linhas, 0 atualizadas, 1 ignoradas.');

        $this->assertSame(9550, (int) AdSpend::sum('spend_cents'));
        $this->assertSame('vibe coding junior', AdSpend::first()->campaign_key);
    }

    public function test_reimporting_updates_instead_of_duplicating(): void
    {
        $header = "Day;Campaign name;Amount spent (BRL)\n";

        $this->actingAs($this->admin)->post(route('crm.ads.import'), ['file' => $this->csv($header.'2026-10-20;remarketing-lp;10.00')]);
        $this->actingAs($this->admin)->post(route('crm.ads.import'), ['file' => $this->csv($header.'2026-10-20;remarketing-lp;12.00')])
            ->assertSessionHas('status', 'Importação concluída: 0 novas linhas, 1 atualizadas, 0 ignoradas.');

        $this->assertSame(1200, (int) AdSpend::sole()->spend_cents);
    }

    public function test_a_csv_without_required_columns_imports_nothing(): void
    {
        $this->actingAs($this->admin)
            ->post(route('crm.ads.import'), ['file' => $this->csv("Campanha,Gasto\nx,10")])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('ad_spends', 0);
    }

    public function test_manual_spend_entry(): void
    {
        $this->actingAs($this->admin)->post(route('crm.ads.store'), [
            'date' => now('America/Sao_Paulo')->toDateString(),
            'campaign' => 'Portfolio Recrutador',
            'spend' => '30,00',
        ])->assertSessionHas('status');

        $this->assertSame(3000, (int) AdSpend::sole()->spend_cents);
    }

    public function test_metrics_cross_spend_with_leads_customers_and_revenue(): void
    {
        $today = now('America/Sao_Paulo')->toDateString();
        AdSpend::factory()->campaign('vibe-coding-junior')->create(['date' => $today, 'spend_cents' => 20000]);
        Lead::factory()->count(3)->fromMeta('vibe-coding-junior')->create();
        $sale = Sale::factory()->mentoria()->create(['channel' => 'meta', 'utm_campaign' => 'vibe-coding-junior']);
        Payment::factory()->paid()->create(['sale_id' => $sale->id, 'amount_cents' => 39990]);

        $row = app(AdMetrics::class)->byCampaign(Period::preset('mes'))->firstWhere('key', 'vibe-coding-junior');

        $this->assertSame(20000, $row['spend']);
        $this->assertSame(3, $row['leads']);
        $this->assertSame(6666, $row['cpl']);
        $this->assertSame(1, $row['customers']);
        $this->assertSame(20000, $row['cac']);
        $this->assertEqualsWithDelta(2.0, $row['roas'], 0.01);
        $this->assertFalse($row['is_unprofitable']);
    }

    public function test_a_campaign_whose_cac_exceeds_ltv_is_flagged(): void
    {
        AdSpend::factory()->campaign('claude-code-tcc')->create(['date' => now('America/Sao_Paulo')->toDateString(), 'spend_cents' => 200000]);
        Sale::factory()->mentoria()->create(['channel' => 'meta', 'utm_campaign' => 'claude-code-tcc']);

        $row = app(AdMetrics::class)->byCampaign(Period::preset('mes'))->firstWhere('key', 'claude-code-tcc');

        $this->assertTrue($row['is_unprofitable']);
    }

    public function test_page_shows_dashes_without_spend(): void
    {
        $this->actingAs($this->admin)
            ->get(route('crm.ads'))
            ->assertOk()
            ->assertSee('Nenhum gasto registrado no período')
            ->assertSee('utm_source=meta');
    }
}
