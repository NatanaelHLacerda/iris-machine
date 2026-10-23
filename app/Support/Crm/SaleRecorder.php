<?php

namespace App\Support\Crm;

use App\Enums\ActivityType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use App\Models\Lead;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SaleRecorder
{
    public function __construct(private LeadPipeline $pipeline) {}

    /**
     * Registra a venda, cria assinatura e primeira cobrança e fecha o lead, tudo ou nada.
     *
     * @param  array{plan: Plan, amount_cents: int, is_recurring: bool, billing_day?: ?int, sold_at: CarbonImmutable, paid_now?: bool, method?: ?PaymentMethod, notes?: ?string}  $data
     */
    public function record(Lead $lead, array $data): Sale
    {
        return DB::transaction(function () use ($lead, $data) {
            $soldAt = $data['sold_at'];

            $sale = $lead->sales()->create([
                'plan' => $data['plan'],
                'amount_cents' => $data['amount_cents'],
                'is_recurring' => $data['is_recurring'],
                'sold_at' => $soldAt,
                'channel' => $lead->channel,
                'utm_campaign' => $lead->utm_campaign,
                'utm_content' => $lead->utm_content,
                'utm_term' => $lead->utm_term,
                'notes' => $data['notes'] ?? null,
            ]);

            $subscription = null;

            if ($data['is_recurring']) {
                $subscription = $sale->subscription()->create([
                    'lead_id' => $lead->id,
                    'plan' => $data['plan'],
                    'monthly_amount_cents' => $data['amount_cents'],
                    'billing_day' => $data['billing_day'] ?? min($soldAt->setTimezone(Period::TIMEZONE)->day, 28),
                    'status' => SubscriptionStatus::Active,
                    'started_at' => $soldAt,
                ]);
            }

            $paidNow = $data['paid_now'] ?? false;

            $sale->payments()->create([
                'subscription_id' => $subscription?->id,
                'lead_id' => $lead->id,
                'amount_cents' => $data['amount_cents'],
                'due_date' => $soldAt->setTimezone(Period::TIMEZONE)->toDateString(),
                'status' => $paidNow ? PaymentStatus::Paid : PaymentStatus::Pending,
                'paid_at' => $paidNow ? $soldAt : null,
                'method' => $paidNow ? ($data['method'] ?? PaymentMethod::Pix) : null,
            ]);

            $this->pipeline->close($lead);
            $this->pipeline->log($lead, ActivityType::SaleRecorded, [
                'sale_id' => $sale->id,
                'plan' => $data['plan']->value,
                'amount_cents' => $data['amount_cents'],
                'description' => $data['plan']->label().' · '.Money::format($data['amount_cents']).($data['is_recurring'] ? '/mês' : ''),
            ]);

            return $sale;
        });
    }
}
