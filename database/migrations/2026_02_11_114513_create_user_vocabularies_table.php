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
        Schema::create('user_vocabularies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dictionary_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_story_id')->nullable()->constrained('stories')->nullOnDelete();
            $table->unsignedBigInteger('source_sentence_id')->nullable();
            $table->text('user_note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'dictionary_entry_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vocabularies');
    }
};
