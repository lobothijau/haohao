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
        Schema::create('srs_review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('srs_card_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('previous_state', 15)->nullable();
            $table->string('new_state', 15)->nullable();
            $table->unsignedInteger('previous_interval')->nullable();
            $table->unsignedInteger('new_interval')->nullable();
            $table->decimal('previous_ease', 4, 2)->nullable();
            $table->decimal('new_ease', 4, 2)->nullable();
            $table->unsignedInteger('time_taken_ms')->nullable();
            $table->timestamp('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('srs_review_logs');
    }
};
