<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaystackVerificationService;
use Illuminate\Console\Command;

class ReconcilePaystackPayments extends Command
{
    protected $signature = 'payments:reconcile-paystack';

    protected $description = 'Reconcile pending Paystack payments through the transaction verify API.';

    public function handle(PaystackVerificationService $verification): int
    {
        $after = now()->subMinutes((int) config('paystack.verify_after_minutes', 2));
        $count = 0;

        Payment::query()
            ->where('provider', 'paystack')
            ->whereIn('status', ['created', 'initiated', 'pending'])
            ->where('created_at', '<=', $after)
            ->orderBy('id')
            ->chunkById(100, function ($payments) use ($verification, &$count) {
                foreach ($payments as $payment) {
                    if ($verification->safeVerify($payment)) {
                        $count++;
                    }
                }
            });

        $this->info("Reconciled Paystack payments: {$count}");

        return self::SUCCESS;
    }
}
