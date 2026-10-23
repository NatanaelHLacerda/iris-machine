<?php

namespace Database\Factories;

use App\Enums\CashCategory;
use App\Enums\CashDirection;
use App\Models\CashEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashEntry>
 */
class CashEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now('America/Sao_Paulo')->toDateString(),
            'direction' => CashDirection::Out,
            'category' => CashCategory::Ferramentas,
            'description' => 'Assinatura do Claude',
            'amount_cents' => 11000,
        ];
    }
}
