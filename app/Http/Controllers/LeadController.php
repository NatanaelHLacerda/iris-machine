<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): RedirectResponse|JsonResponse
    {
        Lead::updateOrCreate(
            ['email' => $request->validated('email')],
            $request->safe()->except(['email', 'site']),
        );

        $message = 'Recebemos seu contato! Em breve a gente te chama para marcar a conversa.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()->to(route('home').'#agendar')->with('lead_ok', $message);
    }
}
