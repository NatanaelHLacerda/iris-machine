<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\UpdateOpeningBalanceRequest;
use App\Models\Setting;
use App\Support\Crm\CashFlow;
use Illuminate\Http\RedirectResponse;

class OpeningBalanceController extends Controller
{
    public function update(UpdateOpeningBalanceRequest $request): RedirectResponse
    {
        Setting::put(CashFlow::OPENING_BALANCE_KEY, [
            'amount_cents' => (int) $request->validated('amount_cents'),
            'date' => $request->validated('date'),
        ]);

        return back()->with('status', 'Saldo inicial definido.');
    }
}
