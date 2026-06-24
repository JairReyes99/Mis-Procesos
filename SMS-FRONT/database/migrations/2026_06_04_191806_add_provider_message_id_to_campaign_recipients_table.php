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
        Schema::table('campaign_recipients', function (Blueprint $table) {
            // Message ID returned by the SMS provider for individual delivery tracking.
            $table->string('provider_message_id', 100)->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropColumn('provider_message_id');
        });
    }
};
