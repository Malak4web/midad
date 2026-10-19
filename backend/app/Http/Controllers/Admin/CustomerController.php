<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Orders\OrderStatusService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Customers (BE-52): accounts with their order history and lifetime value. */
class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->validate(['q' => ['nullable', 'string', 'max:80']])['q'] ?? null;
        $users = User::where('role', User::ROLE_CUSTOMER)
            ->when($q, fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($o) => $o->whereNotIn('status', ['cancelled', 'pending_payment'])], 'total_piasters')
            ->latest('id')->paginate(30);

        return response()->json([
            'data' => $users->getCollection()->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
                'orders' => $u->orders_count, 'spent' => Money::egp((int) $u->spent), 'joinedAt' => $u->created_at?->toDateString(),
            ]),
            'meta' => ['total' => $users->total(), 'current_page' => $users->currentPage(), 'last_page' => $users->lastPage()],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $orders = $user->orders()->latest('id')->get();

        return response()->json(['data' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'joinedAt' => $user->created_at?->toDateString(),
            'addresses' => $user->addresses()->with('governorate')->get()->map(fn ($a) => trim("{$a->governorate?->name}، {$a->city}، {$a->street}")),
            'orders' => $orders->map(fn ($o) => ['number' => $o->number, 'status' => OrderStatusService::LABELS[$o->status], 'total' => Money::egp($o->total_piasters), 'at' => $o->created_at?->toDateString()]),
        ]]);
    }
}
