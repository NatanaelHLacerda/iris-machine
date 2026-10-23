<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Pix = 'pix';
    case Cartao = 'cartao';
    case Boleto = 'boleto';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Cartao => 'Cartão',
            self::Boleto => 'Boleto',
            self::Outro => 'Outro',
        };
    }
}
