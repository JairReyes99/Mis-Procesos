<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// C-01: worker_id allows atomic claiming of immediate (status=3) campaigns and
// lets a new worker detect stale claims from crashed workers.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('worker_id', 16)->nullable()->after('campaign_status');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('worker_id');
        });
    }
};
