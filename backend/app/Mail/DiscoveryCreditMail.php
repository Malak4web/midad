<?php

namespace App\Mail;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DiscoveryCreditMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public Coupon $credit) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'رصيد مجموعة الاكتشاف جاهز لأول زجاجة');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.discovery-credit', with: [
            'order' => $this->order,
            'credit' => $this->credit,
            'url' => rtrim(config('app.frontend_url'), '/').'/',
        ]);
    }
}
