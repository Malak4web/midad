<?php

namespace Tests\Feature;

use App\Models\AdminActivity;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role])->save();

        return $u;
    }

    private function productPayload(array $extra = []): array
    {
        return array_merge([
            'category_id' => Category::where('slug', 'oriental')->value('id'),
            'name' => 'عنبر الليل', 'latin_name' => 'Amber Night', 'inspired_by' => 'عطر تجريبي', 'signature_note' => 'العنبر والعود',
            'is_active' => true,
            'variants' => [['size_ml' => 50, 'price' => 1100, 'stock' => 12], ['size_ml' => 100, 'price' => 1600, 'stock' => 5]],
            'notes' => ['العنبر', 'العود', 'الزعفران'],
            'pyramid' => ['top' => ['الزعفران'], 'heart' => ['العود'], 'base' => ['العنبر']],
        ], $extra);
    }

    private function placeOrder(): Order
    {
        Mail::fake();
        $res = $this->withCredentials()->withHeader('Origin', 'http://localhost:5391')->postJson('/api/cart/items', ['slug' => 'ocean-wave', 'size_ml' => 50]);
        // signed-in carts live on the account; guests carry the cart cookie
        $token = collect($res->headers->getCookies())->first(fn ($c) => $c->getName() === 'midad_cart')?->getValue();
        $req = $this->withCredentials()->withHeader('Origin', 'http://localhost:5391');
        ($token ? $req->withUnencryptedCookie('midad_cart', $token) : $req)
            ->postJson('/api/checkout', ['customer_name' => '=HYPERLINK("x")', 'phone' => '01012345678', 'governorate_id' => 1, 'city' => 'x', 'street' => 'y', 'payment_method' => 'cod'])
            ->assertCreated();

        return Order::latest('id')->firstOrFail();
    }

    public function test_roles_gate_the_admin(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
        $this->actingAs($this->user('customer'))->getJson('/api/admin/dashboard')->assertForbidden()->assertJsonPath('message', 'ليس لديك صلاحية لهذا الإجراء.');
        $staff = $this->user('staff');
        $this->actingAs($staff)->getJson('/api/admin/dashboard')->assertOk()->assertJsonStructure(['data' => ['today', 'week', 'toConfirm', 'lowStock']]);
        $this->actingAs($staff)->getJson('/api/admin/orders')->assertOk();
        $this->actingAs($staff)->postJson('/api/admin/products', $this->productPayload())->assertForbidden();
        $this->actingAs($staff)->getJson('/api/admin/coupons')->assertForbidden();
    }

    public function test_admin_creates_and_edits_a_product_with_sizes_and_pyramid(): void
    {
        Cache::put('home', ['stale' => true]);
        $admin = $this->user('admin');
        $res = $this->actingAs($admin)->postJson('/api/admin/products', $this->productPayload())->assertCreated()
            ->assertJsonPath('data.slug', 'amber-night')->assertJsonPath('data.pyramid.heart', ['العود'])->assertJsonCount(2, 'data.variants');
        $this->assertFalse(Cache::has('home'), 'home cache is cleared');
        $id = $res->json('data.id');

        $this->actingAs($admin)->putJson("/api/admin/products/{$id}", $this->productPayload([
            'variants' => [['size_ml' => 50, 'price' => 1200], ['size_ml' => 30, 'price' => 800]],
        ]))->assertOk();
        $p = Product::with('variants')->find($id);
        $this->assertSame(120000, $p->variants->firstWhere('size_ml', 50)->price_piasters);
        $this->assertSame(12, $p->variants->firstWhere('size_ml', 50)->stock, 'stock is not touched by edits');
        $this->assertFalse($p->variants->firstWhere('size_ml', 100)->is_active, 'removed size is deactivated, not deleted');
        $this->assertTrue(AdminActivity::where('user_id', $admin->id)->exists());

        $this->actingAs($admin)->postJson('/api/admin/products', $this->productPayload(['variants' => [['size_ml' => 40, 'price' => 1]]]))->assertStatus(422);
    }

    public function test_spotlight_palettes_must_be_readable(): void
    {
        $admin = $this->user('admin');
        $cat = Category::where('slug', 'men')->first();
        $base = ['category_id' => $cat->id, 'product_id' => Product::where('slug', 'wanted-night')->value('id'), 'tagline' => 'هيل وليمون', 'texture' => 'resin', 'ingredients' => ['cardamom']];

        $this->actingAs($admin)->postJson('/api/admin/spotlights', $base + ['palette' => ['bg' => '#F2EADB', 'surface' => '#F8F3E8', 'fg' => '#E8E0D0', 'muted' => '#DDD5C5', 'accent' => '#B08D57']])
            ->assertStatus(422)->assertJsonPath('errors', fn ($e) => isset($e['palette.fg'], $e['palette.muted']));

        $this->actingAs($admin)->postJson('/api/admin/spotlights', $base + ['palette' => ['bg' => '#1A2A22', 'surface' => '#22362C', 'fg' => '#F2EADB', 'muted' => '#B8C9BC', 'accent' => '#D6B77E']])
            ->assertCreated()->assertJsonPath('data.sample', false);

        $this->actingAs($admin)->postJson('/api/admin/spotlights', array_merge($base, ['product_id' => Product::where('slug', 'her-blossom')->value('id')]) + ['palette' => ['bg' => '#1A2A22', 'surface' => '#22362C', 'fg' => '#F2EADB', 'muted' => '#B8C9BC', 'accent' => '#D6B77E']])
            ->assertStatus(422)->assertJsonPath('errors.product_id.0', 'اختر عطراً من نفس القسم.');
    }

    public function test_stock_adjustments_are_logged_and_cannot_undercut_reservations(): void
    {
        $staff = $this->user('staff');
        $order = $this->placeOrder();
        $v = ProductVariant::find($order->items->first()->product_variant_id);

        $this->actingAs($staff)->postJson("/api/admin/variants/{$v->id}/stock", ['change' => 10, 'reason' => 'restock', 'note' => 'شحنة جديدة'])->assertOk();
        $this->assertSame(30, $v->fresh()->stock);
        $this->actingAs($staff)->getJson("/api/admin/variants/{$v->id}/movements")->assertJsonPath('data.0.change', 10);

        $this->actingAs($staff)->postJson("/api/admin/variants/{$v->id}/stock", ['change' => -30, 'reason' => 'adjustment'])
            ->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['change']]); // the usual 422 shape
    }

    public function test_staff_moves_orders_and_prints_waybills(): void
    {
        $staff = $this->user('staff');
        $order = $this->placeOrder();

        $this->actingAs($staff)->getJson("/api/admin/orders/{$order->number}")->assertOk()->assertJsonPath('data.allowed', ['confirmed', 'cancelled']);
        $this->actingAs($staff)->postJson("/api/admin/orders/{$order->number}/status", ['status' => 'shipped'])->assertStatus(422);
        $this->actingAs($staff)->postJson("/api/admin/orders/{$order->number}/status", ['status' => 'confirmed', 'note' => 'اتصلنا بالعميل'])
            ->assertOk()->assertJsonPath('data.status', 'confirmed')->assertJsonPath('data.history.1.by', $staff->name);
        $this->actingAs($staff)->get("/api/admin/orders/{$order->number}/waybill")->assertOk()->assertSee($order->number)->assertSee('المطلوب تحصيله');
        $this->actingAs($staff)->getJson('/api/admin/orders?status=confirmed')->assertJsonPath('meta.total', 1);

        // the internal note: a body without the key is a 422, not a 500
        $this->actingAs($staff)->putJson("/api/admin/orders/{$order->number}/note", [])->assertStatus(422);
        $this->actingAs($staff)->putJson("/api/admin/orders/{$order->number}/note", ['internal_note' => null])->assertOk();
    }

    public function test_settings_store_whatsapp_in_international_form(): void
    {
        $admin = $this->user('admin');
        $base = $this->actingAs($admin)->getJson('/api/admin/settings')->json('data');
        foreach (['01012345678', '+20 10 1234 5678', '٠١٠١٢٣٤٥٦٧٨', '201012345678'] as $typed) {
            $this->actingAs($admin)->putJson('/api/admin/settings', ['whatsapp' => $typed] + $base)
                ->assertOk()->assertJsonPath('data.whatsapp', '201012345678');
        }
        $this->actingAs($admin)->putJson('/api/admin/settings', ['whatsapp' => '210123456789'] + $base)->assertStatus(422);
        $this->actingAs($admin)->getJson('/api/admin/products?per_page=-5')->assertStatus(422);
    }

    public function test_coupons_crud_and_report_with_safe_csv(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->postJson('/api/admin/coupons', ['code' => 'ramadan15', 'type' => 'percent', 'value' => 15, 'per_customer_limit' => 1])
            ->assertCreated()->assertJsonPath('data.code', 'RAMADAN15')->assertJsonPath('data.label', 'خصم 15٪');
        $this->actingAs($admin)->postJson('/api/admin/coupons', ['code' => 'bad code!', 'type' => 'fixed', 'value' => 50])->assertStatus(422);
        $this->assertSame(1, Coupon::count());

        $order = $this->placeOrder();
        $this->actingAs($admin)->getJson('/api/admin/reports')->assertOk()
            ->assertJsonPath('data.totals.orders', 1)->assertJsonPath('data.topProducts.0.name', 'أوشن ويف')->assertJsonCount(30, 'data.daily');

        $csv = $this->actingAs($admin)->get('/api/admin/reports/orders.csv')->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString($order->number, $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'formula-looking names are neutralized');
    }

    public function test_product_images_are_reencoded_to_webp(): void
    {
        Storage::fake('public');
        $admin = $this->user('admin');
        $p = Product::where('slug', 'qahwa-fusion')->first();
        $res = $this->actingAs($admin)->post("/api/admin/products/{$p->id}/images", ['image' => UploadedFile::fake()->image('bottle.jpg', 1200, 1500)], ['Accept' => 'application/json'])->assertCreated();
        $url = $res->json('data.images.0.url');
        $this->assertStringEndsWith('-800.webp', $url);
        $base = str_replace(['/storage/', '-800.webp'], '', $url);
        foreach ([400, 800, 1600] as $w) {
            Storage::disk('public')->assertExists("{$base}-{$w}.webp");
        }
        // INT-12: the storefront gets every rendition as a srcset
        $this->getJson('/api/products/qahwa-fusion')->assertOk()
            ->assertJsonPath('data.images.0.src', $url)
            ->assertJsonPath('data.images.0.srcset', "/storage/{$base}-400.webp 400w, /storage/{$base}-800.webp 800w, /storage/{$base}-1600.webp 1600w");
        $this->actingAs($admin)->post("/api/admin/products/{$p->id}/images", ['image' => UploadedFile::fake()->create('evil.php', 10, 'image/jpeg')], ['Accept' => 'application/json'])->assertStatus(422);
    }
}
