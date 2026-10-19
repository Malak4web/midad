<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Governorate;
use App\Services\Cart\CartService;
use App\Services\Cart\CouponService;
use App\Services\Cart\PricingService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Cart API (BE-30 … BE-34). Every response returns the full, server-priced cart. */
class CartController extends Controller
{
    public function __construct(private CartService $carts) {}

    public function show(Request $request): JsonResponse
    {
        $data = $request->validate(['governorate_id' => ['nullable', 'integer', 'exists:governorates,id']]);

        return $this->respond($request, $this->carts->resolve($request), $data['governorate_id'] ?? null);
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:120'],
            'size_ml' => ['required', 'integer', 'in:10,30,50,100'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);
        $cart = $this->carts->resolve($request, true);
        $this->carts->addProduct($cart, $data['slug'], $data['size_ml'], $data['quantity'] ?? 1);

        return $this->respond($request, $cart, null, 201);
    }

    public function addDiscoveryBox(Request $request): JsonResponse
    {
        $data = $request->validate(['product_ids' => ['required', 'array', 'max:12'], 'product_ids.*' => ['integer']]);
        $cart = $this->carts->resolve($request, true);
        $this->carts->addDiscoveryBox($cart, $data['product_ids']);

        return $this->respond($request, $cart, null, 201);
    }

    public function updateItem(Request $request, int $item): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:10']]);
        $cart = $this->carts->resolve($request);
        $this->carts->setQuantity($this->ownedItem($cart, $item), $data['quantity']);

        return $this->respond($request, $cart);
    }

    public function removeItem(Request $request, int $item): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $this->ownedItem($cart, $item)->delete();

        return $this->respond($request, $cart);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $cart = $this->carts->resolve($request, true);
        $this->carts->applyCoupon($cart, $data['code'], $request->user()?->phone, $request->user()?->id);

        return $this->respond($request, $cart);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);
        $cart?->update(['coupon_id' => null]);

        return $this->respond($request, $cart);
    }

    public function gift(Request $request): JsonResponse
    {
        $data = $request->validate([
            'wrap' => ['required', 'boolean'],
            'recipient' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:120'],
        ]);
        $cart = $this->carts->resolve($request, true);
        $this->carts->setGift($cart, $data['wrap'], $data['recipient'] ?? null, $data['message'] ?? null);

        return $this->respond($request, $cart);
    }

    private function ownedItem(?Cart $cart, int $id): CartItem
    {
        abort_unless($cart, 404);

        return $cart->items()->whereKey($id)->firstOrFail();
    }

    private function respond(Request $request, ?Cart $cart, ?int $governorateId = null, int $status = 200): JsonResponse
    {
        $response = response()->json(['data' => self::present($cart, $governorateId ? Governorate::find($governorateId) : null, $request->user()?->phone, $request->user()?->id)], $status);
        if ($cart && ($cookie = $this->carts->cookieFor($cart))) {
            $response->withCookie($cookie);
        }

        return $response;
    }

    public static function present(?Cart $cart, ?Governorate $gov = null, ?string $phone = null, ?int $userId = null): array
    {
        if (! $cart) {
            return ['items' => [], 'count' => 0, 'coupon' => null, 'gift' => ['wrap' => false, 'recipient' => null, 'message' => null],
                'summary' => ['subtotal' => 0, 'discount' => 0, 'credit' => 0, 'shipping' => null, 'gift' => 0, 'total' => 0, 'freeShippingRemaining' => null]];
        }
        $cart->refresh();
        $q = PricingService::quote($cart, $gov, $phone, $userId);
        $items = $q['lines']->map(function ($l) {
            $base = ['id' => $l['item']->id, 'kind' => $l['kind'], 'quantity' => $l['quantity'], 'unitPrice' => Money::egp($l['unit']), 'lineTotal' => Money::egp($l['total']), 'available' => $l['available']];
            if ($l['kind'] === 'discovery_box') {
                return $base + ['name' => 'مجموعة الاكتشاف', 'samples' => $l['samples']];
            }
            $p = $l['variant']?->product;

            return $base + ['name' => $p?->name, 'latin' => $p?->latin_name, 'slug' => $p?->slug, 'ml' => $l['variant']?->size_ml, 'stock' => $l['stock']];
        })->values();

        return [
            'items' => $items,
            'count' => (int) $q['lines']->sum('quantity'),
            'coupon' => $q['coupon'] ? ['code' => $q['coupon']->code, 'label' => CouponService::label($q['coupon']), 'problem' => $q['couponProblem']] : null,
            'gift' => ['wrap' => (bool) $cart->gift_wrap, 'recipient' => $cart->gift_recipient, 'message' => $cart->gift_message],
            'summary' => [
                'subtotal' => Money::egp($q['subtotal']),
                'discount' => Money::egp($q['discount']),
                'credit' => Money::egp($q['credit']),
                'shipping' => Money::egp($q['shipping']),
                'gift' => Money::egp($q['gift']),
                'total' => Money::egp($q['total']),
                'freeShippingRemaining' => Money::egp($q['freeShippingRemaining']),
            ],
        ];
    }
}
