<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// C-08: without these indexes, every fetch_recipient_batch does a full table scan
// over all rows. With 250k recipients per campaign, that is O(n) per batch.
// These indexes turn the most frequent queries into O(log n) index seeks.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            // Covers: SELECT TOP(N) id WHERE campaign_id=? AND send_status=1 ORDER BY id ASC
            $table->index(
                ['campaign_id', 'send_status', 'id'],
                'idx_cr_campaign_status_id'
            );
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropIndex('idx_cr_campaign_status_id');
        });
    }
};
