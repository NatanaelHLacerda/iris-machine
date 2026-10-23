<?php

namespace App\Enums;

enum CashDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Entrada',
            self::Out => 'Saída',
        };
    }
}
