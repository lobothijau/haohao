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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('hsk_level')->default(1);
            $table->string('locale', 5)->default('id');
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_expires_at')->nullable();
            $table->unsignedInteger('streak_count')->default(0);
            $table->date('streak_last_date')->nullable();
            $table->string('avatar_url', 500)->nullable();

            $table->index('hsk_level');
            $table->index(['is_premium', 'premium_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['hsk_level']);
            $table->dropIndex(['is_premium', 'premium_expires_at']);

            $table->dropColumn([
                'hsk_level',
                'locale',
                'timezone',
                'is_premium',
                'premium_expires_at',
                'streak_count',
                'streak_last_date',
                'avatar_url',
            ]);
        });
    }
};
