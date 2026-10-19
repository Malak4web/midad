<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Governorate;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\Cart\CouponService;
use App\Services\Cart\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/** TST-01: the pricing engine, coupon rules and shipping threshold, service level. */
class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private Governorate $cairo;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->cairo = Governorate::where('name', 'القاهرة')->first();
        $this->cairo->update(['shipping_fee_piasters' => 6000]);
        Setting::updateOrCreate(['key' => 'free_shipping_threshold_piasters'], ['value' => 150000]);
        Setting::updateOrCreate(['key' => 'gift_wrap_fee_piasters'], ['value' => 2500]);
    }

    /** A cart with given lines: [price in piasters, quantity], each on a different variant. */
    private function cart(array $lines, array $attrs = []): Cart
    {
        $cart = Cart::create(['token' => Str::random(40)] + $attrs);
        $variants = ProductVariant::orderBy('id')->take(count($lines))->get();
        foreach ($lines as $n => [$price, $qty]) {
            $variants[$n]->update(['price_piasters' => $price, 'stock' => 50, 'is_active' => true]);
            $cart->items()->create(['kind' => 'product', 'product_variant_id' => $variants[$n]->id, 'quantity' => $qty]);
        }

        return $cart->fresh();
    }

    private function coupon(array $attrs): Coupon
    {
        return Coupon::create($attrs + ['code' => strtoupper(Str::random(8)), 'is_active' => true, 'min_subtotal_piasters' => 0]);
    }

    public function test_subtotal_shipping_and_total(): void
    {
        $q = PricingService::quote($this->cart([[50000, 2]]), $this->cairo);
        $this->assertSame(100000, $q['subtotal']);
        $this->assertSame(6000, $q['shipping']);
        $this->assertSame(106000, $q['total']);
        $this->assertSame(50000, $q['freeShippingRemaining']);
    }

    public function test_free_shipping_from_the_threshold_after_discounts(): void
    {
        $this->assertSame(0, PricingService::quote($this->cart([[150000, 1]]), $this->cairo)['shipping']);

        // a discount that drops the order under the threshold brings shipping back
        $c = $this->coupon(['type' => 'fixed', 'value' => 10000]);
        $q = PricingService::quote($this->cart([[150000, 1]], ['coupon_id' => $c->id]), $this->cairo);
        $this->assertSame(10000, $q['discount']);
        $this->assertSame(6000, $q['shipping']);
    }

    public function test_no_governorate_means_shipping_unknown_not_free(): void
    {
        $q = PricingService::quote($this->cart([[50000, 1]]));
        $this->assertNull($q['shipping']);
        $this->assertSame(50000, $q['total']);
    }

    public function test_percent_coupon_respects_its_cap(): void
    {
        $c = $this->coupon(['type' => 'percent', 'value' => 20, 'max_discount_piasters' => 15000]);
        $q = PricingService::quote($this->cart([[100000, 1]], ['coupon_id' => $c->id]), $this->cairo);
        $this->assertSame(15000, $q['discount']);

        // a nonsense percentage is clamped to 100%
        $silly = $this->coupon(['type' => 'percent', 'value' => 250]);
        $this->assertSame(100000, PricingService::quote($this->cart([[100000, 1]], ['coupon_id' => $silly->id]))['discount']);
    }

    public function test_fixed_coupon_never_goes_below_zero(): void
    {
        $c = $this->coupon(['type' => 'fixed', 'value' => 999999]);
        $q = PricingService::quote($this->cart([[30000, 1]], ['coupon_id' => $c->id]), $this->cairo);
        $this->assertSame(30000, $q['discount']);
        $this->assertSame(6000, $q['total']);
    }

    public function test_free_shipping_coupon_and_gift_wrap(): void
    {
        $c = $this->coupon(['type' => 'free_shipping', 'value' => 0]);
        $q = PricingService::quote($this->cart([[30000, 1]], ['coupon_id' => $c->id, 'gift_wrap' => true]), $this->cairo);
        $this->assertSame(0, $q['shipping']);
        $this->assertSame(2500, $q['gift']);
        $this->assertSame(32500, $q['total']);
    }

    public function test_coupon_rules_explain_themselves(): void
    {
        $this->assertSame('هذا الكود غير مفعّل.', CouponService::problem($this->coupon(['type' => 'fixed', 'value' => 1, 'is_active' => false]), 1000));
        $this->assertSame('هذا الكود لم يبدأ بعد.', CouponService::problem($this->coupon(['type' => 'fixed', 'value' => 1, 'starts_at' => now()->addDay()]), 1000));
        $this->assertSame('انتهت صلاحية هذا الكود.', CouponService::problem($this->coupon(['type' => 'fixed', 'value' => 1, 'ends_at' => now()->subMinute()]), 1000));
        $this->assertSame('هذا الكود يعمل مع الطلبات من 500 ج.م فأكثر.', CouponService::problem($this->coupon(['type' => 'fixed', 'value' => 1, 'min_subtotal_piasters' => 50000]), 49999));
        $this->assertNull(CouponService::problem($this->coupon(['type' => 'fixed', 'value' => 1, 'min_subtotal_piasters' => 50000]), 50000));

        $limited = $this->coupon(['type' => 'fixed', 'value' => 1, 'usage_limit' => 1]);
        $limited->forceFill(['used_count' => 1])->save();
        $this->assertSame('تم استخدام هذا الكود بالكامل.', CouponService::problem($limited, 1000));
    }

    public function test_discovery_credit_is_bound_to_its_phone_and_only_covers_bottles(): void
    {
        $credit = $this->coupon(['type' => 'fixed', 'kind' => 'discovery_credit', 'value' => 40000, 'phone' => '01012345678']);
        $this->assertNotNull(CouponService::problem($credit, 100000, '01099999999'));
        $this->assertNull(CouponService::problem($credit, 100000, '01012345678'));

        $q = PricingService::quote($this->cart([[25000, 1]], ['coupon_id' => $credit->id]), $this->cairo, '01012345678');
        $this->assertSame(25000, $q['credit']); // never more than the bottles themselves
        $this->assertSame(0, $q['discount']);
    }

    public function test_inactive_variants_cost_nothing_and_are_flagged(): void
    {
        $cart = $this->cart([[50000, 1], [30000, 1]]);
        $cart->load('items.variant')->items->first()->variant->update(['is_active' => false]);
        $q = PricingService::quote($cart->fresh(), $this->cairo);
        $this->assertSame(30000, $q['subtotal']);
        $this->assertFalse($q['lines']->first()['available']);
    }
}
