<?php

namespace App\Models;

use Database\Factories\AdSpendFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Gasto diário no Meta por campanha/conjunto/anúncio. `date` fica como texto Y-m-d (dia do Gerenciador, sem fuso).
 */
#[Fillable([
    'date', 'platform', 'campaign', 'adset', 'ad', 'campaign_key', 'adset_key', 'ad_key',
    'spend_cents', 'impressions', 'link_clicks', 'source',
])]
class AdSpend extends Model
{
    /** @use HasFactory<AdSpendFactory> */
    use HasFactory;
}
