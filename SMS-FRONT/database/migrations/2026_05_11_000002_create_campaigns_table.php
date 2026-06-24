<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name', 255);
            $table->unsignedBigInteger('send_type_id');
            $table->dateTime('scheduled_at')->nullable();
            $table->text('no_send_rules')->nullable();   // JSON: [{from:"21:00",to:"07:00"}]
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            // 1=Borrador 2=Programada 3=Procesando 4=Completada 5=Pausada 6=Cancelada
            $table->tinyInteger('campaign_status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                  ->references('id')->on('companies')
                  ->onDelete('set null');

            $table->foreign('send_type_id')
                  ->references('id')->on('campaign_send_types')
                  ->onDelete('no action');

            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
