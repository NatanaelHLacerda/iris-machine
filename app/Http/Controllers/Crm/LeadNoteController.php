<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreLeadNoteRequest;
use App\Models\Lead;
use App\Support\Crm\LeadPipeline;
use Illuminate\Http\RedirectResponse;

class LeadNoteController extends Controller
{
    public function store(StoreLeadNoteRequest $request, Lead $lead, LeadPipeline $pipeline): RedirectResponse
    {
        $pipeline->log($lead, ActivityType::Note, ['text' => $request->validated('text')]);

        return back()->with('status', 'Anotação salva.');
    }
}
