<?php

namespace Database\Seeders\Demo;

use App\Enums\CashCategory;
use App\Enums\CashDirection;
use App\Enums\Channel;
use App\Enums\EventType;
use App\Enums\LeadLevel;
use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use App\Models\AdSpend;
use App\Models\CashEntry;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\TrackingEvent;
use App\Support\Crm\ChargeGenerator;
use App\Support\Crm\LeadPipeline;
use App\Support\Crm\Period;
use App\Support\Crm\SaleRecorder;
use App\Support\Crm\UtmKey;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Seis meses fictícios e coerentes da Iris Machine: visitas → eventos → leads → pipeline → vendas → cobranças → caixa.
 * Usa os serviços reais (LeadPipeline, SaleRecorder, ChargeGenerator) com o relógio voltado para cada momento.
 */
class DemoScenario
{
    public const int MONTHS = 6;

    /**
     * [nome, peso nos leads do Meta, peso no gasto, qualidade (multiplica a chance de fechar)]
     */
    private const array CAMPAIGNS = [
        ['vibe-coding-junior', 0.55, 0.35, 1.25, ['estudantes-ti', 'devs-junior'], ['video-terminal', 'carrossel-portfolio']],
        ['portfolio-recrutador', 0.25, 0.25, 1.0, ['recrutamento', 'estagio'], ['depoimento', 'antes-depois']],
        ['claude-code-tcc', 0.03, 0.32, 0.1, ['universitarios'], ['tcc-com-ia']],
        ['remarketing-lp', 0.15, 0.10, 1.4, ['visitantes-30d'], ['oferta-mentoria']],
    ];

    private const array GOALS = [
        'Quero usar agentes no meu TCC sem virar refém da IA',
        'Montar um portfólio que passe na triagem de estágio',
        'Aprender Claude Code para usar no estágio',
        'Entender como revisar o código que a IA gera',
        'Automatizar testes e deploy dos meus projetos',
        'Criar skills para o meu time',
        'Sair do tutorial e construir um projeto real com agentes',
        'Conseguir minha primeira vaga de dev júnior',
        'Usar MCP para conectar a IA às ferramentas do trabalho',
        'Parar de travar quando o projeto cresce',
        null,
    ];

    private readonly CarbonImmutable $now;

    private readonly CarbonImmutable $start;

    /** @var array<int, array<string, mixed>> */
    private array $events = [];

    public function __construct(
        private readonly LeadPipeline $pipeline,
        private readonly SaleRecorder $recorder,
        private readonly ChargeGenerator $charges,
        private readonly float $scale = 1.0,
    ) {
        $this->now = CarbonImmutable::now(Period::TIMEZONE);
        $this->start = $this->now->subMonthsNoOverflow(self::MONTHS - 1)->startOfMonth();
    }

    public function start(): CarbonImmutable
    {
        return $this->start;
    }

    public function run(): void
    {
        try {
            for ($day = $this->start; $day->lte($this->now); $day = $day->addDay()) {
                $this->simulateDay($day);
            }

            $this->flushEvents();
            $this->runBilling();
            $this->shapeToday();
        } finally {
            $this->travel(null);
        }
    }

    private function simulateDay(CarbonImmutable $day): void
    {
        $progress = $this->monthProgress($day);
        $visits = (int) round((15 + 30 * $progress) * $this->scale * (0.8 + mt_rand(0, 40) / 100) * ($day->isWeekend() ? 0.7 : 1));
        $metaShare = 0.55 + 0.15 * $progress;
        $dailySpend = (int) round((600 + 2400 * $progress) * $this->scale);

        foreach (self::CAMPAIGNS as [$campaign, , $spendWeight, , $adsets, $ads]) {
            if ($day->isSameDay($this->now) && $this->now->hour < 12) {
                break;
            }

            AdSpend::create([
                'date' => $day->toDateString(),
                'platform' => 'meta',
                'campaign' => $campaign,
                'campaign_key' => UtmKey::normalize($campaign),
                'adset_key' => '',
                'ad_key' => '',
                'spend_cents' => (int) round($dailySpend * $spendWeight * (0.85 + mt_rand(0, 30) / 100)),
                'impressions' => mt_rand(900, 1600) * (int) ceil($spendWeight * 10),
                'link_clicks' => mt_rand(12, 30) * (int) ceil($spendWeight * 10),
                'source' => 'manual',
            ]);
        }

        for ($i = 0; $i < $visits; $i++) {
            $this->simulateVisit($day, $metaShare);
        }

        if ($day->day === 5) {
            $this->expense($day, CashCategory::Ferramentas, 'Assinatura do Claude', 11000);
        }
        if ($day->day === 10) {
            $this->expense($day, CashCategory::Infraestrutura, 'Hospedagem e banco de dados', 6000);
        }
        if ($day->day === 15) {
            $this->expense($day, CashCategory::Ferramentas, 'Domínio, e-mail e ferramentas', 4500);
        }
    }

    private function simulateVisit(CarbonImmutable $day, float $metaShare): void
    {
        $at = $day->startOfDay()->addMinutes(mt_rand(7 * 60, 23 * 60 + 50));

        if ($at->gt($this->now)) {
            return;
        }

        $origin = $this->origin($metaShare);
        $visitor = (string) Str::uuid();
        $session = (string) Str::uuid();

        $this->event(EventType::PageView, $at, $visitor, $session, $origin);
        if (mt_rand(1, 100) <= 35) {
            $this->event(EventType::PageView, $at->addSeconds(40), $visitor, $session, $origin);
        }

        $clicked = mt_rand(1, 100) <= 28;
        if ($clicked) {
            $this->event(EventType::CtaClick, $at->addSeconds(25), $visitor, $session, $origin, ['hero', 'nav', 'cta-bar', 'plan:mentoria', 'plan:sessao'][mt_rand(0, 4)]);
        }

        if (mt_rand(1, 100) > ($clicked ? 88 : 1)) {
            return;
        }
        $this->event(EventType::FormView, $at->addSeconds(50), $visitor, $session, $origin);

        if (mt_rand(1, 100) > 40) {
            return;
        }
        $this->event(EventType::FormStart, $at->addSeconds(70), $visitor, $session, $origin);

        if (mt_rand(1, 100) > 45) {
            return;
        }
        $this->event(EventType::FormSubmit, $at->addSeconds(150), $visitor, $session, $origin);

        $lead = $this->createLead($at->addSeconds(150), $visitor, $origin);
        $this->progress($lead, $origin['quality']);
    }

    /**
     * @return array{channel: Channel, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_content: ?string, utm_term: ?string, referrer: ?string, quality: float}
     */
    private function origin(float $metaShare): array
    {
        $roll = mt_rand(1, 1000) / 1000;
        $none = ['utm_source' => null, 'utm_medium' => null, 'utm_campaign' => null, 'utm_content' => null, 'utm_term' => null, 'referrer' => null, 'quality' => 1.0];

        if ($roll <= $metaShare) {
            $campaign = $this->weighted(array_map(fn ($row) => $row[1], self::CAMPAIGNS));
            [$name, , , $quality, $adsets, $ads] = self::CAMPAIGNS[$campaign];

            return [
                'channel' => Channel::Meta,
                'utm_source' => mt_rand(0, 1) ? 'meta' : 'instagram',
                'utm_medium' => 'paid',
                'utm_campaign' => $name,
                'utm_content' => $adsets[array_rand($adsets)],
                'utm_term' => $ads[array_rand($ads)],
                'referrer' => null,
                'quality' => $quality,
            ];
        }

        return match (true) {
            $roll <= $metaShare + 0.14 => [...$none, 'channel' => Channel::Organico, 'referrer' => 'https://www.google.com/', 'quality' => 1.3],
            $roll <= $metaShare + 0.26 => [...$none, 'channel' => Channel::Direto, 'quality' => 1.2],
            $roll <= $metaShare + 0.32 => [...$none, 'channel' => Channel::Google, 'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'busca-claude-code'],
            default => [...$none, 'channel' => Channel::Outro, 'referrer' => 'https://www.linkedin.com/', 'quality' => 1.1],
        };
    }

    /**
     * @param  array<string, mixed>  $origin
     */
    private function createLead(CarbonImmutable $at, string $visitor, array $origin): Lead
    {
        $name = fake('pt_BR')->firstName().' '.fake('pt_BR')->lastName();
        $plan = [Plan::Mentoria, Plan::Mentoria, Plan::Mentoria, Plan::Sessao, Plan::Sessao, Plan::Squad, null, null][mt_rand(0, 7)];

        $lead = Lead::create([
            'name' => $name,
            'email' => Str::slug($name, '.').'.'.Str::lower(Str::random(4)).'@example.com',
            'whatsapp' => fake('pt_BR')->randomElement(['11', '21', '31', '41', '48', '51', '61', '71', '81']).'9'.fake()->numerify('########'),
            'level' => [LeadLevel::Junior, LeadLevel::Junior, LeadLevel::Estudante, LeadLevel::Estudante, LeadLevel::Estagiario, LeadLevel::Outro][mt_rand(0, 5)],
            'plan_interest' => $plan,
            'goal' => self::GOALS[array_rand(self::GOALS)],
            'status' => LeadStatus::Novo,
            'channel' => $origin['channel'],
            'utm_source' => $origin['utm_source'],
            'utm_medium' => $origin['utm_medium'],
            'utm_campaign' => $origin['utm_campaign'],
            'utm_content' => $origin['utm_content'],
            'utm_term' => $origin['utm_term'],
            'referrer' => $origin['referrer'],
            'landing_path' => '/',
            'visitor_id' => $visitor,
            'last_submitted_at' => $at->utc(),
            'status_changed_at' => $at->utc(),
        ]);

        $lead->forceFill(['created_at' => $at->utc(), 'updated_at' => $at->utc()])->saveQuietly();
        $lead->submissions()->forceCreate([
            'payload' => ['name' => $lead->name, 'email' => $lead->email, 'level' => $lead->level->value, 'plan' => $plan?->value, 'goal' => $lead->goal],
            'utm_source' => $lead->utm_source,
            'utm_campaign' => $lead->utm_campaign,
            'landing_path' => '/',
            'created_at' => $at->utc(),
        ]);

        return $lead;
    }

    /**
     * Avança o lead pelo pipeline com datas plausíveis; para no que ainda estaria no futuro.
     */
    private function progress(Lead $lead, float $quality): void
    {
        $at = CarbonImmutable::instance($lead->created_at)->setTimezone(Period::TIMEZONE);

        $steps = [
            [LeadStatus::ContatoFeito, 0.82, [2, 30], LostReason::SemResposta],
            [LeadStatus::ConversaAgendada, 0.55, [12, 72], LostReason::SemResposta],
            [LeadStatus::DiagnosticoFeito, 0.9, [24, 96], LostReason::Timing],
            [LeadStatus::Proposta, 0.72, [1, 30], LostReason::Timing],
        ];

        foreach ($steps as [$status, $chance, [$minHours, $maxHours], $lostReason]) {
            $at = $at->addMinutes(mt_rand($minHours * 60, $maxHours * 60));

            if ($at->gt($this->now)) {
                return;
            }

            if (mt_rand(1, 1000) > $chance * 1000) {
                $this->at($at, fn () => $this->pipeline->move($lead, LeadStatus::Perdido, $lostReason));

                return;
            }

            $meeting = $status === LeadStatus::ConversaAgendada ? $at->addHours(mt_rand(20, 70))->utc() : null;
            $this->at($at, fn () => $this->pipeline->move($lead, $status, meetingAt: $meeting));
        }

        $at = $at->addMinutes(mt_rand(12 * 60, 5 * 24 * 60));

        if ($at->gt($this->now)) {
            return;
        }

        if (mt_rand(1, 1000) > min(0.28 * $quality, 0.9) * 1000) {
            $reason = [LostReason::Preco, LostReason::Preco, LostReason::Timing, LostReason::EscolheuOutro, LostReason::ForaDoPublico][mt_rand(0, 4)];
            $this->at($at, fn () => $this->pipeline->move($lead, LeadStatus::Perdido, $reason));

            return;
        }

        $plan = [Plan::Mentoria, Plan::Mentoria, Plan::Mentoria, Plan::Mentoria, Plan::Mentoria, Plan::Mentoria, Plan::Mentoria, Plan::Sessao, Plan::Sessao, Plan::Squad][mt_rand(0, 9)];
        $this->sell($lead, $plan, $at);

        if ($plan === Plan::Sessao && mt_rand(1, 100) <= 30) {
            $upsellAt = $at->addDays(mt_rand(10, 30));

            if ($upsellAt->lte($this->now)) {
                $this->sell($lead, Plan::Mentoria, $upsellAt);
            }
        }
    }

    private function sell(Lead $lead, Plan $plan, CarbonImmutable $at): void
    {
        $amount = match ($plan) {
            Plan::Squad => mt_rand(9, 18) * 10000,
            Plan::Mentoria => mt_rand(1, 10) === 1 ? 35990 : 39990,
            Plan::Sessao => 12990,
        };

        $this->at($at, fn () => $this->recorder->record($lead->fresh(), [
            'plan' => $plan,
            'amount_cents' => $amount,
            'is_recurring' => $plan === Plan::Mentoria,
            'billing_day' => min($at->day, 28),
            'sold_at' => $at->utc(),
            'paid_now' => mt_rand(1, 100) <= 85,
            'method' => [PaymentMethod::Pix, PaymentMethod::Pix, PaymentMethod::Cartao, PaymentMethod::Boleto][mt_rand(0, 3)],
            'notes' => null,
        ]));
    }

    /**
     * Mês a mês: churn, cobranças do mês e pagamento de 90% delas em até 3 dias.
     */
    private function runBilling(): void
    {
        for ($month = $this->start; $month->lte($this->now); $month = $month->addMonthNoOverflow()) {
            $isCurrent = $month->isSameMonth($this->now);

            Subscription::where('status', SubscriptionStatus::Active)
                ->where('started_at', '<', $month->utc())
                ->get()
                ->each(function (Subscription $subscription) use ($month) {
                    if (mt_rand(1, 100) > 8) {
                        return;
                    }

                    $canceledAt = $month->setDay(max(1, $subscription->billing_day - 1))->setTime(18, 0);

                    if ($canceledAt->gt($this->now)) {
                        return;
                    }

                    $subscription->update([
                        'status' => SubscriptionStatus::Canceled,
                        'canceled_at' => $canceledAt->utc(),
                        'cancel_reason' => ['Conseguiu a vaga', 'Financeiro', 'Sem tempo no semestre', 'Concluiu a trilha'][mt_rand(0, 3)],
                    ]);
                });

            $this->charges->run($isCurrent ? $this->now : $month->setDay(28)->setTime(12, 0));
        }

        Payment::where('status', PaymentStatus::Pending)->get()->each(function (Payment $payment) {
            $paidAt = CarbonImmutable::parse($payment->due_date->toDateString().' 10:00', Period::TIMEZONE)->addDays(mt_rand(0, 3))->addMinutes(mt_rand(0, 600));

            if (mt_rand(1, 100) > 92 || $paidAt->gt($this->now)) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => $paidAt->utc(),
                'method' => [PaymentMethod::Pix, PaymentMethod::Pix, PaymentMethod::Cartao][mt_rand(0, 2)],
            ]);
        });
    }

    /**
     * Garante que hoje haja trabalho visível: leads esfriando, follow-ups vencidos e conversas marcadas.
     */
    private function shapeToday(): void
    {
        $cooling = Lead::cooling()->count();

        for ($i = $cooling; $i < 4; $i++) {
            $this->createLead($this->now->subHours(mt_rand(30, 70)), (string) Str::uuid(), $this->origin(0.7));
        }

        $actions = ['Mandar proposta da mentoria', 'Confirmar horário da conversa', 'Enviar material do diagnóstico', 'Cobrar retorno da proposta', 'Ligar para entender objeção de preço'];

        Lead::whereIn('status', [LeadStatus::ContatoFeito, LeadStatus::DiagnosticoFeito, LeadStatus::Proposta])
            ->inRandomOrder()->limit(10)->get()
            ->each(function (Lead $lead, int $index) use ($actions) {
                $lead->update([
                    'next_action' => $actions[$index % count($actions)],
                    'next_action_at' => ($index < 4 ? $this->now->subDays(mt_rand(1, 3)) : $this->now->addDays(mt_rand(0, 4)))->setTime(mt_rand(9, 17), 0)->utc(),
                ]);
            });

        Lead::where('status', LeadStatus::ConversaAgendada)->inRandomOrder()->limit(2)->get()
            ->each(fn (Lead $lead) => $lead->update(['meeting_at' => $this->now->setTime(mt_rand(14, 19), 0)->utc()]));
    }

    private function expense(CarbonImmutable $day, CashCategory $category, string $description, int $cents): void
    {
        CashEntry::create([
            'date' => $day->toDateString(),
            'direction' => CashDirection::Out,
            'category' => $category,
            'description' => $description,
            'amount_cents' => $cents,
        ]);
    }

    /**
     * @param  array<string, mixed>  $origin
     */
    private function event(EventType $type, CarbonImmutable $at, string $visitor, string $session, array $origin, ?string $target = null): void
    {
        $this->events[] = [
            'type' => $type->value,
            'target' => $target,
            'visitor_id' => $visitor,
            'session_id' => $session,
            'path' => '/',
            'channel' => $origin['channel']->value,
            'utm_source' => $origin['utm_source'],
            'utm_campaign' => $origin['utm_campaign'],
            'utm_content' => $origin['utm_content'],
            'utm_term' => $origin['utm_term'],
            'ip_hash' => null,
            'created_at' => $at->utc()->format('Y-m-d H:i:s'),
        ];

        if (count($this->events) >= 1000) {
            $this->flushEvents();
        }
    }

    private function flushEvents(): void
    {
        foreach (array_chunk($this->events, 500) as $chunk) {
            TrackingEvent::insert($chunk);
        }

        $this->events = [];
    }

    /**
     * Executa com o relógio parado no instante dado, para histórico e timestamps saírem com a data simulada.
     */
    private function at(CarbonImmutable $moment, callable $callback): mixed
    {
        $this->travel($moment->utc());

        try {
            return $callback();
        } finally {
            $this->travel($this->now->utc());
        }
    }

    private function travel(?CarbonImmutable $moment): void
    {
        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow($moment);
    }

    private function monthProgress(CarbonImmutable $day): float
    {
        return min(1, $this->start->diffInDays($day) / max(1, $this->start->diffInDays($this->now)));
    }

    /**
     * @param  array<int, float>  $weights
     */
    private function weighted(array $weights): int
    {
        $roll = mt_rand(1, 1000) / 1000 * array_sum($weights);

        foreach ($weights as $index => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $index;
            }
        }

        return array_key_last($weights);
    }
}
