<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaClicksignDocumentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clicksign_documentos', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('documento_clicksign_id')->nullable();
            $table->string('caminho_arquivo')->nullable();
            $table->string('status')->nullable();
            $table->date('data_finalizacao')->nullable();
            $table->json('json_retorno')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clicksign_documentos');
    }
}
