<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreCashEntryRequest;
use App\Models\CashEntry;
use Illuminate\Http\RedirectResponse;

/**
 * Só lançamentos manuais: pagamentos e anúncios se editam na origem.
 */
class CashEntryController extends Controller
{
    public function store(StoreCashEntryRequest $request): RedirectResponse
    {
        CashEntry::create($request->validated());

        return back()->with('status', 'Lançamento salvo.');
    }

    public function update(StoreCashEntryRequest $request, CashEntry $cashEntry): RedirectResponse
    {
        $cashEntry->update($request->validated());

        return back()->with('status', 'Lançamento atualizado.');
    }

    public function destroy(CashEntry $cashEntry): RedirectResponse
    {
        $cashEntry->delete();

        return back()->with('status', 'Lançamento apagado.');
    }
}
