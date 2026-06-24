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
        Schema::table('campaigns', function (Blueprint $table) {
            // Sender name shown on recipient's phone (max 16 alphanumeric chars).
            // Required by Directo SMS ("from" field). Falls back to company name when null.
            $table->string('sender', 16)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('sender');
        });
    }
};
