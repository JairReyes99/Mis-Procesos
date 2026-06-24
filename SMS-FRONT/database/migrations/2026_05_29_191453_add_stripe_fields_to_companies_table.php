<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('settings');
            $table->string('stripe_pm_id')->nullable()->after('stripe_customer_id');
            $table->boolean('auto_recharge_enabled')->default(false)->after('stripe_pm_id');
            $table->decimal('auto_recharge_amount', 10, 2)->nullable()->after('auto_recharge_enabled');
            $table->decimal('auto_recharge_threshold', 10, 2)->nullable()->after('auto_recharge_amount');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_pm_id',
                'auto_recharge_enabled',
                'auto_recharge_amount',
                'auto_recharge_threshold',
            ]);
        });
    }
};
