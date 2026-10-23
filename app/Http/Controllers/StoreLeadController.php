<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Support\Crm\ChannelClassifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StoreLeadController extends Controller
{
    private const string SUCCESS_MESSAGE = 'Recebemos seu contato! Vamos te chamar em breve para combinar a conversa.';

    public function __invoke(StoreLeadRequest $request): JsonResponse|RedirectResponse
    {
        if (! $request->isSpam()) {
            $this->store($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => self::SUCCESS_MESSAGE], 201);
        }

        return redirect(route('home').'#agendar')->with('lead_sent', self::SUCCESS_MESSAGE);
    }

    private function store(StoreLeadRequest $request): void
    {
        $data = $request->validated();
        $attribution = array_map(fn (?string $value) => $value ?: null, $request->safe()->only(Lead::ATTRIBUTION_FIELDS));
        $contact = array_filter([
            'name' => $data['name'],
            'whatsapp' => $data['whatsapp'] ?? null,
            'level' => $data['level'],
            'plan_interest' => $data['plan'] ?? null,
            'goal' => $data['goal'] ?? null,
        ], fn ($value) => filled($value));

        DB::transaction(function () use ($data, $attribution, $contact, $request) {
            $lead = Lead::firstOrNew(['email' => $data['email']]);

            if ($lead->exists) {
                $lead->fill($contact);
            } else {
                $lead->fill([
                    ...$contact,
                    ...$attribution,
                    'status' => LeadStatus::Novo,
                    'channel' => ChannelClassifier::classify($attribution['utm_source'] ?? null, $attribution['fbclid'] ?? null, $attribution['referrer'] ?? null),
                    'visitor_id' => $data['visitor_id'] ?? null,
                ]);
            }

            $lead->last_submitted_at = now();
            $lead->save();

            $lead->submissions()->create([
                ...array_diff_key($attribution, ['fbclid' => true]),
                'payload' => $request->safe()->only(['name', 'email', 'whatsapp', 'level', 'plan', 'goal']),
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ]);
        });
    }
}
