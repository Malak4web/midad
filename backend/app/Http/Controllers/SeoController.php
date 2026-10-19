<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\CatalogController;
use App\Models\Category;
use App\Models\Product;
use App\Support\Money;
use App\Support\SeoPage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * BE-56 / BE-57: serves the built SPA shell with per-URL meta, plus the
 * sitemap and robots.txt. API, Sanctum and storage routes never reach here.
 */
class SeoController extends Controller
{
    private const HOME_DESC = 'مداد: عطور عربية فاخرة بثبات طويل، من ١٠ إلى ١٠٠ مل. مجموعة اكتشاف، تغليف هدايا، والدفع عند الاستلام في كل مصر.';

    /** Static storefront pages: path => [title, description]. */
    private const PAGES = [
        '/discovery' => ['مجموعة الاكتشاف', 'ست عينات من ٢ مل تختارها بنفسك، وثمن العلبة يعود لك رصيداً على أول زجاجة.'],
        '/finder' => ['اسأل خبير العطور', 'أربعة أسئلة قصيرة، ونرشّح لك العطور الأقرب لذوقك.'],
        '/gifts' => ['الهدايا', 'عطر في علبة مداد مع كارت بخط يدك، يصل مغلّفاً لأي محافظة في مصر.'],
        '/about' => ['قصة مداد', 'مداد: الحبر الذي يكتب أثراً يبقى. عطور عربية بتركيز عالٍ وثبات طويل.'],
        '/help/shipping' => ['الشحن والاسترجاع', 'مدة التوصيل ورسومه لكل محافظة، وسياسة الاستبدال والاسترجاع.'],
        '/help/faq' => ['الأسئلة الشائعة', 'إجابات عن الثبات والأحجام والدفع والشحن.'],
        '/help/contact' => ['تواصل معنا', 'راسلنا على واتساب أو بالبريد.'],
        '/legal/privacy' => ['سياسة الخصوصية', 'كيف نتعامل مع بياناتك.'],
        '/legal/terms' => ['الشروط والأحكام', 'شروط الشراء من مداد.'],
    ];

    /** Pages that exist for one visitor and should never be indexed. */
    private const PRIVATE_PREFIXES = ['/admin', '/cart', '/checkout', '/pay', '/order', '/track', '/account', '/login', '/register', '/forgot-password', '/reset-password', '/verify-email', '/search'];

    public function page(Request $request): Response
    {
        $path = '/'.ltrim($request->path(), '/');
        $page = $this->resolve($path);

        return response($page->render($this->template()), $page->status)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            // the shell is tiny and changes on every deploy; the hashed assets carry the long cache
            ->header('Cache-Control', 'no-cache');
    }

    private function resolve(string $path): SeoPage
    {
        if ($path === '/') {
            return new SeoPage(SeoPage::SITE, self::HOME_DESC, '/', jsonLd: [$this->organization()], boot: ['home' => CatalogController::homePayload()], firstPaint: $this->firstPaint());
        }
        if (preg_match('#^/p/([^/]+)$#u', $path, $m)) {
            return $this->product(urldecode($m[1])) ?? $this->missing($path);
        }
        if (preg_match('#^/c/([^/]+)$#u', $path, $m)) {
            return $this->category(urldecode($m[1])) ?? $this->missing($path);
        }
        if (isset(self::PAGES[$path])) {
            [$title, $desc] = self::PAGES[$path];

            return new SeoPage($title, $desc, $path);
        }
        foreach (self::PRIVATE_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return new SeoPage(SeoPage::SITE, self::HOME_DESC, $path, index: false);
            }
        }

        return $this->missing($path);
    }

    private function product(string $slug): ?SeoPage
    {
        $p = Product::active()->where('slug', $slug)
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true), 'images'])
            ->first();
        if (! $p || ! $p->category?->is_active) {
            return null;
        }
        $image = $p->images->first() ? '/storage/'.$p->images->first()->path : null;
        $desc = SeoPage::excerpt($p->description ?: trim($p->signature_note.'. مستوحى من '.$p->inspired_by));
        $path = '/p/'.$p->slug;
        $prices = $p->variants->pluck('price_piasters');
        $inStock = $p->variants->contains(fn ($v) => $v->stock > 0);

        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $p->name,
            'alternateName' => $p->latin_name,
            'description' => $desc,
            'sku' => $p->slug,
            'brand' => ['@type' => 'Brand', 'name' => SeoPage::SITE],
            'category' => $p->category->name,
            'url' => rtrim(config('app.url'), '/').$path,
        ];
        if ($image) {
            $ld['image'] = rtrim(config('app.url'), '/').$image;
        }
        if ($prices->isNotEmpty()) {
            $ld['offers'] = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'EGP',
                'lowPrice' => Money::egp($prices->min()),
                'highPrice' => Money::egp($prices->max()),
                'offerCount' => $prices->count(),
                'availability' => 'https://schema.org/'.($inStock ? 'InStock' : 'OutOfStock'),
            ];
        }

        return new SeoPage("{$p->name} · {$p->latin_name}", $desc, $path, $image, 'product', jsonLd: [$ld, $this->breadcrumbs([
            [$p->category->name, '/c/'.$p->category->slug],
            [$p->name, $path],
        ])]);
    }

    private function category(string $slug): ?SeoPage
    {
        $c = Category::where('is_active', true)->where('slug', $slug)->first();
        if (! $c) {
            return null;
        }
        $path = '/c/'.$c->slug;
        $desc = SeoPage::excerpt($c->description ?: "عطور {$c->name} من مداد، بأحجام من ١٠ إلى ١٠٠ مل.");

        return new SeoPage("عطور {$c->name}", $desc, $path, jsonLd: [$this->breadcrumbs([[$c->name, $path]])]);
    }

    private function missing(string $path): SeoPage
    {
        return new SeoPage('الصفحة غير موجودة', self::HOME_DESC, $path, index: false, status: 404);
    }

    private function organization(): array
    {
        $base = rtrim(config('app.url'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => SeoPage::SITE,
            'alternateName' => 'Midad',
            'url' => $base.'/',
            'logo' => $base.'/favicon.svg',
        ];
    }

    /** @param  array<int, array{0:string,1:string}>  $trail */
    private function breadcrumbs(array $trail): array
    {
        $base = rtrim(config('app.url'), '/');
        $items = [['@type' => 'ListItem', 'position' => 1, 'name' => SeoPage::SITE, 'item' => $base.'/']];
        foreach ($trail as $i => [$name, $path]) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $name, 'item' => $base.$path];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /** FE-51: the prerendered header + hero for "/", if the build produced one. */
    private function firstPaint(): ?string
    {
        $file = dirname(config('app.spa_index')).'/home-first-paint.html';

        return is_file($file) ? file_get_contents($file) : null;
    }

    private function template(): string
    {
        $file = config('app.spa_index');
        abort_unless(is_file($file), 503, 'Storefront build missing: run `npm run build:deploy` in frontend/.');

        return file_get_contents($file);
    }

    public function sitemap(): Response
    {
        $xml = Cache::remember('seo:sitemap', 3600, function () {
            $base = rtrim(config('app.url'), '/');
            $e = fn ($v) => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $urls = [[$base.'/', null, '1.0']];
            foreach (Category::where('is_active', true)->orderBy('sort')->get() as $c) {
                $urls[] = [$base.'/c/'.rawurlencode($c->slug), $c->updated_at, '0.8'];
            }
            $products = Product::active()->whereHas('category', fn ($q) => $q->where('is_active', true))
                ->orderBy('sort')->get(['slug', 'updated_at']);
            foreach ($products as $p) {
                $urls[] = [$base.'/p/'.rawurlencode($p->slug), $p->updated_at, '0.7'];
            }
            foreach (array_keys(self::PAGES) as $path) {
                $urls[] = [$base.$path, null, '0.4'];
            }

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach ($urls as [$loc, $mod, $prio]) {
                $out .= '  <url><loc>'.$e($loc).'</loc>'.($mod ? '<lastmod>'.$mod->toDateString().'</lastmod>' : '').'<priority>'.$prio.'</priority></url>'."\n";
            }

            return $out.'</urlset>'."\n";
        });

        return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /api/', 'Disallow: /cart', 'Disallow: /checkout', 'Disallow: /account', 'Disallow: /order/', 'Disallow: /pay/', 'Disallow: /search', '', 'Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml']
            // staging and local must never be indexed
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
