<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\UpdateNextActionRequest;
use App\Models\Lead;
use App\Support\Crm\LeadPipeline;
use App\Support\Crm\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class LeadNextActionController extends Controller
{
    public function update(UpdateNextActionRequest $request, Lead $lead, LeadPipeline $pipeline): RedirectResponse
    {
        $lead->update([
            'next_action' => $request->validated('next_action'),
            'next_action_at' => $request->filled('next_action_at')
                ? Carbon::parse($request->validated('next_action_at'), Period::TIMEZONE)->utc()
                : null,
        ]);

        $pipeline->log($lead, ActivityType::NextActionSet, array_filter([
            'next_action' => $lead->next_action,
            'next_action_at' => $lead->next_action_at?->toIso8601String(),
        ]));

        return back()->with('status', $lead->next_action ? 'Próxima ação definida.' : 'Próxima ação removida.');
    }
}
