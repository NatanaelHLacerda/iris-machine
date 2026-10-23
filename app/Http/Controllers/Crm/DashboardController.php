<?php

namespace App\Http\Controllers\Crm;

use App\Enums\Channel;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Support\Crm\GrowthDashboard;
use App\Support\Crm\Period;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const array TABS = ['funil', 'aquisicao', 'receita'];

    public function __invoke(Request $request, GrowthDashboard $dashboard, ?string $tab = null): View|RedirectResponse
    {
        if ($tab !== null && ! in_array($tab, self::TABS, true)) {
            return redirect()->route('crm.dashboard');
        }

        $period = Period::fromRequest($request);

        return match ($tab) {
            'funil' => view('crm.dashboard.funnel', [
                'period' => $period,
                'filters' => $filters = array_filter($request->only('channel', 'campaign'), 'is_string'),
                'steps' => $dashboard->funnel($period, $filters),
                'channels' => Channel::cases(),
                'campaigns' => Lead::whereNotNull('utm_campaign')->distinct()->orderBy('utm_campaign')->pluck('utm_campaign'),
            ]),
            'aquisicao' => view('crm.dashboard.acquisition', ['period' => $period, ...$dashboard->acquisition($period)]),
            'receita' => view('crm.dashboard.revenue', $dashboard->revenue()),
            default => view('crm.dashboard.overview', ['period' => $period, ...$dashboard->overview($period)]),
        };
    }
}
