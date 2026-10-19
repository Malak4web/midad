<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// BE-03 + BE-04 + BE-10: catalog, scent pyramid, spotlight identities, search index.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 64);
            $table->string('latin_name', 64)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('slug', 120)->unique();
            $table->string('name', 120);
            $table->string('latin_name', 120);
            $table->string('inspired_by', 120)->nullable();
            $table->string('signature_note', 120)->nullable();
            $table->text('description')->nullable();
            $table->string('concentration', 32)->default('EAU DE PARFUM');
            $table->text('search_text'); // normalized Arabic + Latin, see ArabicNormalizer
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unsignedInteger('sold_count')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'is_active', 'sort']);
        });
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement('ALTER TABLE products ADD FULLTEXT products_search_fulltext (search_text)');
        }

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('size_ml');
            $table->string('sku', 64)->unique();
            $table->unsignedInteger('price_piasters');
            $table->unsignedInteger('compare_at_piasters')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'size_ml']);
            $table->index('price_piasters');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt', 160)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->string('slug', 64)->unique();
            $table->timestamps();
        });

        Schema::create('product_note', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->string('layer', 8)->default('key'); // top | heart | base | key
            $table->unsignedTinyInteger('sort')->default(0);
            $table->primary(['product_id', 'note_id', 'layer']);
        });

        Schema::create('category_spotlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('tagline', 200);
            $table->json('palette'); // {bg, surface, fg, muted, accent}
            $table->string('texture', 24)->default('plain');
            $table->json('ingredients')->nullable();
            $table->string('scene_image')->nullable();
            $table->string('bottle_image')->nullable();
            $table->boolean('is_sample')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['category_id', 'is_active']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('change');
            $table->string('reason', 32); // sale | restock | adjustment | release | return
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('category_spotlights');
        Schema::dropIfExists('product_note');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
