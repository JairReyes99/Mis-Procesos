<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_statuses', function (Blueprint $table) {
            $table->unsignedTinyInteger('id');
            $table->primary('id');
            $table->string('name', 50);
            $table->string('slug', 30)->unique();
            $table->string('color', 30)->default('pill--draft'); // clase CSS del pill
            $table->tinyInteger('order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_statuses');
    }
};
