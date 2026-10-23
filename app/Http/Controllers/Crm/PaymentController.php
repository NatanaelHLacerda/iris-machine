<?php

namespace App\Http\Controllers\Crm;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\PayPaymentRequest;
use App\Models\Payment;
use App\Support\Crm\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function pay(PayPaymentRequest $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages(['payment' => 'Essa cobrança não está em aberto.']);
        }

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => Carbon::parse($request->validated('paid_at'), Period::TIMEZONE)->setTimeFrom(Period::today())->utc(),
            'method' => $request->validated('method'),
        ]);

        return back()->with('status', 'Pagamento registrado.');
    }

    public function refund(Payment $payment): RedirectResponse
    {
        if ($payment->status !== PaymentStatus::Paid) {
            throw ValidationException::withMessages(['payment' => 'Só dá para estornar um pagamento recebido.']);
        }

        $payment->update(['status' => PaymentStatus::Refunded, 'refunded_at' => now()]);

        return back()->with('status', 'Pagamento estornado.');
    }
}
