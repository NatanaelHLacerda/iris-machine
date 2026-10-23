<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Support\Crm\AdMetrics;
use App\Support\Crm\Period;
use App\Support\Crm\RevenueMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    public const string UTM_TEMPLATE = 'utm_source=meta&utm_medium=paid&utm_campaign={{campaign.name}}&utm_content={{adset.name}}&utm_term={{ad.name}}';

    public function __invoke(Request $request, AdMetrics $metrics, RevenueMetrics $revenue): View
    {
        $period = Period::fromRequest($request);
        $campaigns = $metrics->byCampaign($period);

        return view('crm.ads.index', [
            'period' => $period,
            'summary' => $metrics->summary($period),
            'campaigns' => $campaigns,
            'adsets' => $campaigns->mapWithKeys(fn (array $campaign) => [$campaign['key'] => $metrics->byAdset($period, $campaign['key'])]),
            'daily' => $metrics->dailySpend($period),
            'outsideMeta' => $metrics->leadsOutsideMeta($period),
            'ltv' => $revenue->ltvCents(),
            'utmTemplate' => self::UTM_TEMPLATE,
        ]);
    }
}
