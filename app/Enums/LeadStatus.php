<?php

namespace App\Enums;

/**
 * Etapas do pipeline, na ordem em que aparecem no kanban.
 */
enum LeadStatus: string
{
    case Novo = 'novo';
    case ContatoFeito = 'contato_feito';
    case ConversaAgendada = 'conversa_agendada';
    case DiagnosticoFeito = 'diagnostico_feito';
    case Proposta = 'proposta';
    case Fechado = 'fechado';
    case Perdido = 'perdido';

    public function label(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::ContatoFeito => 'Contato feito',
            self::ConversaAgendada => 'Conversa agendada',
            self::DiagnosticoFeito => 'Diagnóstico feito',
            self::Proposta => 'Proposta',
            self::Fechado => 'Fechado',
            self::Perdido => 'Perdido',
        };
    }

    public function order(): int
    {
        return array_search($this, self::cases(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Fechado, self::Perdido], true);
    }

    /**
     * Tom do badge; o texto sempre acompanha a cor.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Novo => 'pink',
            self::Fechado => 'ok',
            self::Perdido => 'danger',
            self::Proposta => 'warn',
            default => 'info',
        };
    }
}
