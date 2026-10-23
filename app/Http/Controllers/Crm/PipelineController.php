<?php

namespace App\Http\Controllers\Crm;

use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    /**
     * Fechado e Perdido acumulam; no board ficam só os dos últimos 30 dias.
     */
    private const int CLOSED_WINDOW_DAYS = 30;

    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $leads = Lead::query()
            ->where(fn ($query) => $query
                ->whereNotIn('status', [LeadStatus::Fechado, LeadStatus::Perdido])
                ->orWhere('status_changed_at', '>=', now()->subDays(self::CLOSED_WINDOW_DAYS)))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderByRaw('next_action_at is null')
            ->orderBy('next_action_at')
            ->latest()
            ->get()
            ->groupBy(fn (Lead $lead) => $lead->status->value);

        return view('crm.pipeline.index', [
            'columns' => collect(LeadStatus::cases())->map(fn (LeadStatus $status) => [
                'status' => $status,
                'leads' => $leads->get($status->value, collect()),
            ]),
            'total' => $leads->flatten()->count(),
            'lostReasons' => LostReason::cases(),
            'search' => $search,
        ]);
    }
}
