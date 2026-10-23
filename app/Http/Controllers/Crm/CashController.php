<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Support\Crm\CashFlow;
use App\Support\Crm\Period;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CashController extends Controller
{
    private const array ORIGINS = ['payment' => 'Pagamentos', 'refund' => 'Estornos', 'ad_spend' => 'Anúncios', 'manual' => 'Manuais'];

    public function __invoke(Request $request, CashFlow $cash): View
    {
        $period = Period::fromRequest($request);
        $origin = array_key_exists((string) $request->query('origem'), self::ORIGINS) ? $request->query('origem') : null;

        return view('crm.cash.index', [
            'period' => $period,
            'opening' => $cash->openingBalance(),
            'balance' => $cash->balance(),
            'summary' => $cash->summary($period),
            'statement' => $cash->statement($period)->when($origin, fn ($lines) => $lines->where('origin', $origin)->values()),
            'series' => $cash->balanceSeries($period),
            'origins' => self::ORIGINS,
            'origin' => $origin,
        ]);
    }
}
