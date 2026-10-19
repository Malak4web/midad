<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const SUBJECTS = [
        'placed' => 'وصلنا طلبك',
        'confirmed' => 'تم تأكيد طلبك',
        'shipped' => 'طلبك في الطريق',
        'delivered' => 'تم توصيل طلبك',
        'cancelled' => 'تم إلغاء طلبك',
    ];

    public function __construct(public Order $order, public string $status) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (self::SUBJECTS[$this->status] ?? 'تحديث طلبك').' · '.$this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-status', with: [
            'order' => $this->order->load('items'),
            'label' => OrderStatusService::LABELS[$this->status] ?? $this->status,
            'status' => $this->status,
            'url' => rtrim(config('app.frontend_url'), '/').'/order/'.$this->order->number.'?phone='.$this->order->phone,
        ]);
    }
}
