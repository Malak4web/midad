<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BE-08 + BE-09: scent finder, store settings, admin activity log.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finder_questions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('question', 160);
            $table->unsignedTinyInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('finder_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finder_question_id')->constrained()->cascadeOnDelete();
            $table->string('value', 32);
            $table->string('label', 80);
            $table->unsignedTinyInteger('sort')->default(0);
            $table->unique(['finder_question_id', 'value']);
        });

        Schema::create('finder_answer_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finder_answer_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 16); // category | note
            $table->unsignedBigInteger('target_id');
            $table->tinyInteger('weight')->default(1);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->json('value');
            $table->timestamps();
        });

        Schema::create('admin_activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 48);
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_log');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('finder_answer_weights');
        Schema::dropIfExists('finder_answers');
        Schema::dropIfExists('finder_questions');
    }
};
