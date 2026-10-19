<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** TST-27: hostile input in search, filters and bodies never reaches SQL or HTML raw. */
class InputSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public static function hostileQueries(): array
    {
        return [
            'sql quote' => ["' OR 1=1 --"],
            'stacked' => ['عود"; DROP TABLE products; --'],
            'union' => ['x UNION SELECT password FROM users'],
            'fulltext operators only' => ['+ - > < ( ) ~ * " @'],
            'lone operator' => ['*'],
            'like wildcards' => ['%%%'],
            'underscore' => ['___'],
            'backslash' => ['\\\\'],
            'html' => ['<script>alert(1)</script>'],
            'very long' => [str_repeat('عطر ', 40)],
        ];
    }

    #[DataProvider('hostileQueries')]
    public function test_search_survives_hostile_queries(string $q): void
    {
        $res = $this->getJson('/api/search?q='.urlencode($q));
        $this->assertContains($res->status(), [200, 422], "q={$q} gave {$res->status()}");
        if ($res->status() === 200) {
            // wildcards and injections must not turn into "match everything"
            $this->assertLessThan(Product::count(), count($res->json('data')));
        }
        $this->assertTrue(Schema::hasTable('products'));
    }

    public function test_catalog_filters_reject_anything_but_their_whitelist(): void
    {
        foreach ([
            'sort=price_asc;DROP TABLE products',
            'sort=(SELECT 1)',
            'size=50 OR 1=1',
            'price_min=0x10',
            'price_max=1e9',
            'per_page=100000',
        ] as $qs) {
            $this->getJson('/api/products?'.$qs)->assertStatus(422);
        }
        // string filters are bound values: an injection simply matches nothing
        $this->getJson('/api/products?category='.urlencode("men' OR '1'='1"))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/products?note='.urlencode("x') OR 1=1 #"))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_filters_are_whitelisted_too(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin);

        $this->getJson('/api/admin/orders?status='.urlencode("new' OR 1=1"))->assertStatus(422);
        $this->getJson('/api/admin/orders?from=2026-01-01%27%20OR%201=1')->assertStatus(422);
        $this->getJson('/api/admin/orders?q='.urlencode("%' OR 1=1 --"))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/admin/products?q='.urlencode('%'))->assertOk();
    }
}
