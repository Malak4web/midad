<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BE-02: governorates (shipping rates) and customer addresses.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governorates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->unsignedInteger('shipping_fee_piasters')->default(0);
            $table->unsignedTinyInteger('delivery_days_min')->default(2);
            $table->unsignedTinyInteger('delivery_days_max')->default(4);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('governorate_id')->constrained()->restrictOnDelete();
            $table->string('recipient_name', 120);
            $table->string('phone', 20);
            $table->string('city', 80);
            $table->string('street', 160);
            $table->string('building', 40)->nullable();
            $table->string('floor', 20)->nullable();
            $table->string('apartment', 20)->nullable();
            $table->string('landmark', 160)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('governorates');
    }
};
