<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Mail\DiscoveryCreditMail;
use App\Mail\OrderStatusMail;
use App\Services\Orders\DiscoveryCreditService;
use Illuminate\Support\Facades\Mail;

/** Order e-mails (BE-44) and the discovery-box credit on delivery (BE-45). */
class SendOrderNotifications
{
    private const NOTIFY = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        if ($event->to === 'delivered' && ($credit = DiscoveryCreditService::issue($order)) && $order->email) {
            Mail::to($order->email)->queue(new DiscoveryCreditMail($order, $credit));
        }
        if ($order->email && in_array($event->to, self::NOTIFY, true)) {
            Mail::to($order->email)->queue(new OrderStatusMail($order, $event->to));
        }
    }
}
