<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Migrate existing plan strings to plan_id references
        $plans = Plan::all()->keyBy('slug');
        DB::table('subscriptions')->whereNotNull('plan')->get()->each(function ($subscription) use ($plans) {
            $plan = $plans->get($subscription->plan);
            if ($plan) {
                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update(['plan_id' => $plan->id]);
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('plan', 10)->nullable()->after('user_id');
        });

        // Migrate plan_id back to plan string
        $plans = Plan::all()->keyBy('id');
        DB::table('subscriptions')->whereNotNull('plan_id')->get()->each(function ($subscription) use ($plans) {
            $plan = $plans->get($subscription->plan_id);
            if ($plan) {
                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update(['plan' => $plan->slug]);
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
};
