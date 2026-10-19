<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Models\Category;
use App\Models\CategorySpotlight;
use App\Models\FinderAnswer;
use App\Models\FinderQuestion;
use App\Models\Governorate;
use App\Models\Note;
use App\Models\Setting;
use App\Support\Contrast;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Categories, spotlight identities, shipping rates, settings and the finder (BE-47, BE-50, BE-54). */
class StoreController extends Controller
{
    public const INGREDIENTS = ['vanilla', 'amber', 'lavender', 'cardamom', 'strawberry', 'blackberry', 'jasmine', 'musk', 'incense', 'tobacco', 'oud', 'resin'];

    public function categories(): JsonResponse
    {
        return response()->json(['data' => Category::orderBy('sort')->withCount('products')->get()->map(fn ($c) => [
            'id' => $c->id, 'slug' => $c->slug, 'name' => $c->name, 'latin' => $c->latin_name, 'description' => $c->description,
            'active' => $c->is_active, 'sort' => $c->sort, 'products' => $c->products_count,
            'spotlights' => $c->spotlights()->with('product')->orderBy('sort')->get()->map(fn ($s) => $this->spotlight($s)),
        ])]);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:64'], 'latin_name' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['boolean'], 'sort' => ['integer', 'min:0'],
        ]));
        Cache::forget('home');

        return $this->categories();
    }

    public function saveSpotlight(Request $request, ?CategorySpotlight $spotlight = null): JsonResponse
    {
        $hex = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('category_id', $request->input('category_id'))->whereNull('deleted_at')],
            'tagline' => ['required', 'string', 'max:200'],
            'palette' => ['required', 'array'],
            'palette.bg' => $hex, 'palette.surface' => $hex, 'palette.fg' => $hex, 'palette.muted' => $hex, 'palette.accent' => $hex,
            'texture' => ['required', Rule::in(CategorySpotlight::TEXTURES)],
            'ingredients' => ['array', 'max:4'],
            'ingredients.*' => [Rule::in(self::INGREDIENTS), 'distinct'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ], ['product_id.exists' => 'اختر عطراً من نفس القسم.']);

        // The Readable World Rule, enforced on save (FE-41)
        $p = $data['palette'];
        $problems = [];
        if (Contrast::ratio($p['bg'], $p['fg']) < 4.5) {
            $problems['palette.fg'] = ['لون النص غير مقروء على لون الخلفية (التباين أقل من 4.5).'];
        }
        if (Contrast::ratio($p['surface'], $p['fg']) < 4.5) {
            $problems['palette.surface'] = ['لون النص غير مقروء على لون الكروت.'];
        }
        if (Contrast::ratio($p['bg'], $p['muted']) < 3) {
            $problems['palette.muted'] = ['اللون الثانوي باهت جداً على الخلفية (أقل من 3).'];
        }
        if ($problems) {
            throw ValidationException::withMessages($problems);
        }

        $data['is_sample'] = false;
        $spotlight = $spotlight?->exists ? tap($spotlight)->update($data) : CategorySpotlight::create($data);
        Cache::forget('home');

        return response()->json(['data' => $this->spotlight($spotlight->fresh('product'))], $spotlight->wasRecentlyCreated ? 201 : 200);
    }

    public function deleteSpotlight(CategorySpotlight $spotlight): JsonResponse
    {
        $spotlight->delete();
        Cache::forget('home');

        return response()->json(null, 204);
    }

    public function governorates(): JsonResponse
    {
        return response()->json(['data' => Governorate::orderBy('sort')->get()->map(fn ($g) => [
            'id' => $g->id, 'name' => $g->name, 'fee' => Money::egp($g->shipping_fee_piasters), 'daysMin' => $g->delivery_days_min, 'daysMax' => $g->delivery_days_max, 'active' => $g->is_active,
        ])]);
    }

    public function updateGovernorates(Request $request): JsonResponse
    {
        $rows = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.id' => ['required', 'integer', 'exists:governorates,id'],
            'rows.*.fee' => ['required', 'numeric', 'min:0', 'max:2000'],
            'rows.*.daysMin' => ['required', 'integer', 'min:0', 'max:30'],
            'rows.*.daysMax' => ['required', 'integer', 'gte:rows.*.daysMin', 'max:30'],
            'rows.*.active' => ['boolean'],
        ])['rows'];
        DB::transaction(function () use ($rows) {
            foreach ($rows as $r) {
                Governorate::whereKey($r['id'])->update(['shipping_fee_piasters' => Money::piasters($r['fee']), 'delivery_days_min' => $r['daysMin'], 'delivery_days_max' => $r['daysMax'], 'is_active' => $r['active'] ?? true]);
            }
        });

        return $this->governorates();
    }

    public function settings(): JsonResponse
    {
        $box = Setting::get('discovery_box', []);

        return response()->json(['data' => [
            'announcements' => Setting::get('announcements', []),
            'freeShippingThreshold' => Money::egp((int) Setting::get('free_shipping_threshold_piasters', 0)),
            'discoveryPrice' => Money::egp((int) ($box['price_piasters'] ?? 0)),
            'discoveryCreditDays' => (int) ($box['credit_days'] ?? 60),
            'giftWrapFee' => Money::egp((int) Setting::get('gift_wrap_fee_piasters', 0)),
            'reservationMinutes' => (int) Setting::get('reservation_minutes', 30),
            'whatsapp' => (string) Setting::get('whatsapp_number', ''),
        ]]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $d = $request->validate([
            'announcements' => ['array', 'max:5'], 'announcements.*' => ['string', 'max:80'],
            'freeShippingThreshold' => ['required', 'numeric', 'min:0', 'max:100000'],
            'discoveryPrice' => ['required', 'numeric', 'min:0', 'max:10000'],
            'discoveryCreditDays' => ['required', 'integer', 'min:1', 'max:365'],
            'giftWrapFee' => ['required', 'numeric', 'min:0', 'max:1000'],
            'reservationMinutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'whatsapp' => ['nullable', 'string', Phone::rule()],
        ]);
        $box = Setting::get('discovery_box', []);
        foreach ([
            'announcements' => array_values(array_filter($d['announcements'] ?? [])),
            'free_shipping_threshold_piasters' => Money::piasters($d['freeShippingThreshold']),
            'discovery_box' => array_merge($box, ['price_piasters' => Money::piasters($d['discoveryPrice']), 'credit_days' => $d['discoveryCreditDays']]),
            'gift_wrap_fee_piasters' => Money::piasters($d['giftWrapFee']),
            'reservation_minutes' => $d['reservationMinutes'],
            // wa.me wants the international form without "+": 2010XXXXXXXX
            'whatsapp_number' => empty($d['whatsapp']) ? '' : '2'.Phone::normalize($d['whatsapp']),
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('home');

        return $this->settings();
    }

    public function finder(): JsonResponse
    {
        $notes = Note::pluck('name', 'id');
        $cats = Category::pluck('slug', 'id');

        return response()->json(['data' => FinderQuestion::with('answers.weights')->orderBy('sort')->get()->map(fn ($q) => [
            'id' => $q->id, 'key' => $q->key, 'question' => $q->question,
            'answers' => $q->answers->map(fn ($a) => [
                'id' => $a->id, 'value' => $a->value, 'label' => $a->label,
                // a map, also when empty: {} not []
                'categories' => (object) $a->weights->where('target_type', 'category')->mapWithKeys(fn ($w) => [$cats[$w->target_id] ?? '?' => $w->weight])->all(),
                'notes' => $a->weights->where('target_type', 'note')->map(fn ($w) => $notes[$w->target_id] ?? null)->filter()->values(),
            ]),
        ])]);
    }

    public function updateFinderAnswer(Request $request, int $answer): JsonResponse
    {
        $d = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'categories' => ['array'], 'categories.*' => ['integer', 'between:-5,5'],
            'notes' => ['array', 'max:20'], 'notes.*' => ['string', 'exists:notes,name'],
        ]);
        $a = FinderAnswer::findOrFail($answer);
        DB::transaction(function () use ($a, $d) {
            $a->update(['label' => $d['label']]);
            $a->weights()->delete();
            $cats = Category::pluck('id', 'slug');
            foreach ($d['categories'] ?? [] as $slug => $w) {
                if (isset($cats[$slug]) && $w) {
                    $a->weights()->create(['target_type' => 'category', 'target_id' => $cats[$slug], 'weight' => $w]);
                }
            }
            foreach (Note::whereIn('name', $d['notes'] ?? [])->pluck('id') as $id) {
                $a->weights()->create(['target_type' => 'note', 'target_id' => $id, 'weight' => 2]);
            }
        });

        return $this->finder();
    }

    private function spotlight(CategorySpotlight $s): array
    {
        return [
            'id' => $s->id, 'categoryId' => $s->category_id, 'productId' => $s->product_id, 'product' => $s->product?->name,
            'tagline' => $s->tagline, 'palette' => $s->palette, 'texture' => $s->texture, 'ingredients' => $s->ingredients ?? [],
            'sample' => $s->is_sample, 'active' => $s->is_active, 'startsAt' => $s->starts_at?->toIso8601String(), 'endsAt' => $s->ends_at?->toIso8601String(),
            'pyramid' => $s->product ? ProductDetailResource::pyramid($s->product) : null,
        ];
    }
}
