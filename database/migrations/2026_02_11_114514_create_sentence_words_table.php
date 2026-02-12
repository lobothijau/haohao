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
        Schema::create('sentence_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_sentence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dictionary_entry_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('surface_form', 50);

            $table->index(['story_sentence_id', 'position']);
            $table->index('dictionary_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentence_words');
    }
};
