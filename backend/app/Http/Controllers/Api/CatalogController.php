<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SpotlightResource;
use App\Models\Category;
use App\Models\FinderQuestion;
use App\Models\Note;
use App\Models\Setting;
use App\Services\Catalog\FinderService;
use App\Services\Catalog\ProductQuery;
use App\Services\Catalog\ProductSearch;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Public storefront endpoints: BE-24 … BE-29. */
class CatalogController extends Controller
{
    private const MIDAD_PALETTE = ['bg' => '#3B342C', 'surface' => '#4A4137', 'fg' => '#F2EADB', 'muted' => '#CFC4B2', 'accent' => '#D6B77E'];

    public function home(): JsonResponse
    {
        return response()->json(['data' => self::homePayload()]);
    }

    /** Also embedded in the HTML shell of "/" so the first paint skips a round trip (FE-51). */
    public static function homePayload(): array
    {
        // cached; admin writes call Cache::forget('home') (BE-47, BE-54)
        return Cache::remember('home', now()->addMinutes(10), function () {
            $box = Setting::get('discovery_box', []);
            $sections = Category::where('is_active', true)->orderBy('sort')->get()->map(function (Category $c) {
                $spot = $c->activeSpotlight()?->load(['product.variants', 'product.notes', 'product.category', 'product.images']);
                $others = ProductQuery::base()->where('category_id', $c->id)
                    ->when($spot, fn ($q) => $q->whereKeyNot($spot->product_id))
                    ->orderBy('sort')->get();

                return [
                    'category' => (new CategoryResource($c))->resolve(),
                    'spotlight' => $spot ? (new SpotlightResource($spot))->resolve() : null,
                    'palette' => $spot?->palette ?? self::MIDAD_PALETTE,
                    'others' => ProductResource::collection($others)->resolve(),
                ];
            })->values()->all();

            // round-trip to plain arrays: nested resources are objects, and the cache store refuses serialized objects
            return json_decode(json_encode([
                'announcements' => Setting::get('announcements', []),
                'discovery' => [
                    'price' => Money::egp($box['price_piasters'] ?? 0),
                    'samples' => $box['samples'] ?? 6,
                    'sampleMl' => $box['sample_ml'] ?? 2,
                ],
                'sections' => $sections,
            ]), true);
        });
    }

    public function categories()
    {
        return CategoryResource::collection(
            Category::where('is_active', true)->withCount(['products' => fn ($q) => $q->active()])->orderBy('sort')->get()
        );
    }

    public function category(Request $request, string $slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $filters = $request->validate(ProductQuery::rules());
        $products = ProductQuery::apply(ProductQuery::base(), ['category' => $slug] + $filters)
            ->paginate($filters['per_page'] ?? 24)->withQueryString();

        return ProductResource::collection($products)->additional(['meta' => ['category' => new CategoryResource($category)]]);
    }

    public function products(Request $request)
    {
        $filters = $request->validate(ProductQuery::rules());

        return ProductResource::collection(
            ProductQuery::apply(ProductQuery::base(), $filters)->paginate($filters['per_page'] ?? 24)->withQueryString()
        );
    }

    public function product(string $slug)
    {
        $product = ProductQuery::base()->where('slug', $slug)->firstOrFail();
        $noteIds = $product->notes->pluck('id');
        // similar = most shared notes, then same category
        $similar = ProductQuery::base()->whereKeyNot($product->id)
            ->withCount(['notes as shared' => fn ($q) => $q->whereIn('notes.id', $noteIds)])
            ->orderByDesc('shared')
            ->orderByRaw('category_id = ? desc', [$product->category_id])
            ->limit(4)->get();

        return (new ProductDetailResource($product))->additional(['meta' => ['similar' => ProductResource::collection($similar)]]);
    }

    public function search(Request $request)
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:1', 'max:80'], 'limit' => ['nullable', 'integer', 'min:1', 'max:24']]);

        return ProductResource::collection(ProductSearch::run($data['q'], $data['limit'] ?? 24));
    }

    public function notes(): JsonResponse
    {
        $notes = Note::query()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')->orderBy('name')
            ->get()
            ->map(fn ($n) => ['slug' => $n->slug, 'name' => $n->name, 'count' => $n->products_count]);

        return response()->json(['data' => $notes]);
    }

    public function finderQuestions(): JsonResponse
    {
        $questions = FinderQuestion::where('is_active', true)->orderBy('sort')->with('answers')->get()
            ->map(fn ($q) => [
                'key' => $q->key,
                'q' => $q->question,
                'options' => $q->answers->map(fn ($a) => ['v' => $a->value, 'label' => $a->label]),
            ]);

        return response()->json(['data' => $questions]);
    }

    public function finderResult(Request $request): JsonResponse
    {
        $data = $request->validate(['answers' => ['required', 'array', 'max:10'], 'answers.*' => ['string', 'max:32']]);
        $results = FinderService::recommend($data['answers'])->map(fn ($r) => [
            'product' => (new ProductResource($r['product']))->resolve(),
            'why' => $r['why'],
        ]);

        return response()->json(['data' => $results]);
    }
}
