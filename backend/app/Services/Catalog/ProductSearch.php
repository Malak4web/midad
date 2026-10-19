<?php

namespace App\Services\Catalog;

use App\Support\ArabicNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Search by Midad name, Latin name, the original perfume, or signature note (BE-27).
 * MySQL/MariaDB FULLTEXT (boolean, prefix) over the normalized search_text,
 * with a LIKE pass so short or partial Arabic words still match.
 */
final class ProductSearch
{
    public static function run(string $q, int $limit = 24): Collection
    {
        $norm = ArabicNormalizer::normalize($q);
        if ($norm === '') {
            return new Collection;
        }
        $words = array_values(array_filter(explode(' ', $norm), fn ($w) => mb_strlen($w) > 0));
        $like = '%'.addcslashes($norm, '%_\\').'%';
        // FULLTEXT terms keep letters and digits only: a word made of operators
        // ("--", "*", "%") would otherwise become "+*", a MySQL syntax error (TST-27)
        $terms = array_values(array_filter(array_map(fn ($w) => preg_replace('/[^\p{L}\p{N}]+/u', '', $w), $words), fn ($t) => $t !== ''));
        $fulltext = $terms && in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
        $boolean = implode(' ', array_map(fn ($t) => '+'.$t.'*', $terms));

        $query = ProductQuery::base()->where(function ($w) use ($words, $fulltext, $boolean) {
            $w->where(function ($all) use ($words) {
                foreach ($words as $word) {
                    $all->where('search_text', 'like', '%'.addcslashes($word, '%_\\').'%');
                }
            });
            if ($fulltext) {
                $w->orWhereRaw('MATCH(search_text) AGAINST(? IN BOOLEAN MODE)', [$boolean]);
            }
        });

        // rank: exact phrase first, then fulltext score, then catalog order
        $query->orderByRaw('CASE WHEN search_text LIKE ? THEN 0 ELSE 1 END', [$like]);
        if ($fulltext) {
            $query->orderByRaw('MATCH(search_text) AGAINST(? IN BOOLEAN MODE) DESC', [$boolean]);
        }

        return $query->orderBy('sort')->limit($limit)->get();
    }
}
