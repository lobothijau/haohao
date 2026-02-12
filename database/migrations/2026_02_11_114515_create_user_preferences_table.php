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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('show_pinyin')->default(true);
            $table->boolean('show_translation')->default(false);
            $table->string('font_size', 10)->default('medium');
            $table->string('reading_mode', 10)->default('full');
            $table->string('character_set', 15)->default('simplified');
            $table->unsignedSmallInteger('new_cards_per_day')->default(20);
            $table->unsignedSmallInteger('max_reviews_per_day')->default(100);
            $table->string('card_order', 15)->default('mixed');
            $table->boolean('daily_reminder')->default(false);
            $table->time('reminder_time')->default('09:00:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
