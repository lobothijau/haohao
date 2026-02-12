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
        Schema::create('srs_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dictionary_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_vocabulary_id')->constrained()->cascadeOnDelete();
            $table->string('card_state', 15)->default('new');
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->unsignedInteger('lapses')->default(0);
            $table->unsignedTinyInteger('learning_step')->default(0);
            $table->timestamp('due_at');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('graduated_at')->nullable();
            $table->string('card_type', 15)->default('recognition');
            $table->timestamps();

            $table->unique(['user_id', 'dictionary_entry_id', 'card_type']);
            $table->index(['user_id', 'card_state', 'due_at']);
            $table->index(['user_id', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('srs_cards');
    }
};
