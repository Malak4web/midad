<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Server cart (BE-30, BE-31, BE-34, BE-23). Guests are identified by an
 * encrypted, http-only cookie; signed-in customers by their account.
 */
final class CartService
{
    public const COOKIE = 'midad_cart';

    public const MAX_QTY = 10;

    public const MAX_BOXES = 3;

    public function guestCart(Request $request): ?Cart
    {
        $token = $request->cookie(self::COOKIE);

        return $token ? Cart::where('token', $token)->whereNull('user_id')->first() : null;
    }

    public function resolve(Request $request, bool $create = false): ?Cart
    {
        if ($user = $request->user()) {
            return $create
                ? Cart::firstOrCreate(['user_id' => $user->id], ['token' => Str::random(48)])
                : Cart::where('user_id', $user->id)->first();
        }
        $cart = $this->guestCart($request);
        if (! $cart && $create) {
            $cart = Cart::create(['token' => Str::random(48)]);
        }

        return $cart;
    }

    public function cookieFor(Cart $cart): ?SymfonyCookie
    {
        return $cart->user_id ? null : Cookie::make(self::COOKIE, $cart->token, 60 * 24 * 30, '/', null, null, true, false, 'lax');
    }

    public function forgetCookie(): SymfonyCookie
    {
        return Cookie::forget(self::COOKIE);
    }

    /** Called right after login/register: guest lines move into the account cart. */
    public function mergeIntoUser(?Cart $guest, User $user): void
    {
        if (! $guest || $guest->items()->doesntExist()) {
            $guest?->delete();

            return;
        }
        DB::transaction(function () use ($guest, $user) {
            $mine = Cart::firstOrCreate(['user_id' => $user->id], ['token' => Str::random(48)]);
            foreach ($guest->items as $item) {
                $same = $item->kind === 'product'
                    ? $mine->items()->where('kind', 'product')->where('product_variant_id', $item->product_variant_id)->first()
                    : null;
                if ($same) {
                    $same->update(['quantity' => min(self::MAX_QTY, $same->quantity + $item->quantity)]);
                } else {
                    $item->forceFill(['cart_id' => $mine->id])->save();
                }
            }
            $mine->fill([
                'coupon_id' => $mine->coupon_id ?? $guest->coupon_id,
                'gift_wrap' => $mine->gift_wrap || $guest->gift_wrap,
                'gift_recipient' => $mine->gift_recipient ?? $guest->gift_recipient,
                'gift_message' => $mine->gift_message ?? $guest->gift_message,
            ])->save();
            $guest->delete();
        });
    }

    public function addProduct(Cart $cart, string $slug, int $ml, int $qty): void
    {
        $variant = ProductVariant::where('size_ml', $ml)->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->active()->where('slug', $slug))->first();
        if (! $variant) {
            throw ValidationException::withMessages(['size_ml' => ['هذا الحجم غير متاح.']]);
        }
        $item = $cart->items()->where('kind', 'product')->where('product_variant_id', $variant->id)->first();
        $this->ensureStock($variant, ($item?->quantity ?? 0) + $qty);
        $item
            ? $item->update(['quantity' => min(self::MAX_QTY, $item->quantity + $qty)])
            : $cart->items()->create(['kind' => 'product', 'product_variant_id' => $variant->id, 'quantity' => min(self::MAX_QTY, $qty)]);
    }

    public function setQuantity(CartItem $item, int $qty): void
    {
        if ($qty <= 0) {
            $item->delete();

            return;
        }
        if ($item->kind === 'product') {
            $this->ensureStock($item->variant, $qty);
        } else {
            $others = CartItem::where('cart_id', $item->cart_id)->where('kind', 'discovery_box')->whereKeyNot($item->id)->sum('quantity');
            if ($others + $qty > self::MAX_BOXES) {
                throw ValidationException::withMessages(['quantity' => ['الحد الأقصى ثلاث علب اكتشاف في الطلب الواحد.']]);
            }
        }
        $item->update(['quantity' => min($item->kind === 'product' ? self::MAX_QTY : self::MAX_BOXES, $qty)]);
    }

    /** @param int[] $productIds */
    public function addDiscoveryBox(Cart $cart, array $productIds): void
    {
        $box = Setting::get('discovery_box', []);
        $need = (int) ($box['samples'] ?? 6);
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        if (count($ids) !== $need || Product::active()->whereIn('id', $ids)->count() !== $need) {
            throw ValidationException::withMessages(['product_ids' => ["اختر {$need} عطور مختلفة متاحة."]]);
        }
        if ($cart->items()->where('kind', 'discovery_box')->sum('quantity') >= self::MAX_BOXES) {
            throw ValidationException::withMessages(['product_ids' => ['الحد الأقصى ثلاث علب اكتشاف في الطلب الواحد.']]);
        }
        $cart->items()->create(['kind' => 'discovery_box', 'quantity' => 1, 'meta' => ['product_ids' => $ids]]);
    }

    public function setGift(Cart $cart, bool $wrap, ?string $recipient, ?string $message): void
    {
        $clean = fn (?string $s, int $max) => $s === null ? null : (mb_substr(trim(strip_tags($s)), 0, $max) ?: null);
        $cart->update(['gift_wrap' => $wrap, 'gift_recipient' => $wrap ? $clean($recipient, 60) : null, 'gift_message' => $wrap ? $clean($message, 120) : null]);
    }

    public function applyCoupon(Cart $cart, string $code, ?string $phone, ?int $userId): void
    {
        $coupon = CouponService::find($code);
        if (! $coupon) {
            throw ValidationException::withMessages(['code' => ['كود الخصم غير صحيح.']]);
        }
        $subtotal = PricingService::quote($cart)['subtotal'];
        if ($problem = CouponService::problem($coupon, $subtotal, $phone, $userId)) {
            throw ValidationException::withMessages(['code' => [$problem]]);
        }
        $cart->update(['coupon_id' => $coupon->id]);
    }

    private function ensureStock(?ProductVariant $variant, int $qty): void
    {
        $available = $variant?->available() ?? 0;
        if ($qty > min($available, self::MAX_QTY)) {
            throw ValidationException::withMessages(['quantity' => [
                $available <= 0 ? 'هذا الحجم نفد من المخزون.' : 'الكمية المتاحة من هذا الحجم '.min($available, self::MAX_QTY).' فقط.',
            ]]);
        }
    }
}
