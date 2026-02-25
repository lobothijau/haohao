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
        Schema::table('stories', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('created_by')->constrained('series')->nullOnDelete();
            $table->unsignedSmallInteger('series_order')->nullable()->after('series_id');
            $table->index(['series_id', 'series_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropIndex(['series_id', 'series_order']);
            $table->dropConstrainedForeignId('series_id');
            $table->dropColumn('series_order');
        });
    }
};
