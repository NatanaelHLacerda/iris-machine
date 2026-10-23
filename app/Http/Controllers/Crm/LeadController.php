<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ActivityType;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreLeadRequest;
use App\Models\Lead;
use App\Models\TrackingEvent;
use App\Support\Crm\LeadFilters;
use App\Support\Crm\LeadPipeline;
use App\Support\Crm\LeadTimeline;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $filters = LeadFilters::fromRequest($request);
        $query = LeadFilters::apply(Lead::query(), $filters);

        $summary = (clone $query)->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status not in (?, ?) then 1 else 0 end) as open', [LeadStatus::Fechado->value, LeadStatus::Perdido->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as closed', [LeadStatus::Fechado->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as lost', [LeadStatus::Perdido->value])
            ->selectRaw('sum(case when utm_campaign is not null then 1 else 0 end) as with_utm')
            ->first();

        return view('crm.leads.index', [
            'leads' => $query->latest()->paginate(50)->withQueryString(),
            'filters' => $filters,
            'summary' => $summary,
            'campaigns' => Lead::query()->whereNotNull('utm_campaign')->distinct()->orderBy('utm_campaign')->pluck('utm_campaign'),
        ]);
    }

    public function create(): View
    {
        return view('crm.leads.create');
    }

    public function store(StoreLeadRequest $request, LeadPipeline $pipeline): RedirectResponse
    {
        $lead = Lead::create([
            ...$request->validated(),
            'status' => LeadStatus::Novo,
            'status_changed_at' => now(),
        ]);

        $pipeline->log($lead, ActivityType::Created, ['manual' => true]);

        return redirect()->route('crm.leads.show', $lead)->with('status', 'Lead cadastrado.');
    }

    public function show(Lead $lead): View
    {
        $lead->load(['submissions', 'activities.user', 'sales.subscription', 'sales.payments', 'payments']);

        return view('crm.leads.show', [
            'lead' => $lead,
            'timeline' => LeadTimeline::for($lead),
        ]);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        DB::transaction(function () use ($lead) {
            if ($lead->visitor_id) {
                TrackingEvent::where('visitor_id', $lead->visitor_id)->delete();
            }

            $lead->delete();
        });

        return redirect()->route('crm.leads.index')->with('status', 'Lead e dados ligados a ele foram apagados.');
    }
}
