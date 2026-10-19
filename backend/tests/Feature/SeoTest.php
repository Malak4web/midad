<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** BE-56 / BE-57 / INT-13 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private string $shell;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->shell = tempnam(sys_get_temp_dir(), 'spa');
        file_put_contents($this->shell, '<!doctype html><html lang="ar"><head><meta name="description" content="x" /><title>old</title></head><body><div id="app"></div></body></html>');
        config(['app.spa_index' => $this->shell, 'app.url' => 'https://midad.test']);
    }

    protected function tearDown(): void
    {
        @unlink($this->shell);
        parent::tearDown();
    }

    public function test_product_page_carries_its_own_meta_and_product_json_ld(): void
    {
        $res = $this->get('/p/victory-elixir')->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $html = $res->getContent();

        $this->assertStringContainsString('<title>فيكتوري إكسير · Victory Elixir | مداد</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://midad.test/p/victory-elixir" />', $html);
        $this->assertStringContainsString('<meta property="og:type" content="product" />', $html);
        $this->assertStringNotContainsString('noindex', $html);
        $this->assertSame(1, substr_count($html, '<title>'));

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $product = json_decode($m[1][0], true);
        $this->assertSame('Product', $product['@type']);
        $this->assertSame('EGP', $product['offers']['priceCurrency']);
        $this->assertSame('BreadcrumbList', json_decode($m[1][1], true)['@type']);
    }

    public function test_unknown_or_hidden_products_are_real_404s(): void
    {
        $this->get('/p/nothing-here')->assertNotFound()->assertSee('noindex', false);
        Product::where('slug', 'victory-elixir')->update(['is_active' => false]);
        $this->get('/p/victory-elixir')->assertNotFound();
        $this->get('/some/random/path')->assertNotFound();
    }

    public function test_category_static_and_private_pages(): void
    {
        $this->get('/c/men')->assertOk()->assertSee('<title>عطور رجالي | مداد</title>', false);
        $this->get('/discovery')->assertOk()->assertSee('<title>مجموعة الاكتشاف | مداد</title>', false);
        $home = $this->get('/')->assertOk()->assertSee('"@type":"Organization"', false)->getContent();
        // FE-51: the home payload rides in the shell, identical to /api/home
        preg_match('#<script type="application/json" id="midad-boot">(.*?)</script>#s', $home, $boot);
        $this->assertSame($this->getJson('/api/home')->json('data'), json_decode($boot[1], true)['home']);
        $this->assertStringNotContainsString('midad-boot', $this->get('/c/men')->getContent());
        foreach (['/checkout', '/admin/orders', '/account', '/order/MD-1', '/search?q=x'] as $path) {
            $this->get($path)->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
        }
    }

    public function test_values_are_escaped_and_json_ld_cannot_break_out(): void
    {
        Product::where('slug', 'victory-elixir')->first()->forceFill([
            'name' => 'عطر "<b>x</b>',
            'description' => 'وصف </script><script>alert(1)</script>',
        ])->save();
        $html = $this->get('/p/victory-elixir')->assertOk()->getContent();

        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('&quot;&lt;b&gt;', $html);
    }

    public function test_api_and_storage_paths_are_not_swallowed_by_the_shell(): void
    {
        $this->getJson('/api/does-not-exist')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_sitemap_lists_active_products_and_categories(): void
    {
        Product::where('slug', 'victory-elixir')->update(['is_active' => false]);
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $this->assertStringContainsString('https://midad.test/c/men', $xml);
        $this->assertStringNotContainsString('/p/victory-elixir', $xml);
        $this->assertSame(1 + 3 + Product::active()->count() + 9, count($doc->url));
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
        $this->app['env'] = 'production';
        $this->get('/robots.txt')->assertSee('Sitemap: https://midad.test/sitemap.xml')->assertSee('Disallow: /admin');
    }

    public function test_security_headers_and_csp_on_html_only(): void
    {
        $html = $this->get('/c/men')->assertOk();
        $html->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringContainsString("script-src 'self'", $html->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", $html->headers->get('Content-Security-Policy'));

        $api = $this->getJson('/api/home')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNull($api->headers->get('Content-Security-Policy'));
        $this->assertNull($api->headers->get('Strict-Transport-Security'));
    }
}
