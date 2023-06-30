<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaClicksignErros extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clicksign_erros', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('clicksign_signatario_documentos_id')->nullable();
            $table->integer('clicksign_documentos_id')->nullable();
            $table->integer('clicksign_signatarios_id')->nullable();
            $table->string('mensagem_erro')->nullable();
            $table->json('json_retorno_erro')->nullable();
            $table->json('json_enviado')->nullable();
            $table->timestamps();

            $table->foreign('clicksign_documentos_id')
                ->references('id')
                ->on('clicksign_documentos')
                ->onDelete('NO ACTION');
            $table->foreign('clicksign_signatarios_id')
                ->references('id')
                ->on('clicksign_signatarios')
                ->onDelete('NO ACTION');
            $table->foreign('clicksign_signatario_documentos_id')
                ->references('id')
                ->on('clicksign_signatario_documentos')
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
        Schema::dropIfExists('clicksign_erros');
    }
}
