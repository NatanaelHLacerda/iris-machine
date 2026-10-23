<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use App\Support\Crm\CashFlow;
use App\Support\Crm\ChargeGenerator;
use App\Support\Crm\GoalProgress;
use App\Support\Crm\LeadPipeline;
use App\Support\Crm\SaleRecorder;
use Database\Seeders\Demo\DemoScenario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Dados de demonstração do CRM. Só pelo comando crm:demo, nunca em produção.
 */
class DemoSeeder extends Seeder
{
    public const string ADMIN_EMAIL = 'admin@iris.test';

    public ?string $password = null;

    public float $scale = 1.0;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Dados de demonstração não rodam em produção.');
        }

        mt_srand(2026);
        fake('pt_BR')->seed(2026);
        fake()->seed(2026);

        $this->password = Str::password(16, symbols: false);

        User::updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            ['name' => 'Admin Iris', 'password' => $this->password],
        );

        $scenario = new DemoScenario(app(LeadPipeline::class), app(SaleRecorder::class), app(ChargeGenerator::class), $this->scale);

        Setting::put(CashFlow::OPENING_BALANCE_KEY, ['amount_cents' => 150000, 'date' => $scenario->start()->toDateString()]);
        Setting::put(GoalProgress::GOAL_KEY, 400000);

        $scenario->run();
    }
}
