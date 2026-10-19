<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Models\Note;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\Media\ImageService;
use App\Support\Money;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Products, sizes, notes, pyramid, images and stock (BE-46, BE-48). */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:80'], 'category_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:active,inactive,low'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $products = Product::query()->withTrashed(false)
            ->with(['category', 'variants', 'images'])
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($x) => $x->where('name', 'like', "%{$s}%")->orWhere('latin_name', 'like', "%{$s}%")->orWhere('inspired_by', 'like', "%{$s}%")))
            ->when($f['category_id'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->when(($f['status'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($f['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when(($f['status'] ?? null) === 'low', fn ($q) => $q->whereHas('variants', fn ($v) => $v->whereRaw('stock - reserved <= low_stock_threshold')))
            ->orderBy('category_id')->orderBy('sort')
            ->paginate($f['per_page'] ?? 30);

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $p) => $this->row($p)),
            'meta' => ['total' => $products->total(), 'current_page' => $products->currentPage(), 'last_page' => $products->lastPage()],
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(['data' => $this->full($product)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $product = DB::transaction(function () use ($data) {
            $product = new Product(self::attributes($data));
            $product->slug = Slugger::unique(Product::class, $data['latin_name'] ?: $data['name']);
            $product->save();
            $this->syncChildren($product, $data, true);

            return $product;
        });
        Cache::forget('home');

        return response()->json(['data' => $this->full($product)], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request, $product);
        DB::transaction(function () use ($product, $data) {
            $product->update(self::attributes($data));
            $this->syncChildren($product, $data, false);
        });
        Cache::forget('home');

        return response()->json(['data' => $this->full($product->fresh())]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->update(['is_active' => false]);
        $product->delete();
        Cache::forget('home');

        return response()->json(null, 204);
    }

    public function uploadImage(Request $request, Product $product, ImageService $images): JsonResponse
    {
        $request->validate(['image' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'], 'alt' => ['nullable', 'string', 'max:160']]);
        $path = $images->storeProductImage($request->file('image'), $product->id);
        $product->images()->create(['path' => $path, 'alt' => $request->input('alt') ?: $product->name, 'sort' => (int) $product->images()->max('sort') + 1]);
        Cache::forget('home');

        return response()->json(['data' => $this->full($product)], 201);
    }

    public function deleteImage(Product $product, ProductImage $image, ImageService $images): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        $images->delete($image->path);
        $image->delete();
        Cache::forget('home');

        return response()->json(['data' => $this->full($product)]);
    }

    public function sortImages(Request $request, Product $product): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        foreach ($ids as $i => $id) {
            $product->images()->whereKey($id)->update(['sort' => $i]);
        }

        return response()->json(['data' => $this->full($product)]);
    }

    /** Stock adjustment with a reason; every change lands in stock_movements. */
    public function adjustStock(Request $request, ProductVariant $variant): JsonResponse
    {
        $data = $request->validate([
            'change' => ['required', 'integer', 'between:-10000,10000', 'not_in:0'],
            'reason' => ['required', 'in:restock,adjustment,damage'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($variant, $data, $request) {
            $v = ProductVariant::whereKey($variant->id)->lockForUpdate()->first();
            $new = $v->stock + $data['change'];
            if ($new < $v->reserved) {
                throw ValidationException::withMessages(['change' => ['لا يمكن أن يقل المخزون عن الكمية المحجوزة لطلبات قائمة ('.$v->reserved.').']]);
            }
            $v->forceFill(['stock' => $new])->save();
            StockMovement::create(['product_variant_id' => $v->id, 'change' => $data['change'], 'reason' => $data['reason'], 'user_id' => $request->user()->id, 'note' => $data['note'] ?? null]);
        });
        Cache::forget('home');

        return response()->json(['data' => $this->full($variant->product)]);
    }

    public function movements(ProductVariant $variant): JsonResponse
    {
        $rows = StockMovement::where('product_variant_id', $variant->id)->latest('id')->limit(50)->get()
            ->map(fn ($m) => ['change' => $m->change, 'reason' => $m->reason, 'note' => $m->note, 'orderId' => $m->order_id, 'at' => $m->created_at?->toIso8601String()]);

        return response()->json(['data' => $rows]);
    }

    public function lowStock(): JsonResponse
    {
        $rows = ProductVariant::with('product')->whereRaw('stock - reserved <= low_stock_threshold')->where('is_active', true)
            ->orderByRaw('stock - reserved')->limit(50)->get()
            ->map(fn ($v) => ['variantId' => $v->id, 'product' => $v->product?->name, 'slug' => $v->product?->slug, 'ml' => $v->size_ml, 'available' => $v->available(), 'threshold' => $v->low_stock_threshold]);

        return response()->json(['data' => $rows]);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'latin_name' => ['required', 'string', 'max:120'],
            'inspired_by' => ['nullable', 'string', 'max:120'],
            'signature_note' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'concentration' => ['nullable', 'string', 'max:32'],
            'is_active' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'variants' => ['required', 'array', 'min:1', 'max:4'],
            'variants.*.size_ml' => ['required', 'integer', Rule::in(ProductVariant::SIZES), 'distinct'],
            'variants.*.price' => ['required', 'numeric', 'min:1', 'max:100000'],
            'variants.*.compare_at' => ['nullable', 'numeric', 'gt:variants.*.price', 'max:100000'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'variants.*.is_active' => ['boolean'],
            'notes' => ['array', 'max:20'],
            'notes.*' => ['string', 'max:64'],
            'pyramid' => ['array'],
            'pyramid.top' => ['array', 'max:8'], 'pyramid.heart' => ['array', 'max:8'], 'pyramid.base' => ['array', 'max:8'],
            'pyramid.*.*' => ['string', 'max:64'],
        ]);
        $data['sort'] ??= $product?->sort ?? 0;
        $data['concentration'] ??= 'EAU DE PARFUM';

        return $data;
    }

    /** Product columns only; sizes, notes and pyramid are synced separately. */
    private static function attributes(array $data): array
    {
        return Arr::except($data, ['variants', 'notes', 'pyramid']);
    }

    private function syncChildren(Product $product, array $data, bool $creating): void
    {
        foreach ($data['variants'] as $v) {
            $variant = $product->variants()->firstOrNew(['size_ml' => $v['size_ml']]);
            $variant->fill([
                'price_piasters' => Money::piasters($v['price']),
                'compare_at_piasters' => isset($v['compare_at']) ? Money::piasters($v['compare_at']) : null,
                'low_stock_threshold' => $v['low_stock_threshold'] ?? $variant->low_stock_threshold ?? 5,
                'is_active' => $v['is_active'] ?? true,
            ]);
            if (! $variant->exists) {
                $variant->sku = 'MD-'.Str::upper(Str::limit(Str::slug($product->latin_name, ''), 12, '')).'-'.$v['size_ml'].'-'.Str::upper(Str::random(3));
                $variant->stock = $creating ? (int) ($v['stock'] ?? 0) : 0;
            }
            $variant->save();
        }
        $keep = collect($data['variants'])->pluck('size_ml');
        $product->variants()->whereNotIn('size_ml', $keep)->update(['is_active' => false]);

        $product->notes()->detach();
        $attach = fn (string $name, string $layer, int $i) => $product->notes()->attach(
            Note::firstOrCreate(['name' => trim($name)], ['slug' => Slugger::unique(Note::class, $name)])->id,
            ['layer' => $layer, 'sort' => $i],
        );
        foreach (array_unique($data['notes'] ?? []) as $i => $name) {
            $attach($name, 'key', $i);
        }
        foreach (['top', 'heart', 'base'] as $layer) {
            foreach (array_unique($data['pyramid'][$layer] ?? []) as $i => $name) {
                $attach($name, $layer, $i);
            }
        }
    }

    private function row(Product $p): array
    {
        return [
            'id' => $p->id, 'slug' => $p->slug, 'name' => $p->name, 'latin' => $p->latin_name, 'inspiredBy' => $p->inspired_by,
            'category' => $p->category?->name, 'categoryId' => $p->category_id, 'active' => $p->is_active, 'soldCount' => $p->sold_count,
            'image' => $p->images->first() ? '/storage/'.$p->images->first()->path : null,
            'variants' => $p->variants->map(fn ($v) => ['id' => $v->id, 'ml' => $v->size_ml, 'price' => Money::egp($v->price_piasters), 'stock' => $v->stock, 'reserved' => $v->reserved, 'low' => $v->available() <= $v->low_stock_threshold, 'active' => $v->is_active]),
        ];
    }

    private function full(Product $p): array
    {
        $p->load(['category', 'variants', 'images', 'notes']);

        return $this->row($p) + [
            'description' => $p->description,
            'signature' => $p->signature_note,
            'concentration' => $p->concentration,
            'sort' => $p->sort,
            'notes' => $p->notes->where('pivot.layer', 'key')->pluck('name')->values(),
            'pyramid' => ProductDetailResource::pyramid($p) ?? ['top' => [], 'heart' => [], 'base' => []],
            'images' => $p->images->map(fn ($i) => ['id' => $i->id, 'url' => '/storage/'.$i->path, 'alt' => $i->alt]),
            'variants' => $p->variants->map(fn ($v) => [
                'id' => $v->id, 'ml' => $v->size_ml, 'sku' => $v->sku, 'price' => Money::egp($v->price_piasters), 'compareAt' => Money::egp($v->compare_at_piasters),
                'stock' => $v->stock, 'reserved' => $v->reserved, 'available' => $v->available(), 'lowThreshold' => $v->low_stock_threshold, 'active' => $v->is_active,
            ]),
        ];
    }
}
