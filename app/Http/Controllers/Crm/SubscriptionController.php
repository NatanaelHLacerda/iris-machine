<?php

namespace App\Http\Controllers\Crm;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CancelSubscriptionRequest;
use App\Models\Subscription;
use App\Support\Crm\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Cancela e descarta as cobranças em aberto com vencimento depois do cancelamento.
     */
    public function cancel(CancelSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $canceledAt = Carbon::parse($request->validated('canceled_at'), Period::TIMEZONE)->endOfDay()->utc();

        DB::transaction(function () use ($subscription, $canceledAt, $request) {
            $subscription->update([
                'status' => SubscriptionStatus::Canceled,
                'canceled_at' => $canceledAt,
                'cancel_reason' => $request->validated('cancel_reason'),
            ]);

            $subscription->payments()
                ->where('status', PaymentStatus::Pending)
                ->where('due_date', '>', $canceledAt->copy()->setTimezone(Period::TIMEZONE)->toDateString().' 23:59:59')
                ->delete();
        });

        return back()->with('status', 'Assinatura cancelada.');
    }
}
