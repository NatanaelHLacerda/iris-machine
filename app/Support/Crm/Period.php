<?php

namespace App\Support\Crm;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

/**
 * Períodos do CRM, sempre no fuso de São Paulo e convertidos para UTC nas consultas.
 */
class Period
{
    public const string TIMEZONE = 'America/Sao_Paulo';

    public const array PRESETS = [
        'mes' => 'Este mês',
        'mes-anterior' => 'Mês anterior',
        '7d' => 'Últimos 7 dias',
        '30d' => 'Últimos 30 dias',
        '90d' => 'Últimos 90 dias',
    ];

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE);
    }

    public static function fromRequest(Request $request, string $default = 'mes'): CarbonPeriod
    {
        $preset = (string) $request->query('periodo', $default);

        if ($preset === 'custom') {
            try {
                $from = CarbonImmutable::createFromFormat('Y-m-d', (string) $request->query('de'), self::TIMEZONE)->startOfDay();
                $to = CarbonImmutable::createFromFormat('Y-m-d', (string) $request->query('ate'), self::TIMEZONE)->endOfDay();

                if ($from->lte($to)) {
                    return CarbonPeriod::create($from, $to);
                }
            } catch (\Throwable) {
                // datas inválidas caem no padrão
            }
        }

        return self::preset(array_key_exists($preset, self::PRESETS) ? $preset : $default);
    }

    public static function preset(string $preset): CarbonPeriod
    {
        $today = self::today();

        return match ($preset) {
            'mes-anterior' => CarbonPeriod::create($today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()),
            '7d' => CarbonPeriod::create($today->subDays(6)->startOfDay(), $today->endOfDay()),
            '30d' => CarbonPeriod::create($today->subDays(29)->startOfDay(), $today->endOfDay()),
            '90d' => CarbonPeriod::create($today->subDays(89)->startOfDay(), $today->endOfDay()),
            default => CarbonPeriod::create($today->startOfMonth(), $today->endOfMonth()),
        };
    }

    public static function month(CarbonImmutable $month): CarbonPeriod
    {
        $month = $month->setTimezone(self::TIMEZONE);

        return CarbonPeriod::create($month->startOfMonth(), $month->endOfMonth());
    }

    /**
     * Mesmo tamanho, imediatamente antes.
     */
    public static function previous(CarbonPeriod $period): CarbonPeriod
    {
        $start = CarbonImmutable::instance($period->getStartDate());
        $end = CarbonImmutable::instance($period->getEndDate());

        if ($start->day === 1 && $end->isSameDay($end->endOfMonth()) && $start->isSameMonth($end)) {
            $previous = $start->subMonthNoOverflow();

            return CarbonPeriod::create($previous->startOfMonth(), $previous->endOfMonth());
        }

        $days = (int) $start->diffInDays($end->startOfDay()) + 1;

        return CarbonPeriod::create($start->subDays($days), $start->subSecond());
    }

    /**
     * Limites em UTC para usar em where('created_at', ...).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function bounds(CarbonPeriod $period): array
    {
        return [
            CarbonImmutable::instance($period->getStartDate())->utc(),
            CarbonImmutable::instance($period->getEndDate())->utc(),
        ];
    }

    public static function label(CarbonPeriod $period): string
    {
        $start = CarbonImmutable::instance($period->getStartDate());
        $end = CarbonImmutable::instance($period->getEndDate());

        return $start->format('d/m/Y').' – '.$end->format('d/m/Y');
    }
}
