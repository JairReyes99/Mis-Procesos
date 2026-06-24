<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sub_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('visible_menu')->default(true);
            $table->string('icon')->nullable()->default('fa fa-file');
            $table->string('route')->nullable();
            $table->integer('order')->nullable();
            $table->string('permission')->nullable();
            $table->unsignedBigInteger('status_id')->default(1); // 1 = Active
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_menus');
    }
};
