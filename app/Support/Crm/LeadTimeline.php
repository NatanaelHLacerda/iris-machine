<?php

namespace App\Support\Crm;

use App\Enums\ActivityType;
use App\Enums\EventType;
use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSubmission;
use App\Models\TrackingEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Junta histórico, envios do formulário e eventos da landing numa linha do tempo única.
 */
class LeadTimeline
{
    /**
     * @return Collection<int, array{at: CarbonInterface, kind: string, text: string, note: ?string}>
     */
    public static function for(Lead $lead): Collection
    {
        $activities = $lead->activities->map(fn (LeadActivity $activity) => [
            'at' => $activity->created_at,
            'kind' => $activity->type === ActivityType::SaleRecorded ? 'sale' : 'activity',
            'text' => self::describe($activity),
            'note' => $activity->data['text'] ?? $activity->data['note'] ?? null,
        ]);

        $submissions = $lead->submissions->map(fn (LeadSubmission $submission) => [
            'at' => $submission->created_at,
            'kind' => 'activity',
            'text' => 'Enviou o formulário da landing',
            'note' => $submission->payload['goal'] ?? null,
        ]);

        $events = $lead->visitor_id
            ? TrackingEvent::where('visitor_id', $lead->visitor_id)
                ->whereIn('type', [EventType::PageView, EventType::CtaClick])
                ->latest('created_at')->limit(30)->get()
                ->map(fn (TrackingEvent $event) => [
                    'at' => $event->created_at,
                    'kind' => 'event',
                    'text' => $event->type->label().($event->target ? " ({$event->target})" : ''),
                    'note' => null,
                ])
            : collect();

        return $activities->concat($submissions)->concat($events)->sortByDesc('at')->values();
    }

    private static function describe(LeadActivity $activity): string
    {
        $data = $activity->data ?? [];

        return match ($activity->type) {
            ActivityType::Created => 'Lead cadastrado manualmente',
            ActivityType::Note => 'Anotação',
            ActivityType::NextActionSet => isset($data['next_action'])
                ? 'Próxima ação: '.$data['next_action']
                : 'Próxima ação removida',
            ActivityType::SaleRecorded => 'Venda registrada: '.($data['description'] ?? ''),
            ActivityType::StatusChanged => sprintf(
                '%s → %s%s',
                LeadStatus::from($data['from'])->label(),
                LeadStatus::from($data['to'])->label(),
                isset($data['reason']) ? ' · '.LostReason::from($data['reason'])->label() : '',
            ),
        };
    }
}
