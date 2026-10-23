<?php

namespace App\Support\Crm;

use App\Enums\GoalStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Setting;
use Carbon\CarbonImmutable;

class GoalProgress
{
    public const string GOAL_KEY = 'monthly_goal_cents';

    public function __construct(private RevenueMetrics $revenue) {}

    public static function goalCents(): int
    {
        return (int) Setting::get(self::GOAL_KEY, config('iris.crm.monthly_goal_cents'));
    }

    /**
     * @return array{goal: int, revenue: int, percent: float, remaining: int, daily_needed: int, projection: int, pending: int, days_left: int, expected_percent: float, status: GoalStatus}
     */
    public function for(CarbonImmutable $month, CarbonImmutable $today): array
    {
        $today = $today->setTimezone(Period::TIMEZONE);
        $month = $month->setTimezone(Period::TIMEZONE)->startOfMonth();
        $goal = self::goalCents();
        $revenue = $this->revenue->revenueForMonth($month);
        $isCurrent = $month->isSameMonth($today);
        $daysInMonth = $month->daysInMonth;
        $daysLeft = $isCurrent ? $daysInMonth - $today->day + 1 : 0;

        $pending = $isCurrent ? (int) Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->whereBetween('due_date', [$month->toDateString(), $month->endOfMonth()->toDateString().' 23:59:59'])
            ->sum('amount_cents') : 0;

        $projection = $revenue + $pending + ($isCurrent ? $this->averageDailyOneOff($today) * ($daysLeft - 1) : 0);
        $remaining = max(0, $goal - $revenue);

        return [
            'goal' => $goal,
            'revenue' => $revenue,
            'percent' => $goal > 0 ? $revenue / $goal : 0.0,
            'remaining' => $remaining,
            'daily_needed' => $daysLeft > 0 ? intdiv($remaining, $daysLeft) : 0,
            'projection' => $projection,
            'pending' => $pending,
            'days_left' => $daysLeft,
            'expected_percent' => $isCurrent ? $today->day / $daysInMonth : 1.0,
            'status' => GoalStatus::fromRatio($goal > 0 ? $projection / $goal : 1, $revenue >= $goal),
        ];
    }

    /**
     * Média diária de vendas avulsas recebidas nos últimos 30 dias (mensalidades já entram como cobrança prevista).
     */
    private function averageDailyOneOff(CarbonImmutable $today): int
    {
        $total = (int) Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereNull('subscription_id')
            ->whereBetween('paid_at', [$today->subDays(30)->utc(), $today->utc()])
            ->sum('amount_cents');

        return intdiv($total, 30);
    }
}
