<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * TST-25 (the part that runs without a server): the busy endpoints run a fixed
 * number of queries however big the catalog or cart gets. N+1 shows up here as
 * a count that grows with the data. (Lazy loading also throws outside production.)
 */
class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function queries(callable $fn): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    /** Duplicate the catalog so every list has twice the rows. */
    private function doubleCatalog(): void
    {
        foreach (Product::with(['variants', 'notes'])->get() as $p) {
            $copy = $p->replicate(['search_text']);
            $copy->slug = $p->slug.'-2';
            $copy->save();
            foreach ($p->variants as $v) {
                $v->replicate()->forceFill(['product_id' => $copy->id, 'sku' => $v->sku.'-2'])->save();
            }
            $copy->notes()->attach($p->notes->mapWithKeys(fn ($n) => [$n->id => ['layer' => $n->pivot->layer, 'sort' => $n->pivot->sort]]));
        }
    }

    public function test_storefront_reads_do_not_grow_with_the_catalog(): void
    {
        $calls = [
            'home' => fn () => $this->getJson('/api/home')->assertOk(),
            'list' => fn () => $this->getJson('/api/products?per_page=48')->assertOk(),
            'category' => fn () => $this->getJson('/api/products?category=women&per_page=48')->assertOk(),
            'search' => fn () => $this->getJson('/api/search?q='.urlencode('عود'))->assertOk(),
            'product' => fn () => $this->getJson('/api/products/her-blossom')->assertOk(),
        ];
        $before = [];
        foreach ($calls as $k => $call) {
            Cache::flush();
            $before[$k] = $this->queries($call);
        }

        $this->doubleCatalog();

        foreach ($calls as $k => $call) {
            Cache::flush();
            $after = $this->queries($call);
            $this->assertSame($before[$k], $after, "{$k}: {$before[$k]} queries with 30 products, {$after} with 60");
            // home builds each category band (about a dozen queries each), then caches for 10 minutes
            $this->assertLessThanOrEqual($k === 'home' ? 40 : 25, $after, "{$k} runs {$after} queries");
        }
    }

    public function test_cached_home_costs_almost_nothing(): void
    {
        Cache::flush();
        $this->getJson('/api/home')->assertOk();
        $this->assertLessThanOrEqual(3, $this->queries(fn () => $this->getJson('/api/home')->assertOk()));
    }

    public function test_checkout_does_not_grow_with_cart_lines(): void
    {
        Mail::fake();
        $run = function (array $slugs) {
            $req = $this->withCredentials()->withHeader('Origin', 'http://localhost:5391');
            $token = null;
            foreach ($slugs as $slug) {
                $res = ($token ? $req->withUnencryptedCookie('midad_cart', $token) : $req)->postJson('/api/cart/items', ['slug' => $slug, 'size_ml' => 30]);
                $token = $res->getCookie('midad_cart', false)?->getValue() ?? $token;
            }

            return $this->queries(fn () => $req->withUnencryptedCookie('midad_cart', $token)->postJson('/api/checkout', [
                'customer_name' => 'سارة', 'phone' => '01012345678', 'governorate_id' => 1,
                'city' => 'مدينة نصر', 'street' => 'شارع ١', 'payment_method' => 'cod',
            ])->assertCreated());
        };

        $two = $run(['her-blossom', 'victory-elixir']);
        $six = $run(['her-blossom', 'victory-elixir', 'ocean-wave', 'white-cream', 'qahwa-fusion', 'black-oud-velvet']);
        // each line needs its own locked row, order item and stock movement; nothing else may scale
        $this->assertLessThanOrEqual($two + 4 * 4, $six, "checkout: {$two} queries for 2 lines, {$six} for 6");
    }
}
