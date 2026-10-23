<?php

namespace Tests\Unit\Crm;

use App\Support\Crm\Period;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use PHPUnit\Framework\TestCase;

class PeriodTest extends TestCase
{
    public function test_previous_of_a_full_month_is_the_previous_month(): void
    {
        $october = CarbonPeriod::create(
            CarbonImmutable::parse('2026-10-01', 'America/Sao_Paulo'),
            CarbonImmutable::parse('2026-10-31 23:59:59', 'America/Sao_Paulo'),
        );

        $previous = Period::previous($october);

        $this->assertSame('2026-09-01', $previous->getStartDate()->toDateString());
        $this->assertSame('2026-09-30', $previous->getEndDate()->toDateString());
    }

    public function test_previous_of_a_rolling_window_has_the_same_length(): void
    {
        $week = CarbonPeriod::create(
            CarbonImmutable::parse('2026-10-17', 'America/Sao_Paulo'),
            CarbonImmutable::parse('2026-10-23 23:59:59', 'America/Sao_Paulo'),
        );

        $previous = Period::previous($week);

        $this->assertSame('2026-10-10', $previous->getStartDate()->toDateString());
        $this->assertSame('2026-10-16', $previous->getEndDate()->toDateString());
    }
}
