<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Governorate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** TST-07: every migration rolls back cleanly and the seeders build a working store. */
class MigrationsTest extends TestCase
{
    public function test_migrations_roll_all_the_way_back_and_up_again(): void
    {
        Artisan::call('migrate:fresh');
        $tables = ['users', 'products', 'product_variants', 'orders', 'order_items', 'coupons', 'category_spotlights', 'finder_questions', 'settings', 'governorates'];
        foreach ($tables as $t) {
            $this->assertTrue(Schema::hasTable($t), "missing {$t} after migrate");
        }

        $this->assertSame(0, Artisan::call('migrate:rollback', ['--step' => 1000]));
        foreach ($tables as $t) {
            $this->assertFalse(Schema::hasTable($t), "{$t} survived rollback");
        }

        $this->assertSame(0, Artisan::call('migrate'));
        $this->assertTrue(Schema::hasTable('orders'));
    }

    public function test_seeders_build_the_store_and_can_run_twice(): void
    {
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $counts = [Product::count(), Category::count(), Governorate::count()];

        $this->assertSame(30, $counts[0]);
        $this->assertSame(3, $counts[1]);
        $this->assertSame(27, $counts[2]);
        $this->assertTrue(User::where('role', 'admin')->exists());
        $this->assertSame(30 * 4, Product::withCount('variants')->get()->sum('variants_count'));

        // re-seeding an existing database must not duplicate anything
        $this->assertSame(0, Artisan::call('db:seed', ['--force' => true]));
        $this->assertSame($counts, [Product::count(), Category::count(), Governorate::count()]);
    }

    protected function tearDown(): void
    {
        // leave a seeded schema behind for the next test class
        Artisan::call('migrate:fresh', ['--seed' => true]);
        parent::tearDown();
    }
}
