<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategorySpotlight;
use App\Models\Note;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The 30 perfumes from the consultation guide (database/data/catalog.json, exported
 * from the frontend mock so both sides share one source). Prices and the
 * top/heart/base split are PLACEHOLDERS until the client sends real values (SET-05).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/catalog.json')), true);

        $categories = [];
        foreach ($data['categories'] as $i => $c) {
            $categories[$c['slug']] = Category::updateOrCreate(['slug' => $c['slug']], [
                'name' => $c['name'],
                'latin_name' => $c['latin'],
                'sort' => $i,
                'is_active' => true,
            ]);
        }

        foreach ($data['products'] as $i => $p) {
            $product = Product::updateOrCreate(['slug' => $p['slug']], [
                'category_id' => $categories[$p['category']]->id,
                'name' => $p['name'],
                'latin_name' => $p['latin'],
                'inspired_by' => $p['inspiredBy'],
                'signature_note' => $p['signature'],
                'concentration' => $p['concentration'],
                'is_active' => true,
                'sort' => $i,
            ]);

            foreach ($p['variants'] as $v) {
                $product->variants()->updateOrCreate(['size_ml' => $v['ml']], [
                    'sku' => strtoupper('MD-'.Str::of($p['slug'])->replace('-', '')->limit(12, '')).'-'.$v['ml'],
                    'price_piasters' => $v['price'] * 100,
                    'stock' => $v['stock'],
                    'is_active' => true,
                ]);
            }

            $product->notes()->detach();
            foreach ($p['notes'] as $n => $name) {
                $product->notes()->attach($this->note($name)->id, ['layer' => 'key', 'sort' => $n]);
            }
            $this->attachPyramid($product, $p['pyramid']);
        }

        foreach ($data['spotlights'] as $slug => $s) {
            $product = Product::where('slug', $s['product'])->firstOrFail();
            CategorySpotlight::updateOrCreate(['category_id' => $categories[$slug]->id], [
                'product_id' => $product->id,
                'tagline' => $s['tagline'],
                'palette' => $s['palette'],
                'texture' => $s['texture'],
                'ingredients' => $s['ingredients'],
                'is_sample' => true,
                'is_active' => true,
            ]);
            // the featured perfume's hand-written pyramid replaces the generated one
            $product->notes()->wherePivotIn('layer', ['top', 'heart', 'base'])->detach();
            $this->attachPyramid($product, $s['pyramid']);
        }
    }

    private function attachPyramid(Product $product, array $pyramid): void
    {
        foreach (['top', 'heart', 'base'] as $layer) {
            foreach ($pyramid[$layer] ?? [] as $n => $name) {
                $product->notes()->attach($this->note($name)->id, ['layer' => $layer, 'sort' => $n]);
            }
        }
    }

    private function note(string $name): Note
    {
        return Note::firstOrCreate(['name' => $name], ['slug' => $this->slug($name)]);
    }

    private function slug(string $name): string
    {
        $slug = Str::slug($name, '-', 'ar');

        return $slug !== '' ? $slug : 'note-'.substr(md5($name), 0, 8);
    }
}
