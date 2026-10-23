<?php

namespace App\Support\Crm;

/**
 * Normaliza nomes de campanha/conjunto/anúncio para cruzar UTM com o gasto do Meta.
 */
class UtmKey
{
    public static function normalize(?string $value): ?string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));

        return $normalized === '' ? null : $normalized;
    }
}
