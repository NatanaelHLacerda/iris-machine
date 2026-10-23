<?php

namespace App\Http\Controllers\Crm;

use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Support\Crm\LeadPipeline;
use App\Support\Crm\Period;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

class LeadStatusController extends Controller
{
    public function update(UpdateLeadStatusRequest $request, Lead $lead, LeadPipeline $pipeline): JsonResponse|RedirectResponse
    {
        try {
            $pipeline->move(
                $lead,
                LeadStatus::from($request->validated('status')),
                LostReason::tryFrom((string) $request->validated('lost_reason')),
                $request->validated('note'),
                $request->filled('meeting_at') ? Date::parse($request->validated('meeting_at'), Period::TIMEZONE)->utc() : null,
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => $lead->status->value, 'label' => $lead->status->label()]);
        }

        return back()->with('status', "Etapa alterada para {$lead->status->label()}.");
    }
}
