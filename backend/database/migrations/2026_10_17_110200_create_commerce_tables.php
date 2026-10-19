<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BE-05 + BE-06 + BE-07: coupons, carts, orders, payments, gifts, discovery boxes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 16); // percent | fixed | free_shipping
            $table->unsignedInteger('value')->default(0); // percent (1-100) or piasters
            $table->unsignedInteger('min_subtotal_piasters')->default(0);
            $table->unsignedInteger('max_discount_piasters')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->string('kind', 24)->default('general'); // general | discovery_credit
            $table->string('phone', 20)->nullable()->index(); // bound credit
            $table->unsignedBigInteger('source_order_id')->nullable()->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('gift_wrap')->default(false);
            $table->string('gift_recipient', 60)->nullable();
            $table->string('gift_message', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16)->default('product'); // product | discovery_box
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quantity')->default(1);
            $table->json('meta')->nullable(); // discovery_box: {product_ids: [...6]}
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('placed')->index();
            $table->string('payment_method', 16); // cod | card | wallet
            $table->string('payment_status', 24)->default('unpaid')->index();
            $table->string('customer_name', 120);
            $table->string('phone', 20)->index();
            $table->string('email')->nullable();
            $table->foreignId('governorate_id')->constrained()->restrictOnDelete();
            $table->json('shipping_address');
            $table->unsignedInteger('subtotal_piasters');
            $table->unsignedInteger('discount_piasters')->default(0);
            $table->unsignedInteger('credit_piasters')->default(0);
            $table->unsignedInteger('shipping_piasters')->default(0);
            $table->unsignedInteger('gift_piasters')->default(0);
            $table->unsignedInteger('total_piasters');
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 40)->nullable();
            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamp('reserved_until')->nullable()->index();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16)->default('product');
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 160);
            $table->unsignedSmallInteger('size_ml')->nullable();
            $table->unsignedInteger('unit_price_piasters');
            $table->unsignedTinyInteger('quantity');
            $table->unsignedInteger('line_total_piasters');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('discovery_box_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('order_gifts', function (Blueprint $table) {
            $table->foreignId('order_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('wrap')->default(true);
            $table->string('recipient_name', 60)->nullable();
            $table->string('message', 120)->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 16); // paymob | cod
            $table->string('gateway_reference', 120)->nullable()->index();
            $table->string('transaction_id', 120)->nullable()->unique(); // idempotency for webhooks
            $table->unsignedInteger('amount_piasters');
            $table->string('currency', 3)->default('EGP');
            $table->string('status', 24)->default('pending'); // pending | paid | failed | refunded
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20)->index();
            $table->unsignedInteger('amount_piasters');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['coupon_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_gifts');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('discovery_box_selections');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('coupons');
    }
};
