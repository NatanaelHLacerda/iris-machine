<?php

namespace App\Enums;

/**
 * Projeção do mês contra a meta (DP-0801): no ritmo ≥ 100%, atenção 80–99%, fora < 80%.
 */
enum GoalStatus: string
{
    case OnTrack = 'on_track';
    case Attention = 'attention';
    case OffTrack = 'off_track';
    case Reached = 'reached';

    public static function fromRatio(float $projectionOverGoal, bool $reached): self
    {
        return match (true) {
            $reached => self::Reached,
            $projectionOverGoal >= 1 => self::OnTrack,
            $projectionOverGoal >= 0.8 => self::Attention,
            default => self::OffTrack,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Reached => 'Meta batida',
            self::OnTrack => 'No ritmo',
            self::Attention => 'Atenção',
            self::OffTrack => 'Fora do ritmo',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Reached, self::OnTrack => 'ok',
            self::Attention => 'warn',
            self::OffTrack => 'danger',
        };
    }
}
