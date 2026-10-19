<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Customer account: orders and saved addresses (BE-37). */
class AccountController extends Controller
{
    public function orders(Request $request)
    {
        return OrderResource::collection($request->user()->orders()->with('items.selections.product')->latest('id')->paginate(10));
    }

    public function addresses(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->addresses()->with('governorate:id,name')->orderByDesc('is_default')->latest('id')->get()]);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $address = $request->user()->addresses()->create($this->validated($request));
        $this->normalizeDefault($request, $address);

        return response()->json(['data' => $address->fresh('governorate:id,name')], 201);
    }

    public function updateAddress(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->update($this->validated($request));
        $this->normalizeDefault($request, $address);

        return response()->json(['data' => $address->fresh('governorate:id,name')]);
    }

    public function destroyAddress(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', Phone::rule()],
            'city' => ['required', 'string', 'max:80'],
            'street' => ['required', 'string', 'max:160'],
            'building' => ['nullable', 'string', 'max:40'],
            'floor' => ['nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:20'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'is_default' => ['boolean'],
        ]);
        $data['phone'] = Phone::normalize($data['phone']);

        return $data;
    }

    private function normalizeDefault(Request $request, Address $address): void
    {
        $user = $request->user();
        if ($address->is_default || $user->addresses()->count() === 1) {
            $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        }
    }
}
