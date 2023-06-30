<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarClicksignSignatarioDocumentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clicksign_signatario_documentos', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('signatario_documento_clicksign_id')->nullable();
            $table->uuid('request_signature_id')->nullable();
            $table->integer('clicksign_documentos_id');
            $table->integer('clicksign_signatarios_id');
            $table->date('data_assinatura')->nullable();
            $table->string('ip')->nullable();
            $table->string('assinou_como')->nullable();
            $table->json('json_retorno')->nullable();
            $table->timestamps();

            $table->foreign('clicksign_documentos_id')
                ->references('id')
                ->on('clicksign_documentos')
                ->onDelete('NO ACTION');
            $table->foreign('clicksign_signatarios_id')
                ->references('id')
                ->on('clicksign_signatarios')
                ->onDelete('NO ACTION');
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clicksign_signatario_documentos');
    }
}
