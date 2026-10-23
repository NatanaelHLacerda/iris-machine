<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreAdSpendRequest;
use App\Models\AdSpend;
use App\Support\Crm\UtmKey;
use Illuminate\Http\RedirectResponse;

class AdSpendController extends Controller
{
    public function store(StoreAdSpendRequest $request): RedirectResponse
    {
        $data = $request->validated();

        AdSpend::updateOrCreate(
            [
                'platform' => 'meta',
                'date' => $data['date'],
                'campaign_key' => UtmKey::normalize($data['campaign']),
                'adset_key' => UtmKey::normalize($data['adset'] ?? null) ?? '',
                'ad_key' => UtmKey::normalize($data['ad'] ?? null) ?? '',
            ],
            [
                'campaign' => $data['campaign'],
                'adset' => $data['adset'] ?? null,
                'ad' => $data['ad'] ?? null,
                'spend_cents' => $data['spend_cents'],
                'impressions' => $data['impressions'] ?? null,
                'link_clicks' => $data['link_clicks'] ?? null,
                'source' => 'manual',
            ],
        );

        return back()->with('status', 'Gasto lançado.');
    }
}
