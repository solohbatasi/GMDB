<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PayHeroCallbackService;
use App\Services\PayHeroClient;
use Illuminate\Console\Command;
use Throwable;

class ReconcilePayHeroPayments extends Command
{
    protected $signature = 'payments:reconcile-payhero';

    protected $description = 'Reconcile pending PayHero payments through the transaction status API.';

    public function handle(PayHeroClient $client, PayHeroCallbackService $callbacks): int
    {
        $count = 0;
        $after = now()->subMinutes((int) config('payhero.reconcile_after_minutes', 2));

        Payment::query()
            ->where('provider', 'payhero')
            ->whereIn('status', ['initiated', 'pending'])
            ->where('created_at', '<=', $after)
            ->chunkById(50, function ($payments) use ($client, $callbacks, &$count) {
                foreach ($payments as $payment) {
                    try {
                        $reference = $payment->payhero_reference ?: $payment->external_reference;
                        $payload = $client->transactionStatus($reference);
                        $payload['external_reference'] ??= $payment->external_reference;
                        $callbacks->handle($payload);
                        $count++;
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });

        $this->info("Reconciled PayHero payments: {$count}");

        return self::SUCCESS;
    }
}
