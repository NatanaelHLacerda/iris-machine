<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreSaleRequest;
use App\Models\Lead;
use App\Support\Crm\SaleRecorder;
use Illuminate\Http\RedirectResponse;

class LeadSaleController extends Controller
{
    public function store(StoreSaleRequest $request, Lead $lead, SaleRecorder $recorder): RedirectResponse
    {
        $sale = $recorder->record($lead, $request->saleData());

        return redirect()->route('crm.leads.show', $lead)
            ->with('status', "Venda de {$sale->plan->label()} registrada. Lead fechado.");
    }
}
