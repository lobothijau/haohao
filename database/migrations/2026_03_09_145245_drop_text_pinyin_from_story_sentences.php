<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('story_sentences', function (Blueprint $table) {
            $table->dropColumn('text_pinyin');
        });
    }

    public function down(): void
    {
        Schema::table('story_sentences', function (Blueprint $table) {
            $table->text('text_pinyin')->after('text_zh');
        });
    }
};
