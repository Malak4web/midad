<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CategorySpotlight;
use App\Models\Coupon;
use App\Models\FinderAnswer;
use App\Models\Order;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * TST-03: every protected endpoint, read straight from the router so a new
 * route is covered the day it is added. Guest → 401, customer on the admin
 * area → 403, staff on admin-only actions → 403.
 */
class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private array $params = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();

        // real rows for every bound parameter, so a 404 can never hide a missing 403
        $this->withCredentials()->withHeader('Origin', 'http://localhost:5391');
        $cookie = $this->postJson('/api/cart/items', ['slug' => 'her-blossom', 'size_ml' => 30])->getCookie('midad_cart', false)->getValue();
        $number = $this->withUnencryptedCookie('midad_cart', $cookie)->postJson('/api/checkout', [
            'customer_name' => 'سارة', 'phone' => '01012345678', 'governorate_id' => 1,
            'city' => 'مدينة نصر', 'street' => 'شارع ١', 'payment_method' => 'cod',
        ])->assertCreated()->json('data.number');

        $owner = User::factory()->create();
        $this->params = [
            'order' => $number,
            'product' => 1,
            'variant' => 1,
            'category' => 1,
            'spotlight' => CategorySpotlight::value('id'),
            'answer' => FinderAnswer::value('id'),
            'coupon' => Coupon::create(['code' => 'MATRIX', 'type' => 'fixed', 'value' => 100, 'min_subtotal_piasters' => 0, 'is_active' => true])->id,
            'image' => ProductImage::forceCreate(['product_id' => 1, 'path' => 'products/1/x-800.webp', 'alt' => '', 'sort' => 0])->id,
            'user' => $owner->id,
            'address' => Address::forceCreate(['user_id' => $owner->id, 'governorate_id' => 1, 'recipient_name' => 'س', 'phone' => '01012345678', 'city' => 'ن', 'street' => 'ش'])->id,
        ];
        $this->assertNotNull(Order::where('number', $number)->first());
    }

    /** @return array<int, array{0:string,1:string,2:array}> [method, uri, middleware] */
    private function routes(callable $filter): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            /** @var LaravelRoute $route */
            $mw = $route->gatherMiddleware();
            if (! str_starts_with($route->uri(), 'api/') || ! $filter($mw, $route)) {
                continue;
            }
            $uri = preg_replace_callback('/\{(\w+)(?::\w+)?\}/', fn ($m) => (string) ($this->params[$m[1]] ?? 1), $route->uri());
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $out[] = [$method, '/'.$uri, $mw];
            }
        }
        $this->assertNotEmpty($out);

        return $out;
    }

    public function test_guests_get_401_on_every_authenticated_endpoint(): void
    {
        $routes = $this->routes(fn ($mw) => in_array('auth:sanctum', $mw, true));
        foreach ($routes as [$method, $uri]) {
            $this->app['auth']->forgetGuards();
            $this->json($method, $uri)->assertStatus(401, "{$method} {$uri}");
        }
    }

    public function test_customers_get_403_everywhere_in_the_admin_area(): void
    {
        $customer = User::factory()->create();
        foreach ($this->routes(fn ($mw) => in_array('can:staff', $mw, true)) as [$method, $uri]) {
            $this->actingAs($customer)->json($method, $uri)->assertStatus(403, "{$method} {$uri}");
        }
    }

    public function test_staff_get_403_on_admin_only_actions(): void
    {
        $staff = User::factory()->create();
        $staff->forceFill(['role' => 'staff'])->save();
        $adminOnly = $this->routes(fn ($mw) => in_array('can:admin', $mw, true));
        $this->assertGreaterThan(15, count($adminOnly));
        foreach ($adminOnly as [$method, $uri]) {
            $this->actingAs($staff)->json($method, $uri)->assertStatus(403, "{$method} {$uri}");
        }
    }

    public function test_staff_can_work_orders_and_stock(): void
    {
        $staff = User::factory()->create();
        $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff);
        $this->getJson('/api/admin/orders')->assertOk();
        $this->getJson('/api/admin/orders/'.$this->params['order'])->assertOk()->assertJsonStructure(['data' => ['number', 'status', 'items', 'history']]);
        $this->getJson('/api/admin/stock/low')->assertOk();
        $this->getJson('/api/admin/dashboard')->assertOk();
    }
}
