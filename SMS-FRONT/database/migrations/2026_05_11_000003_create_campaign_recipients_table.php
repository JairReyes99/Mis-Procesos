<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('campaign_id');
            $table->string('phone', 20);
            $table->text('message');
            $table->tinyInteger('segments')->default(1);
            $table->string('encoding', 7)->default('GSM7'); // GSM7 or Unicode
            // 1=Pendiente 2=Enviado 3=Error 4=Bloqueado
            $table->tinyInteger('send_status')->default(1);
            $table->dateTime('sent_at')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')
                  ->references('id')->on('campaigns')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
