<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PayHeroVerificationService;
use Illuminate\Console\Command;

class ReconcilePayHeroPayments extends Command
{
    protected $signature = 'payments:reconcile-payhero';

    protected $description = 'Reconcile pending PayHero M-Pesa payments through the transaction status API.';

    public function handle(PayHeroVerificationService $verification): int
    {
        $before = now()->subMinutes((int) config('payhero.reconcile_after_minutes', 2));
        $count = 0;

        Payment::query()
            ->where('provider', 'payhero')
            ->whereIn('status', ['created', 'initiated', 'pending'])
            ->where('created_at', '<=', $before)
            ->orderBy('id')
            ->chunkById(100, function ($payments) use ($verification, &$count) {
                foreach ($payments as $payment) {
                    if ($verification->safeVerify($payment)) {
                        $count++;
                    }
                }
            });

        $this->info("Reconciled PayHero payments: {$count}");

        return self::SUCCESS;
    }
}
