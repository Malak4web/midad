<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Cancels online orders whose payment window ran out and frees their stock (BE-36). */
#[Signature('orders:release-expired')]
#[Description('Cancel unpaid online orders past their reservation window')]
class ReleaseExpiredReservations extends Command
{
    public function handle(OrderStatusService $status): int
    {
        $count = 0;
        Order::where('status', 'pending_payment')->where('payment_status', '!=', 'paid')
            ->where('reserved_until', '<', now())
            ->orderBy('id')
            ->each(function (Order $order) use ($status, &$count) {
                $status->transition($order, 'cancelled', null, 'انتهت مهلة الدفع');
                $count++;
            });
        $this->info("Released {$count} order(s).");

        return self::SUCCESS;
    }
}
