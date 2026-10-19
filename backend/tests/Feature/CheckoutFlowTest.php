<?php

namespace Tests\Feature;

use App\Mail\DiscoveryCreditMail;
use App\Mail\OrderStatusMail;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Orders\OrderStatusService;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymobHmac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private ?string $cartToken = null;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();
    }

    /** Sends the guest cart cookie like a browser would and remembers a new one. */
    private function api(string $method, string $uri, array $data = []): TestResponse
    {
        $req = $this->withCredentials()->withHeader('Origin', 'http://localhost:5391');
        if ($this->cartToken) {
            $req = $req->withUnencryptedCookie('midad_cart', $this->cartToken);
        }
        $res = $req->json($method, $uri, $data);
        if ($c = collect($res->headers->getCookies())->first(fn ($c) => $c->getName() === 'midad_cart')) {
            $this->cartToken = $c->getValue() ?: null;
        }

        return $res;
    }

    private function address(array $extra = []): array
    {
        return array_merge([
            'customer_name' => 'سارة أحمد',
            'phone' => '٠١٠١٢٣٤٥٦٧٨',
            'email' => 'sara@example.com',
            'governorate_id' => 1, // القاهرة (60 ج.م placeholder)
            'city' => 'مدينة نصر',
            'street' => 'شارع عباس العقاد',
            'payment_method' => 'cod',
        ], $extra);
    }

    public function test_checkout_needs_only_one_address_line(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'victory-elixir', 'size_ml' => 50, 'quantity' => 1])->assertCreated();
        $this->api('POST', '/api/checkout', ['customer_name' => 'سارة', 'phone' => '01012345678', 'governorate_id' => 1, 'payment_method' => 'cod'])
            ->assertUnprocessable()->assertJsonValidationErrors(['street'])->assertJsonMissingValidationErrors(['city', 'email']);
        $number = $this->api('POST', '/api/checkout', ['customer_name' => 'سارة', 'phone' => '01012345678', 'governorate_id' => 1, 'street' => 'مدينة نصر، شارع عباس العقاد ١٢، الدور ٣', 'payment_method' => 'cod'])
            ->assertCreated()->json('data.number');
        $this->assertSame('مدينة نصر، شارع عباس العقاد ١٢، الدور ٣', Order::where('number', $number)->first()->shipping_address['street']);
    }

    public function test_guest_cart_is_priced_on_the_server(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'victory-elixir', 'size_ml' => 50, 'quantity' => 2, 'price' => 1])
            ->assertCreated()
            ->assertJsonPath('data.count', 2)
            ->assertJsonPath('data.items.0.unitPrice', 950)
            ->assertJsonPath('data.summary.subtotal', 1900);
        $this->assertNotNull($this->cartToken);

        $this->api('GET', '/api/cart?governorate_id=1')
            ->assertJsonPath('data.summary.shipping', 0)          // 1900 ≥ 1500 free-shipping threshold
            ->assertJsonPath('data.summary.total', 1900);
    }

    public function test_quantity_cannot_exceed_stock(): void
    {
        ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'qahwa-fusion'))->where('size_ml', 100)->update(['stock' => 2]);
        $this->api('POST', '/api/cart/items', ['slug' => 'qahwa-fusion', 'size_ml' => 100, 'quantity' => 3])
            ->assertStatus(422)->assertJsonPath('errors.quantity.0', 'الكمية المتاحة من هذا الحجم 2 فقط.');
    }

    public function test_discovery_box_needs_six_different_perfumes(): void
    {
        $ids = Product::orderBy('id')->limit(6)->pluck('id')->all();
        $this->api('POST', '/api/cart/discovery-box', ['product_ids' => array_slice($ids, 0, 5)])->assertStatus(422);
        $this->api('POST', '/api/cart/discovery-box', ['product_ids' => [$ids[0], $ids[0], $ids[1], $ids[2], $ids[3], $ids[4]]])->assertStatus(422);
        $this->api('POST', '/api/cart/discovery-box', ['product_ids' => $ids])
            ->assertCreated()->assertJsonPath('data.items.0.kind', 'discovery_box')
            ->assertJsonPath('data.items.0.unitPrice', 350)
            ->assertJsonCount(6, 'data.items.0.samples');

        // at most three boxes per order, also when raising a line's quantity
        $this->api('POST', '/api/cart/discovery-box', ['product_ids' => $ids])->assertCreated();
        $this->api('POST', '/api/cart/discovery-box', ['product_ids' => $ids])->assertCreated();
        $first = $this->api('GET', '/api/cart')->json('data.items.0.id');
        $this->api('PATCH', "/api/cart/items/{$first}", ['quantity' => 3])->assertStatus(422);
        $this->assertSame(3, $this->api('GET', '/api/cart')->json('data.count'));
    }

    public function test_coupons_apply_and_explain_why_not(): void
    {
        Coupon::create(['code' => 'MIDAD10', 'type' => 'percent', 'value' => 10, 'min_subtotal_piasters' => 100000, 'is_active' => true]);
        $this->api('POST', '/api/cart/items', ['slug' => 'her-blossom', 'size_ml' => 50]);
        $this->api('POST', '/api/cart/coupon', ['code' => 'midad10'])->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'هذا الكود يعمل مع الطلبات من 1000 ج.م فأكثر.');
        $this->api('POST', '/api/cart/items', ['slug' => 'her-blossom', 'size_ml' => 30]);
        $this->api('POST', '/api/cart/coupon', ['code' => 'midad10'])->assertOk()
            ->assertJsonPath('data.coupon.label', 'خصم 10٪')
            ->assertJsonPath('data.summary.discount', 160); // 10% of 1600
        $this->api('POST', '/api/cart/coupon', ['code' => 'NOPE'])->assertStatus(422)->assertJsonPath('errors.code.0', 'كود الخصم غير صحيح.');
    }

    public function test_a_once_per_customer_coupon_cannot_be_used_twice_by_the_same_phone(): void
    {
        Coupon::create(['code' => 'ONCE', 'type' => 'fixed', 'value' => 5000, 'per_customer_limit' => 1, 'min_subtotal_piasters' => 0, 'is_active' => true]);
        $this->api('POST', '/api/cart/items', ['slug' => 'her-blossom', 'size_ml' => 30]);
        $this->api('POST', '/api/cart/coupon', ['code' => 'once'])->assertOk();
        $this->api('POST', '/api/checkout', $this->address())->assertCreated();

        $this->api('POST', '/api/cart/items', ['slug' => 'her-blossom', 'size_ml' => 30]);
        $this->api('POST', '/api/cart/coupon', ['code' => 'once'])->assertOk(); // phone not known yet
        $this->api('POST', '/api/checkout', $this->address(['phone' => '+20 10 1234 5678']))->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'استخدمت هذا الكود من قبل.');
        $this->api('POST', '/api/checkout', $this->address(['phone' => '01099999999']))->assertCreated();
    }

    public function test_gift_message_is_cleaned(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'white-cream', 'size_ml' => 30]);
        $this->api('PUT', '/api/cart/gift', ['wrap' => true, 'recipient' => '<b>سارة</b>', 'message' => '<script>alert(1)</script>كل عام وأنتِ الأثر'])
            ->assertJsonPath('data.gift.recipient', 'سارة')
            ->assertJsonPath('data.gift.message', 'alert(1)كل عام وأنتِ الأثر');
    }

    public function test_cash_on_delivery_checkout_places_reserves_and_notifies(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'black-oud-velvet', 'size_ml' => 50, 'quantity' => 1]);
        $this->api('PUT', '/api/cart/gift', ['wrap' => true, 'recipient' => 'سارة', 'message' => 'كل عام']);
        $res = $this->api('POST', '/api/checkout', $this->address())->assertCreated()
            ->assertJsonPath('data.status', 'placed')->assertJsonPath('data.redirectUrl', null)->assertJsonPath('data.phone', '01012345678');

        $order = Order::where('number', $res->json('data.number'))->firstOrFail();
        $this->assertSame(105000 + 6000, $order->total_piasters); // 1050 + 60 shipping (under threshold)
        $this->assertSame('سارة', $order->gift->recipient_name);
        $variant = ProductVariant::find($order->items->first()->product_variant_id);
        $this->assertSame(1, $variant->reserved);
        $this->api('GET', '/api/cart')->assertJsonPath('data.count', 0);
        Mail::assertQueued(OrderStatusMail::class, fn ($m) => $m->status === 'placed');
    }

    public function test_checkout_rejects_a_bad_phone_in_arabic(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'ocean-wave', 'size_ml' => 30]);
        $this->api('POST', '/api/checkout', $this->address(['phone' => '0123']))->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'اكتب رقم موبايل مصري صحيح من ١١ رقماً يبدأ بـ 01.');
    }

    public function test_online_payment_through_the_sandbox_and_webhook_rules(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'lady-rose', 'size_ml' => 100]);
        $res = $this->api('POST', '/api/checkout', $this->address(['payment_method' => 'card']))->assertCreated()
            ->assertJsonPath('data.status', 'pending_payment');
        $this->assertStringContainsString('/pay/sandbox?ref=', $res->json('data.redirectUrl'));
        Mail::assertNothingQueued();

        $payment = Payment::firstOrFail();
        // spoofed callback is refused
        $obj = FakeGateway::transaction($payment, true);
        $this->postJson('/api/payments/paymob/webhook?hmac=deadbeef', ['obj' => $obj])->assertStatus(400);
        $this->assertSame('pending', $payment->fresh()->status);

        // genuine callback pays the order
        $hmac = PaymobHmac::sign($obj, config('services.paymob.hmac_secret'));
        $this->postJson('/api/payments/paymob/webhook?hmac='.$hmac, ['obj' => $obj])->assertOk();
        $order = $payment->order->fresh();
        $this->assertSame(['paid', 'placed'], [$order->payment_status, $order->status]);

        // replaying it changes nothing (idempotent)
        $this->postJson('/api/payments/paymob/webhook?hmac='.$hmac, ['obj' => $obj])->assertOk();
        $this->assertSame(2, $order->history()->count());
        Mail::assertQueued(OrderStatusMail::class, 1);
    }

    public function test_browsing_does_not_use_up_the_payment_rate_limit(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->getJson('/api/categories')->assertOk();
        }
        $this->api('POST', '/api/cart/items', ['slug' => 'lady-rose', 'size_ml' => 30]);
        $this->api('POST', '/api/checkout', $this->address(['payment_method' => 'card']))->assertCreated();
        $ref = Payment::firstOrFail()->gateway_reference;
        $this->api('POST', "/api/payments/sandbox/{$ref}", ['result' => 'success'])->assertOk();
    }

    public function test_underpaid_callback_does_not_mark_paid(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'lady-rose', 'size_ml' => 100]);
        $this->api('POST', '/api/checkout', $this->address(['payment_method' => 'wallet']));
        $payment = Payment::firstOrFail();
        $obj = array_merge(FakeGateway::transaction($payment, true), ['amount_cents' => 100]);
        $this->postJson('/api/payments/paymob/webhook?hmac='.PaymobHmac::sign($obj, config('services.paymob.hmac_secret')), ['obj' => $obj])->assertOk();
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('pending_payment', $payment->order->fresh()->status);
    }

    public function test_the_last_bottle_cannot_be_sold_twice(): void
    {
        $v = ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'deep-ocean'))->where('size_ml', 100)->firstOrFail();
        $v->update(['stock' => 1]);
        $this->api('POST', '/api/cart/items', ['slug' => 'deep-ocean', 'size_ml' => 100]);
        $first = $this->cartToken;
        $this->cartToken = null;
        $this->api('POST', '/api/cart/items', ['slug' => 'deep-ocean', 'size_ml' => 100]);
        $second = $this->cartToken;

        $this->cartToken = $first;
        $this->api('POST', '/api/checkout', $this->address())->assertCreated();
        $this->cartToken = $second;
        $this->api('POST', '/api/checkout', $this->address(['phone' => '01112345678']))->assertStatus(422);
        $this->assertSame(1, $v->fresh()->reserved);
    }

    public function test_orders_are_private(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'roma-velvet', 'size_ml' => 30]);
        $number = $this->api('POST', '/api/checkout', $this->address())->json('data.number');

        $this->getJson("/api/orders/{$number}?phone=01012345678")->assertOk()->assertJsonPath('data.number', $number);
        $this->getJson("/api/orders/{$number}?phone=01099999999")->assertNotFound();
        $this->getJson("/api/orders/{$number}")->assertNotFound();
        $this->actingAs(User::factory()->create())->getJson("/api/orders/{$number}")->assertNotFound();
    }

    public function test_status_machine_moves_stock(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'paris-glow', 'size_ml' => 50, 'quantity' => 2]);
        $order = Order::where('number', $this->api('POST', '/api/checkout', $this->address())->json('data.number'))->firstOrFail();
        $v = ProductVariant::find($order->items->first()->product_variant_id);
        [$stock, $reserved] = [$v->stock, $v->reserved];
        $svc = app(OrderStatusService::class);

        $svc->transition($order, 'confirmed');
        $v->refresh();
        $this->assertSame([$stock - 2, $reserved - 2], [$v->stock, $v->reserved]);

        $svc->transition($order->fresh(), 'cancelled');
        $this->assertSame($stock, $v->fresh()->stock);

        $this->expectException(ValidationException::class);
        $svc->transition($order->fresh(), 'shipped');
    }

    public function test_expired_online_orders_release_their_stock(): void
    {
        $this->api('POST', '/api/cart/items', ['slug' => 'sugar-bloom', 'size_ml' => 30]);
        $this->api('POST', '/api/checkout', $this->address(['payment_method' => 'card']));
        $order = Order::firstOrFail();
        $order->update(['reserved_until' => now()->subMinute()]);
        $this->artisan('orders:release-expired')->assertSuccessful();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, ProductVariant::find($order->items->first()->product_variant_id)->reserved);
    }

    public function test_delivered_discovery_box_issues_a_phone_bound_credit(): void
    {
        $this->api('POST', '/api/cart/discovery-box', ['product_ids' => Product::orderBy('id')->limit(6)->pluck('id')->all()]);
        $order = Order::where('number', $this->api('POST', '/api/checkout', $this->address())->json('data.number'))->firstOrFail();
        $svc = app(OrderStatusService::class);
        foreach (['confirmed', 'preparing', 'shipped', 'delivered'] as $s) {
            $svc->transition($order->fresh(), $s);
        }
        $credit = Coupon::where('kind', 'discovery_credit')->firstOrFail();
        $this->assertSame([35000, '01012345678'], [$credit->value, $credit->phone]);
        Mail::assertQueued(DiscoveryCreditMail::class);

        // another phone cannot use it at checkout
        $this->cartToken = null;
        $this->api('POST', '/api/cart/items', ['slug' => 'victory-elixir', 'size_ml' => 100]);
        $this->api('POST', '/api/cart/coupon', ['code' => $credit->code])->assertOk()->assertJsonPath('data.summary.credit', 350);
        $this->api('POST', '/api/checkout', $this->address(['phone' => '01112345678']))->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'رصيد مجموعة الاكتشاف مرتبط برقم موبايل آخر.');
        $this->api('POST', '/api/checkout', $this->address())->assertCreated();
        $this->assertSame(1, $credit->fresh()->used_count);
    }
}
