<?php

namespace App\Console\Commands;

use App\Services\CheckoutService;
use Illuminate\Console\Command;

class ReleaseExpiredOrders extends Command
{
    protected $signature = 'orders:release-expired';

    protected $description = 'Release unpaid expired order inventory reservations.';

    public function handle(CheckoutService $checkout): int
    {
        $count = $checkout->releaseExpired();

        $this->info("Released expired orders: {$count}");

        return self::SUCCESS;
    }
}
