<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * List all payments pending verification.
     * GET /admin/payments
     */
    public function index(Request $request): View
    {
        $query = Payment::with(['user', 'package'])->latest();

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        } else {
            // Default: show pending with tx_hash submitted
            $query->where('status', 'pending')->whereNotNull('tx_hash');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tx_hash', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate(20)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * Verify/confirm a payment.
     * PATCH /admin/payments/{payment}/verify
     */
    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $payment->isPending()) {
            return back()->with('error', "Payment #{$payment->id} is already {$payment->status}.");
        }

        $this->subscriptionService->confirmPayment($payment, $request->user(), $request->note ?? '');

        return redirect()->route('admin.payments.index')
            ->with('success', "Payment #{$payment->id} confirmed and subscription activated.");
    }

    /**
     * Reject a payment.
     * PATCH /admin/payments/{payment}/reject
     */
    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if (! $payment->isPending()) {
            return back()->with('error', "Payment #{$payment->id} is already {$payment->status}.");
        }

        $this->subscriptionService->rejectPayment($payment, $request->user(), $request->reason);

        return redirect()->route('admin.payments.index')
            ->with('success', "Payment #{$payment->id} has been rejected.");
    }
}
