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
        $reconciled = 0;
        $failed = 0;

        $query = Payment::query()
            ->where('provider', 'payhero')
            ->whereIn('status', ['created', 'initiated', 'pending'])
            ->where('created_at', '<=', $before);

        $candidates = (clone $query)->count();

        $query->orderBy('id')
            ->chunkById(100, function ($payments) use ($verification, &$reconciled, &$failed) {
                foreach ($payments as $payment) {
                    if ($verification->safeVerify($payment)) {
                        $reconciled++;
                    } else {
                        $failed++;
                    }
                }
            });

        $this->info("Eligible pending PayHero payments: {$candidates}");
        $this->info("Reconciled PayHero payments: {$reconciled}");

        if ($failed > 0) {
            $this->warn("PayHero status lookups that failed: {$failed}. Check storage/logs/laravel.log.");
        }

        return self::SUCCESS;
    }
}
