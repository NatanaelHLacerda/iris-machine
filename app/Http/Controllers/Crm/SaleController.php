<?php

namespace App\Http\Controllers\Crm;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Subscription;
use App\Support\Crm\Period;
use App\Support\Crm\RevenueMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    private const array TABS = ['vendas', 'assinaturas', 'cobrancas'];

    public function index(Request $request, RevenueMetrics $metrics): View
    {
        $tab = in_array($request->query('aba'), self::TABS, true) ? $request->query('aba') : 'vendas';
        $period = Period::fromRequest($request);
        $today = Period::today();

        $overdue = Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->where('due_date', '<', $today->toDateString());

        return view('crm.sales.index', [
            'tab' => $tab,
            'period' => $period,
            'summary' => [
                'revenue' => $metrics->revenueForMonth($today),
                'mrr' => $metrics->mrr(),
                'active' => $metrics->activeSubscriptions(),
                'overdue_count' => (clone $overdue)->count(),
                'overdue_amount' => (int) (clone $overdue)->sum('amount_cents'),
            ],
            'sales' => $tab === 'vendas'
                ? Sale::with('lead')->whereBetween('sold_at', Period::bounds($period))->latest('sold_at')->get()
                : collect(),
            'subscriptions' => $tab === 'assinaturas'
                ? Subscription::with('lead')->withCount(['payments as paid_count' => fn ($query) => $query->where('status', PaymentStatus::Paid)])
                    ->orderByRaw('status = ?', [SubscriptionStatus::Canceled->value])->latest('started_at')->get()
                : collect(),
            'payments' => $tab === 'cobrancas'
                ? Payment::with(['lead', 'sale'])
                    ->where(fn ($query) => $query->where('status', PaymentStatus::Pending)
                        ->orWhereBetween('paid_at', Period::bounds($period)))
                    ->orderByRaw('status = ? desc', [PaymentStatus::Pending->value])
                    ->orderBy('due_date')
                    ->get()
                : collect(),
        ]);
    }
}
