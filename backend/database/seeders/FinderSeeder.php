<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FinderQuestion;
use App\Models\Note;
use Illuminate\Database\Seeder;

/** «اسأل خبير العطور» questions and weights, same rules as frontend/src/utils/finder.js. */
class FinderSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/catalog.json')), true)['finder'];
        $categories = Category::pluck('id', 'slug');
        $notes = Note::all(['id', 'name']);

        foreach ($data as $qi => $q) {
            $question = FinderQuestion::updateOrCreate(['key' => $q['key']], ['question' => $q['q'], 'sort' => $qi, 'is_active' => true]);
            foreach ($q['options'] as $oi => $o) {
                $answer = $question->answers()->updateOrCreate(['value' => $o['v']], ['label' => $o['label'], 'sort' => $oi]);
                $answer->weights()->delete();
                foreach ($o['weight']['cat'] ?? [] as $slug => $w) {
                    $answer->weights()->create(['target_type' => 'category', 'target_id' => $categories[$slug], 'weight' => $w]);
                }
                foreach ($o['weight']['notes'] ?? [] as $keyword) {
                    foreach ($notes->filter(fn ($n) => str_contains($n->name, $keyword)) as $note) {
                        $answer->weights()->create(['target_type' => 'note', 'target_id' => $note->id, 'weight' => 2]);
                    }
                }
            }
        }
    }
}
