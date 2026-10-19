<?php

namespace Tests\Feature;

use App\Models\CategorySpotlight;
use App\Models\Note;
use App\Models\Product;
use App\Support\Slugger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_home_returns_three_sections_with_spotlights(): void
    {
        $res = $this->getJson('/api/home')->assertOk();

        $res->assertJsonCount(3, 'data.sections')
            ->assertJsonPath('data.discovery.samples', 6)
            ->assertJsonPath('data.sections.0.category.slug', 'men')
            ->assertJsonPath('data.sections.0.spotlight.product.slug', 'victory-elixir')
            ->assertJsonPath('data.sections.0.spotlight.sample', true)
            ->assertJsonCount(9, 'data.sections.0.others');
        $this->assertSame(['الهيل', 'الخزامى'], $res->json('data.sections.0.spotlight.pyramid.top'));
    }

    public function test_cached_home_is_identical_to_the_first_response(): void
    {
        $first = $this->getJson('/api/home')->assertOk()->json();
        $cached = $this->getJson('/api/home')->assertOk()->json();

        $this->assertSame($first, $cached);
        $this->assertSame('Victory Elixir', $cached['data']['sections'][0]['spotlight']['product']['latin']);
    }

    public function test_home_falls_back_to_midad_palette_without_a_spotlight(): void
    {
        CategorySpotlight::query()->update(['is_active' => false]);

        $this->getJson('/api/home')->assertOk()
            ->assertJsonPath('data.sections.0.spotlight', null)
            ->assertJsonPath('data.sections.0.palette.bg', '#3B342C')
            ->assertJsonCount(10, 'data.sections.0.others');
    }

    public function test_category_listing_filters_and_sorts(): void
    {
        $res = $this->getJson('/api/categories/oriental?sort=price_desc&per_page=5')->assertOk();
        $res->assertJsonCount(5, 'data')->assertJsonPath('meta.total', 10)->assertJsonPath('meta.category.name', 'شرقي ونيش');

        $this->getJson('/api/categories/men?sort=evil')->assertStatus(422)->assertJsonPath('message', 'راجع البيانات المكتوبة.');
        $this->getJson('/api/categories/nope')->assertNotFound()->assertJsonPath('message', 'غير موجود.');
    }

    public function test_note_filter_returns_only_matching_perfumes(): void
    {
        $res = $this->getJson('/api/products?note='.urlencode(Note::where('name', 'العود')->value('slug')))->assertOk();
        foreach ($res->json('data') as $p) {
            $this->assertContains('العود', $p['notes']);
        }
    }

    public function test_product_detail_has_prices_pyramid_and_similar(): void
    {
        $this->getJson('/api/products/black-oud-velvet')->assertOk()
            ->assertJsonPath('data.latin', 'Black Oud Velvet')
            ->assertJsonPath('data.inspiredBy', 'بلاك أفغانو')
            ->assertJsonPath('data.variants.2.ml', 50)
            ->assertJsonPath('data.variants.2.price', 1050)
            ->assertJsonPath('data.pyramid.heart', ['التبغ', 'العود'])
            ->assertJsonCount(4, 'meta.similar');
    }

    public function test_inactive_products_are_hidden(): void
    {
        Product::where('slug', 'ocean-wave')->update(['is_active' => false]);
        $this->getJson('/api/products/ocean-wave')->assertNotFound();
    }

    public function test_search_by_original_name_latin_and_partial_arabic(): void
    {
        $this->assertSame('victory-elixir', $this->getJson('/api/search?q='.urlencode('انفيكتوس'))->json('data.0.slug'));
        $this->assertSame('black-oud-velvet', $this->getJson('/api/search?q=black%20oud')->json('data.0.slug'));
        $slugs = collect($this->getJson('/api/search?q='.urlencode('عود'))->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('the-rebel-oud'));
        $this->getJson('/api/search')->assertStatus(422)->assertJsonPath('errors.q.0', 'اكتب ما تبحث عنه.');
    }

    public function test_finder_recommends_three_with_reasons(): void
    {
        $res = $this->postJson('/api/finder/result', ['answers' => ['who' => 'him', 'when' => 'night', 'mood' => 'oud']])->assertOk();
        $res->assertJsonCount(3, 'data');
        $this->assertContains('العود', $res->json('data.0.product.notes'));
        $this->assertNotEmpty($res->json('data.0.why'));

        $this->postJson('/api/finder/result', [])->assertStatus(422);
    }

    public function test_slugger_makes_unique_slugs(): void
    {
        $this->assertSame('victory-elixir-2', Slugger::unique(Product::class, 'Victory Elixir'));
        $this->assertSame('victory-elixir', Slugger::unique(Product::class, 'Victory Elixir', Product::where('slug', 'victory-elixir')->value('id')));
        $this->assertSame('عود-ملكي', Slugger::unique(Product::class, 'عود مَلَكي'));
    }
}
