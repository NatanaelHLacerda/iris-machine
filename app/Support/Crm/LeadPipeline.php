<?php

namespace App\Support\Crm;

use App\Enums\ActivityType;
use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Models\Lead;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Única porta de entrada para mudar a etapa de um lead: valida a regra e grava o histórico.
 */
class LeadPipeline
{
    public function move(
        Lead $lead,
        LeadStatus $to,
        ?LostReason $reason = null,
        ?string $note = null,
        ?CarbonInterface $meetingAt = null,
    ): Lead {
        if ($to === LeadStatus::Fechado) {
            throw new DomainException('Para fechar, registre a venda do lead.');
        }

        if ($to === LeadStatus::Perdido && $reason === null) {
            throw new DomainException('Informe o motivo da perda.');
        }

        return DB::transaction(function () use ($lead, $to, $reason, $note, $meetingAt) {
            $from = $lead->status;

            $lead->fill([
                'status' => $to,
                'status_changed_at' => now(),
                'lost_reason' => $to === LeadStatus::Perdido ? $reason : null,
                'lost_note' => $to === LeadStatus::Perdido ? $note : null,
            ]);

            if ($to === LeadStatus::ConversaAgendada && $meetingAt !== null) {
                $lead->meeting_at = $meetingAt;
            }

            $lead->save();

            $this->log($lead, ActivityType::StatusChanged, array_filter([
                'from' => $from->value,
                'to' => $to->value,
                'reason' => $reason?->value,
                'note' => $note,
                'meeting_at' => $meetingAt?->toIso8601String(),
            ]));

            return $lead;
        });
    }

    /**
     * Fecha o lead; chamado só pelo registro de venda.
     */
    public function close(Lead $lead): void
    {
        $from = $lead->status;

        $lead->update([
            'status' => LeadStatus::Fechado,
            'status_changed_at' => now(),
            'lost_reason' => null,
            'lost_note' => null,
            'next_action' => null,
            'next_action_at' => null,
        ]);

        if ($from !== LeadStatus::Fechado) {
            $this->log($lead, ActivityType::StatusChanged, ['from' => $from->value, 'to' => LeadStatus::Fechado->value]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function log(Lead $lead, ActivityType $type, array $data = []): void
    {
        $lead->activities()->create([
            'type' => $type,
            'data' => $data,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }
}
