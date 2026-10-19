<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\Cart\CouponService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Coupons and their usage (BE-51). Values travel in pounds; percent stays a whole number. */
class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kind = $request->validate(['kind' => ['nullable', 'in:general,discovery_credit']])['kind'] ?? 'general';
        $coupons = Coupon::where('kind', $kind)->withSum('redemptions', 'amount_piasters')->latest('id')->paginate(30);

        return response()->json([
            'data' => $coupons->getCollection()->map(fn (Coupon $c) => $this->row($c)),
            'meta' => ['total' => $coupons->total(), 'current_page' => $coupons->currentPage(), 'last_page' => $coupons->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $coupon = Coupon::create($this->validated($request));

        return response()->json(['data' => $this->row($coupon)], 201);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $coupon->update($this->validated($request, $coupon));

        return response()->json(['data' => $this->row($coupon->loadSum('redemptions', 'amount_piasters'))]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->used_count > 0 ? $coupon->update(['is_active' => false]) : $coupon->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $d = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon)],
            'type' => ['required', 'in:percent,fixed,free_shipping'],
            'value' => ['required_unless:type,free_shipping', 'nullable', 'numeric', 'min:0', $request->input('type') === 'percent' ? 'max:90' : 'max:100000'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ], ['code.regex' => 'الكود حروف إنجليزية وأرقام فقط.']);

        return [
            'code' => strtoupper($d['code']),
            'type' => $d['type'],
            'value' => match ($d['type']) {
                'percent' => (int) $d['value'],
                'fixed' => Money::piasters($d['value']),
                default => 0,
            },
            'min_subtotal_piasters' => Money::piasters($d['min_subtotal'] ?? 0),
            'max_discount_piasters' => isset($d['max_discount']) ? Money::piasters($d['max_discount']) : null,
            'usage_limit' => $d['usage_limit'] ?? null,
            'per_customer_limit' => $d['per_customer_limit'] ?? null,
            'starts_at' => $d['starts_at'] ?? null,
            'ends_at' => $d['ends_at'] ?? null,
            'is_active' => $d['is_active'] ?? true,
        ];
    }

    private function row(Coupon $c): array
    {
        return [
            'id' => $c->id, 'code' => $c->code, 'type' => $c->type, 'kind' => $c->kind, 'label' => CouponService::label($c),
            'value' => $c->type === 'percent' ? $c->value : Money::egp($c->value),
            'minSubtotal' => Money::egp($c->min_subtotal_piasters), 'maxDiscount' => Money::egp($c->max_discount_piasters),
            'usageLimit' => $c->usage_limit, 'perCustomerLimit' => $c->per_customer_limit, 'used' => $c->used_count,
            'discountGiven' => Money::egp((int) ($c->redemptions_sum_amount_piasters ?? 0)),
            'phone' => $c->phone, 'startsAt' => $c->starts_at?->toDateString(), 'endsAt' => $c->ends_at?->toDateString(), 'active' => $c->is_active,
        ];
    }
}
