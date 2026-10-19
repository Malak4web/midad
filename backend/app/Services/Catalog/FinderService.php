<?php

namespace App\Services\Catalog;

use App\Models\FinderAnswer;
use App\Models\FinderQuestion;
use App\Models\Product;
use Illuminate\Support\Collection;

/** «اسأل خبير العطور» scoring (BE-29): sums category and note weights of the chosen answers. */
final class FinderService
{
    /** @param array<string,string> $answers question key => answer value */
    public static function recommend(array $answers, int $n = 3): Collection
    {
        $questions = FinderQuestion::where('is_active', true)->pluck('id', 'key');
        $chosen = FinderAnswer::with('weights')
            ->where(function ($q) use ($answers, $questions) {
                foreach ($answers as $key => $value) {
                    if (isset($questions[$key])) {
                        $q->orWhere(fn ($a) => $a->where('finder_question_id', $questions[$key])->where('value', $value));
                    }
                }
            })
            ->get();
        if ($chosen->isEmpty()) {
            return collect();
        }

        $weights = $chosen->flatMap->weights;
        $catWeights = $weights->where('target_type', 'category')->groupBy('target_id')->map->sum('weight');
        $noteWeights = $weights->where('target_type', 'note')->groupBy('target_id')->map->sum('weight');

        return ProductQuery::base()->get()
            ->map(function (Product $p) use ($catWeights, $noteWeights) {
                $hits = $p->notes->filter(fn ($note) => $noteWeights->has($note->id))->unique('id');
                $score = ($catWeights[$p->category_id] ?? 0) + $hits->sum(fn ($note) => $noteWeights[$note->id]);

                return ['product' => $p, 'score' => $score, 'why' => $hits->pluck('name')->take(2)->values()->all()];
            })
            ->sortBy([['score', 'desc'], [fn ($r) => $r['product']->sort, 'asc']])
            ->take($n)
            ->values();
    }
}
