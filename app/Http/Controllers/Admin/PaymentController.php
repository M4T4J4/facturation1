<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:confirmed,rejected']]);

        DB::transaction(function () use ($payment, $validated): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            abort_unless($lockedPayment->status === 'pending', 422);

            if ($validated['status'] === 'confirmed') {
                $invoice = Invoice::query()->lockForUpdate()->findOrFail($lockedPayment->invoice_id);
                abort_unless(in_array($invoice->status, ['unpaid', 'partial'], true), 422);
                $remainingBalance = $invoice->amount - $invoice->amount_paid;
                abort_if($lockedPayment->amount > $remainingBalance, 422);
                $amountPaid = $invoice->amount_paid + $lockedPayment->amount;
                $isPaid = $amountPaid >= $invoice->amount;

                $invoice->update([
                    'amount_paid' => $amountPaid,
                    'status' => $isPaid ? 'paid' : 'partial',
                    'paid_at' => $isPaid ? now() : null,
                ]);
                $lockedPayment->update(['status' => 'confirmed', 'confirmed_at' => now()]);

                return;
            }

            $lockedPayment->update(['status' => 'rejected']);
        });

        return back()->with('status', 'payment-reviewed');
    }
}
