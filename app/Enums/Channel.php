<?php

namespace App\Enums;

enum Channel: string
{
    case Meta = 'meta';
    case Google = 'google';
    case Organico = 'organico';
    case Direto = 'direto';
    case Indicacao = 'indicacao';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Meta => 'Meta Ads',
            self::Google => 'Google',
            self::Organico => 'Orgânico',
            self::Direto => 'Direto',
            self::Indicacao => 'Indicação',
            self::Outro => 'Outro',
        };
    }
}
