<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/** One interface for every way Midad gets paid (BE-39). */
interface PaymentGateway
{
    public function name(): string;

    /** Starts an online payment; returns where the shopper should go next. */
    public function start(Order $order, Payment $payment): string;
}
