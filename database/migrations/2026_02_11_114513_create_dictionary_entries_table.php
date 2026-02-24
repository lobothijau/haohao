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
        Schema::create('dictionary_entries', function (Blueprint $table) {
            $table->id();
            $table->string('simplified', 50);
            $table->string('traditional', 50)->nullable();
            $table->string('pinyin', 100);
            $table->string('pinyin_numbered', 100)->nullable();
            $table->text('meaning_id');
            $table->text('meaning_en')->nullable();
            $table->unsignedTinyInteger('hsk_level')->nullable();
            $table->string('word_type', 50)->nullable();
            $table->unsignedInteger('frequency_rank')->nullable();
            $table->string('audio_url', 500)->nullable();
            $table->text('notes_id')->nullable();
            $table->string('hokkien_cognate', 100)->nullable();
            $table->timestamps();

            $table->index('hsk_level');
            $table->index('frequency_rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dictionary_entries');
    }
};
