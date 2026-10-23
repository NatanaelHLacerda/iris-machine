<?php

namespace App\Support\Crm;

use App\Enums\CashCategory;
use App\Enums\PaymentStatus;
use App\Models\AdSpend;
use App\Models\CashEntry;
use App\Models\Payment;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Livro-caixa composto por consulta: pagamentos recebidos, estornos, gasto no Meta e lançamentos manuais.
 */
class CashFlow
{
    public const string OPENING_BALANCE_KEY = 'opening_balance';

    /**
     * @return array{amount_cents: int, date: CarbonImmutable}|null
     */
    public function openingBalance(): ?array
    {
        $opening = Setting::get(self::OPENING_BALANCE_KEY);

        if (! is_array($opening) || ! isset($opening['amount_cents'], $opening['date'])) {
            return null;
        }

        return [
            'amount_cents' => (int) $opening['amount_cents'],
            'date' => CarbonImmutable::parse($opening['date'], Period::TIMEZONE)->startOfDay(),
        ];
    }

    /**
     * Saldo em conta hoje; null sem saldo inicial (não se inventa um zero).
     */
    public function balance(): ?int
    {
        $opening = $this->openingBalance();

        if ($opening === null) {
            return null;
        }

        return $opening['amount_cents'] + (int) $this->lines($opening['date'], Period::today()->endOfDay())->sum('amount_cents');
    }

    /**
     * @return Collection<int, array{date: string, origin: string, category: CashCategory, description: string, amount_cents: int, editable: bool, link: ?string, entry: ?CashEntry}>
     */
    public function statement(CarbonPeriod $period): Collection
    {
        [$from, $to] = $this->clampToOpening($period);

        return $this->lines($from, $to)->sortByDesc('date')->values();
    }

    /**
     * @return array{in: int, out: int, result: int, margin: ?float, out_by_category: array<string, int>}
     */
    public function summary(CarbonPeriod $period): array
    {
        [$from, $to] = $this->clampToOpening($period);
        $lines = $this->lines($from, $to);

        $in = (int) $lines->where('amount_cents', '>', 0)->sum('amount_cents');
        $out = (int) -$lines->where('amount_cents', '<', 0)->sum('amount_cents');

        return [
            'in' => $in,
            'out' => $out,
            'result' => $in - $out,
            'margin' => $in > 0 ? ($in - $out) / $in : null,
            'out_by_category' => $lines->where('amount_cents', '<', 0)
                ->groupBy(fn (array $line) => $line['category']->label())
                ->map(fn (Collection $group) => (int) -$group->sum('amount_cents'))
                ->sortDesc()
                ->all(),
        ];
    }

    public function monthSummary(CarbonImmutable $month): array
    {
        return $this->summary(Period::month($month));
    }

    /**
     * Saldo no fim de cada dia do período.
     *
     * @return array<string, int>|null
     */
    public function balanceSeries(CarbonPeriod $period): ?array
    {
        $opening = $this->openingBalance();

        if ($opening === null) {
            return null;
        }

        $start = CarbonImmutable::instance($period->getStartDate());
        $end = CarbonImmutable::instance($period->getEndDate())->min(Period::today()->endOfDay());
        $byDay = $this->lines($opening['date'], $end)->groupBy('date')->map(fn (Collection $group) => (int) $group->sum('amount_cents'));

        $running = $opening['amount_cents'];
        $series = [];

        foreach (CarbonPeriod::create($opening['date'], '1 day', $end) as $day) {
            $running += $byDay->get($day->toDateString(), 0);

            if ($day->gte($start->startOfDay())) {
                $series[$day->toDateString()] = $running;
            }
        }

        return $series;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function clampToOpening(CarbonPeriod $period): array
    {
        $from = CarbonImmutable::instance($period->getStartDate());
        $opening = $this->openingBalance();

        if ($opening !== null && $opening['date']->gt($from)) {
            $from = $opening['date'];
        }

        return [$from, CarbonImmutable::instance($period->getEndDate())];
    }

    /**
     * @return Collection<int, array{date: string, origin: string, category: CashCategory, description: string, amount_cents: int, editable: bool, link: ?string, entry: ?CashEntry}>
     */
    private function lines(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $bounds = [$from->utc(), $to->utc()];
        $fromDate = $from->setTimezone(Period::TIMEZONE)->toDateString();
        $toDate = $to->setTimezone(Period::TIMEZONE)->toDateString();
        $localDate = fn ($moment) => $moment->copy()->setTimezone(Period::TIMEZONE)->toDateString();

        $received = Payment::with(['lead:id,name', 'sale:id,plan'])
            ->whereIn('status', [PaymentStatus::Paid, PaymentStatus::Refunded])
            ->whereBetween('paid_at', $bounds)
            ->get()
            ->map(fn (Payment $payment) => $this->line(
                $localDate($payment->paid_at), 'payment', CashCategory::Alunos,
                "{$payment->lead->name} · {$payment->sale->plan->label()}", $payment->amount_cents,
                link: route('crm.leads.show', $payment->lead_id),
            ));

        $refunds = Payment::with('lead:id,name')
            ->where('status', PaymentStatus::Refunded)
            ->whereBetween('refunded_at', $bounds)
            ->get()
            ->map(fn (Payment $payment) => $this->line(
                $localDate($payment->refunded_at), 'refund', CashCategory::Alunos,
                "Estorno · {$payment->lead->name}", -$payment->amount_cents,
                link: route('crm.leads.show', $payment->lead_id),
            ));

        $ads = AdSpend::query()
            ->whereBetween('date', [$fromDate, $toDate])
            ->selectRaw('date, sum(spend_cents) as total')
            ->groupBy('date')
            ->get()
            ->filter(fn ($row) => (int) $row->total > 0)
            ->map(fn ($row) => $this->line(
                $row->date, 'ad_spend', CashCategory::Anuncios, 'Anúncios Meta', -(int) $row->total,
                link: route('crm.ads', ['periodo' => 'custom', 'de' => $row->date, 'ate' => $row->date]),
            ));

        $manual = CashEntry::whereBetween('date', [$fromDate, $toDate])
            ->get()
            ->map(fn (CashEntry $entry) => $this->line(
                $entry->date, 'manual', $entry->category, $entry->description, $entry->signedAmount(), entry: $entry,
            ));

        return $received->concat($refunds)->concat($ads)->concat($manual)->values();
    }

    /**
     * @return array{date: string, origin: string, category: CashCategory, description: string, amount_cents: int, editable: bool, link: ?string, entry: ?CashEntry}
     */
    private function line(string $date, string $origin, CashCategory $category, string $description, int $amount, ?string $link = null, ?CashEntry $entry = null): array
    {
        return [
            'date' => $date,
            'origin' => $origin,
            'category' => $category,
            'description' => $description,
            'amount_cents' => $amount,
            'editable' => $origin === 'manual',
            'link' => $link,
            'entry' => $entry,
        ];
    }
}
