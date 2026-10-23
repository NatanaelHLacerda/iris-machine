<?php

namespace App\Models;

use App\Enums\CashCategory;
use App\Enums\CashDirection;
use Database\Factories\CashEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Lançamento manual do caixa. Pagamentos e gasto de anúncio entram por consulta, nunca copiados para cá.
 */
#[Fillable(['date', 'direction', 'category', 'description', 'amount_cents'])]
class CashEntry extends Model
{
    /** @use HasFactory<CashEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => CashDirection::class,
            'category' => CashCategory::class,
        ];
    }

    public function signedAmount(): int
    {
        return $this->direction === CashDirection::In ? $this->amount_cents : -$this->amount_cents;
    }
}
