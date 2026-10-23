<?php

namespace App\Support\Crm;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Carbon\CarbonImmutable;

/**
 * Cria a cobrança do mês de cada assinatura ativa. Idempotente: rodar de novo não duplica.
 */
class ChargeGenerator
{
    public function run(CarbonImmutable $today): int
    {
        $today = $today->setTimezone(Period::TIMEZONE);
        $created = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('billing_day', '<=', $today->day)
            ->where('started_at', '<', $today->startOfMonth()->utc())
            ->with('sale')
            ->each(function (Subscription $subscription) use ($today, &$created) {
                $dueDate = $today->setDay($subscription->billing_day)->toDateString();

                $alreadyCharged = $subscription->payments()
                    ->whereBetween('due_date', [$today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString().' 23:59:59'])
                    ->exists();

                if ($alreadyCharged) {
                    return;
                }

                $subscription->payments()->create([
                    'sale_id' => $subscription->sale_id,
                    'lead_id' => $subscription->lead_id,
                    'amount_cents' => $subscription->monthly_amount_cents,
                    'due_date' => $dueDate,
                    'status' => PaymentStatus::Pending,
                ]);

                $created++;
            });

        return $created;
    }
}
