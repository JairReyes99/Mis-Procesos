<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            // Precio por segmento aplicado al momento del envío
            $table->decimal('price_per_segment', 10, 4)->nullable()->after('encoding');
            // Costo total = price_per_segment × segments
            $table->decimal('cost', 10, 4)->nullable()->after('price_per_segment');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropColumn(['price_per_segment', 'cost']);
        });
    }
};
