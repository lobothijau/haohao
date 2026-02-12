<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('title_zh', 500);
            $table->string('title_pinyin', 500);
            $table->string('title_id', 500);
            $table->string('slug', 500)->unique();
            $table->text('description_id')->nullable();
            $table->unsignedTinyInteger('hsk_level');
            $table->decimal('difficulty_score', 3, 2)->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('unique_word_count')->default(0);
            $table->unsignedInteger('sentence_count')->default(0);
            $table->unsignedTinyInteger('estimated_minutes')->default(5);
            $table->string('thumbnail_url', 500)->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('content_source', 20)->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['hsk_level', 'is_published', 'is_premium']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
